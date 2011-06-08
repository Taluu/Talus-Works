<?php
/**
 * Fonctions qui gèrent les synchronisations entre forums, sujets, etc.
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *      
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *      
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA. 
 *
 * @package Talus' Works
 * @author Baptiste "Talus" Clavié <talusch@gmail.com>
 * @copyright ©Talus, Talus' Works 2008+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 20/03/2008, Talus
 * @last 11/01/2009, Talus
 */

if (!defined('COMMON') || COMMON == false) {
	header('Location: ' . ROOT . 'index.php');
	exit;
}

class Sync {
    /**
     * Synchronise des forums. Opérations plutôt longuettes.
     * 
     * @param integer $id,... Id des forums à mettre à jour.
     * @return void
     * @static 
     */
    static public function forums(){
        /*
         * On sélectionne les forums ayant des sujets, et on rpends le
         * nombre de sujets, de messges, la dernière date, et le dernier sujet 
         * par forums.
         */
        $sql = 'SELECT f_id, COALESCE(MAX(t_last_pid), 0) as f_last_pid, COALESCE(SUM(t_posts), 0) AS f_replies,
                        f_name, f_parent, f_level, f_left, f_right,
                        (SELECT lt.t_tid 
                            FROM forums_topics lt 
                            WHERE lt.t_fid = t.t_fid 
                            ORDER BY t_last_time DESC 
                            LIMIT 1) AS f_last_tid, 
                        (SELECT COUNT(*)
                            FROM forums_topics tc
                            WHERE tc.t_fid = f_id) AS f_topics 
                    FROM forums f
                    LEFT JOIN forums_topics t ON f.f_id = t.t_fid
                    GROUP BY f_id
                    ORDER BY f_level ASC;';
        
        #REQ SYNC_1
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        /*
         * Il s'agit ici de faire deux boucles ; une qui repertorie tous les
         * forums, et une autre qui permet de mettre corectement à jour le 
         * dernier sujet !
         */
        foreach ($datas as $data){
            if (!$data['f_last_tid']){
                $data['f_last_tid'] = 0;
            }
            
            $tree[$data['f_id']] = $data;
            
            $id = $data['f_id'];
            $parent[$id] = $data['f_parent'];
            
            while ($parent[$id]){
                $tree[$parent[$id]]['f_replies'] += $data['f_replies'];
                $tree[$parent[$id]]['f_topics'] += $data['f_topics'];
                
                /*
                 * On ne met à jour le dernier sujet actif QUE si... L'id du
                 * dernier post est plus vieux !
                 */
                if ($tree[$parent[$id]]['f_last_pid'] < $data['f_last_pid']){
                    $tree[$parent[$id]]['f_last_pid'] = $data['f_last_pid'];
                    $tree[$parent[$id]]['f_last_tid'] = $data['f_last_tid'];
                }
                
                $id = $parent[$id]; 
            }
        }
        
        // -- Enfin, on met à jour les forums.
        $node = array(
                'f_last_tid' => null,
                'f_topics' => null,
                'f_replies' => null,
                'f_id' => null
            );
        
        $sql = 'UPDATE forums
                    SET f_last_tid = :last_tid, f_topics = :topics, f_replies = :replies
                    WHERE f_id = :fid;';
        
        $res = Obj::$db->prepare($sql);
        
        foreach ($tree as &$node) {
            $res->bindValue(':last_tid', $node['f_last_tid'], SQL::PARAM_INT);
            $res->bindValue(':topics', $node['f_topics'], SQL::PARAM_INT);
            $res->bindValue(':replies', $node['f_replies'], SQL::PARAM_INT);
            $res->bindValue(':fid', $node['f_id'], SQL::PARAM_INT);

            #REQ SYNC_2
            $res->execute();
        }
        
        $res = null;
    }
    
    /**
     * Synchronise les sujets
     * 
     * @static 
     */
    static public function sujets(){
        $sql = 'SELECT t_tid, 
                        p_pid, p_uid, p_date, 
                        (SELECT COUNT(*)
                            FROM forums_posts p3
                            WHERE p3.p_tid = t_tid) AS t_posts
                    FROM forums_posts p
                        LEFT JOIN forums_topics t ON t_tid = p_tid
                    WHERE p_pid = (SELECT MAX(p2.p_pid)
                                    FROM forums_posts p2
                                    WHERE p2.p_tid = t_tid);';
        
        #REQ SYNC_3
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        $data = array(
                'p_pid' => null,
                'p_date' => null,
                'p_uid' => null,
                't_posts' => null,
                't_tid' => null
            );
        
        $sql = 'UPDATE forums_topics 
                    SET t_last_pid = :pid, t_last_time = :date, t_last_uid = :uid, t_posts = :posts
                    WHERE t_tid = :tid;';
        

        $res = Obj::$db->prepare($sql);

        // -- On met à jour les sujets
        foreach ($datas as &$data){
            #REQ SYNC_4
            $res->bindValue(':pid', $data['p_pid'], SQL::PARAM_INT);
            $res->bindValue(':uid', $data['p_uid'], SQL::PARAM_INT);
            $res->bindValue(':posts', $data['t_posts'], SQL::PARAM_INT);
            $res->bindValue(':tid', $data['t_tid'], SQL::PARAM_INT);
            $res->bindValue(':date', $data['p_date'], SQL::PARAM_STR);
            $res->execute();
            //$res = null;
        }

        //exit;
        
        $res = null;
    }
    
    /**
     * Supprime les sujets sans forums, les messages sans sujets.
     * 
     * @return void
     * @access public
     * @static
     * 
     */
    static public function clean(){
        // -- On supprime les sujets sans forums
        $sql = 'DELETE FROM forums_topics
                    WHERE t_fid NOT IN (SELECT f_id FROM forums);';
        
        #REQ SYNC_5
        Obj::$db->exec($sql);
        
        // -- Puis, on s'occuppe des messages sans sujets (issus de la dernière suppression)
        $sql = 'DELETE FROM forums_posts
                    WHERE p_tid NOT IN (SELECT t_tid FROM forums_topics);';
        
        #REQ SYNC_6
        Obj::$db->exec($sql);
        
        $date = new Date('@' . (NOW - READ_MONTHS * ONE_MONTH), Obj::$date->getTimezone());
        
        /*
         * Enfin, on s'occupe de nettoyer un peu la table des sujets lus (sujets
         * périmés, sujets non existants, posts non existants)
         */
        $sql = 'DELETE FROM forums_read
                    WHERE fr_uid NOT IN (SELECT u_id FROM users)
                        OR fr_tid NOT IN (SELECT t_tid 
                                        FROM forums_topics
                                        WHERE :date < t_last_time)
                        OR fr_pid NOT IN (SELECT p_pid FROM forums_posts);';
        
        #REQ SYNC_7
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':date', $date->sql(), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        // -- Synchronisation des utilisateurs, des forums.
        self::forums();
        self::users();
    }
    
    /**
     * Met à jour le nombre de messages par membres.
     * 
     * @return void
     * @static
     * @todo A faire :p
     */
    static public function users(){
        $sql = 'SELECT ';
    }
}

/** EOF /**/
