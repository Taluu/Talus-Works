<?php
/**
 * Frame qui gère les flux RSS.
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
 * @author Talus <talusch@gmail.com>
 * @copyright ©Talus, Talus' Works 2008, 2009
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 19/07/2008, Talus
 * @last 19/07/2009, Talus
 */

final class Frame_Child extends Frame {
    /**
     * Contenant des items RSS possibles
     *
     * @var array
     */
    private $_possibilities = array(
            'forum' => array(false, array('messages', 'topics')), 
            'topic' => array(true, array('messages'))
        );
        
    /**
     * Type visé (Forum ? Sujet ?)
     *
     * @var string
     */
    private $_container = 'forum';
        
    /**
     * Sur quelles types de données ? (Messages ? Sujets ?)
     *
     * @var string
     */
    private $_type = 'topics';
    
    /**
     * ID Visé.
     *
     * @var integer
     */
    private $_id = 0;
    
    /**
     * Décalage horaire
     *
     * @var array
     */
    private $_decal = array();
    
    /**
     * @ignore 
     */
    protected function main(){
        $this->_container = isset($_GET['container']) ? $_GET['container'] : 'forum';
        $this->_type = isset($_GET['type']) ? $_GET['type'] : 'topics';
        $this->_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // -- Si on choisit un contenant pas valide...
        if (!isset($this->_possibilities[$this->_container])) {
            message('Le contenant choisi n\'est pas valide', 'rss.xml', 80, MESSAGE_ERROR);
            exit;
        }
        
        // -- Si il faut un ID pour consulter les RSS, et que celui-ci n'est pas fourni...
        if ($this->_possibilities[$this->_container][RSS_ID_NEEDED] && !$this->_id) { 
            message('L\'ID choisi n\'est pas valide', 'rss.xml', 81, MESSAGE_ERROR);
            exit;
        }
        
        // -- Si le type choisit n'existe pas...
        if (!in_array($this->_type, $this->_possibilities[$this->_container][RSS_TYPES])) {
            message('Le type choisi n\'est pas valide', 'rss.xml', 82, MESSAGE_ERROR);
            exit;
        }
        
        $types = array(
                'topics' => 'sujets',
                'messages' => 'messages'
            );
        
        // Calcul horaire
        $this->_decal = float_to_time(Sys::$dst + Sys::$utc);
        
        // -- Récupération de données "globales" (nom du sujet, des forums, ...) si id != 0
        if ($this->_id) {
            call_user_func(array($this, '_fetch' . ucfirst(strtolower($this->_container))));	
        } else {
            Obj::$tpl->set(array(
                    'RSS_TITLE' => 'Flux RSS • Talus\' Works',
                    'RSS_DESCRIPTION' => 'Retrouvez les derniers ' . $types[$this->_type] . ' de Talus\' Works',
                    'RSS_PUBDATE' => parse_date(Obj::$date->unix(), 'D, d M Y H:i:s') . ' ' . $this->_decal['sign'] . $this->_decal['h'] . $this->_decal['m']
                ));
        }
        
        // -- Lancement de la méthode
        call_user_func(array($this, '_' . strtolower($this->_container) . ucfirst(strtolower($this->_type))));
        
        // -- Indication de Headers supplémentaires
        header("Content-Type: application/rss+xml");
        
        // -- Indication du TPL
        $this->setTpl('home/rss.html');
        
        return;
    }
    
    /**
     * Gère les flux RSS des messages d'un (des) forum(s)
     *
     * @return void
     */
    private function _forumMessages(){
        // -- Ids des forums
        $idx = array();
        $txtnode = array();
        
        /*
         * Si c'st pour un forum en particulier, on recupere les id de sa
         * descendance. Sinon, on récupère tous les forums, et en avant marche :)
         */
        if ($this->_id) {     
            $tree = RI::getChildren($this->_id);
            
            foreach ($tree as &$node) {
            	$idx[] = $node['f_id'];
            	
            	/* pour plus tard ? - Si j'ai pas la flemme de m'atteler à ce truc chiant à faire si pas de forums selectionnés...
                if ($this->data['f_id'] != $node['f_id']){
                    $txtnode[$node['f_id']] = $this->data['f_name'] . ' > ' . ($node['f_parent'] == $this->data['f_id'] ? '' : '... > ') . $node['f_name'];
            	} else {
            	    $txtnode[$node['f_id']] = $this->data['f_name'];
            	}/**/
            	$txtnode[$node['f_id']] = $node['f_name'];
            }
        } else {
            Sys::$cache['tree'] = RI::getTree();
            
            foreach (Sys::$cache['tree'] as &$node) {
            	$idx[] = $node['f_id'];
            	
            	/* cf ligne 131/**/
            	$txtnode[$node['f_id']] = $node['f_name'];
            }
        }
        
        $sql = 'SELECT p_pid, p_content, p_date, p_uid,
                        u_login,
                        t_title, t_fid
                    FROM forums_topics t
                        LEFT JOIN forums_posts p ON t.t_tid = p.p_tid
                        LEFT JOIN users u ON p.p_uid = u.u_id
                    WHERE t_fid IN (' . implode(', ', $idx) . ')
                    ORDER BY p_date DESC
                    LIMIT 10;';
        
        #REQ RSS_4
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        foreach ($datas as $data){
            Obj::$tpl->setBlock('items', array(
                    'TITLE' => $data['t_title'] . ' - ' . $txtnode[$data['t_fid']] . ' • Talus\' Works',
                    'SHORT' => bbcode($data['p_content']),
                    
                    'LINK' => DOMAIN_REDIRECT . '/topic-post-' . $data['p_pid'] . '-' . skip_chars($data['t_title']) . '.html#p' . $data['p_pid'],
                    
                    'AUTHOR' => $data['u_login'] ? $data['u_login'] : 'Anonyme',
                    'PUBDATE' => parse_date($data['p_date'], 'D, d M Y H:i:s') . ' ' . $this->_decal['sign'] . $this->_decal['h'] . $this->_decal['m']
                ));
        }
        
    }
    
    /**
     * Gère les flus RSS des sujets d'un (des) forum(s)
     *
     * @return void
     */
    private function _forumTopics(){
        // -- Ids des forums
        $idx = array();
        $txtnode = array();
        
        // -- Si c'st pour un forum en particulier, on recupere les id de sa descendance. Sinon, on récupère tous les forums, et en avant marche :)
        if ($this->_id) {     
            $tree = RI::getChildren($this->_id);
            
            foreach ($tree as &$node) {
            	$idx[] = $node['f_id'];
            	
            	/* cf ligne 131/**/
            	$txtnode[$node['f_id']] = $node['f_name'];
            }
        } else {
            Sys::$cache['tree'] = RI::getTree();
            
            foreach (Sys::$cache['tree'] as &$node) {
            	$idx[] = $node['f_id'];
            	
            	/* cf ligne 131/**/
            	$txtnode[$node['f_id']] = $node['f_name'];
            }
        }
        
        $sql = 'SELECT t_tid, t_description, t_first_time, t_fid, t_title,
                        u_login
                    FROM forums_topics t
                        LEFT JOIN users u ON t.t_first_uid = u.u_id
                    WHERE t_fid IN (' . implode(', ', $idx) . ')
                    ORDER BY t_first_time DESC
                    LIMIT 10;';
        
        #REQ RSS_5
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        foreach ($datas as $data){
            Obj::$tpl->setBlock('items', array(
                    'TITLE' => $data['t_title'] . ' - ' . $txtnode[$data['t_fid']] . ' • Talus\' Works',
                    'SHORT' => bbcode($data['t_description']),
                    
                    'LINK' => DOMAIN_REDIRECT . '/topic-' . $data['t_tid'] . '-p1-' . skip_chars($data['t_title']) . '.html',
                    
                    'AUTHOR' => $data['u_login'] ? $data['u_login'] : 'Anonyme',
                    'PUBDATE' => parse_date($data['t_first_time'], 'D, d M Y H:i:s') . ' ' . $this->_decal['sign'] . $this->_decal['h'] . $this->_decal['m']
                ));
        }
        
    }
    
    /**
     * Gère les flus RSS des messages d'un sujet
     *
     * @return void
     */
    private function _topicMessages(){
        $sql = 'SELECT p_pid, p_content, p_date, p_uid,
                        u.u_login
                    FROM forums_posts p
                        LEFT JOIN users u ON p.p_uid = u.u_id
                    WHERE p_tid = ' . $this->_id . '
                    ORDER BY p_date DESC
                    LIMIT 10;';
        
        #REQ RSS_2
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        $tree = RI::getTree();
        
        foreach ($datas as $data){
            Obj::$tpl->setBlock('items', array(
                    'TITLE' => $this->data['t_title'] . ' - ' . $tree[$this->data['t_fid']]['f_name'] . ' • Talus\' Works',
                    'SHORT' => bbcode($data['p_content']),
                    
                    'LINK' => DOMAIN_REDIRECT . '/topic-post-' . $data['p_pid'] . '-' . skip_chars($this->data['t_title']) . '.html#p' . $data['p_pid'],
                    
                    'AUTHOR' => $data['u_login'] ? $data['u_login'] : 'Anonyme',
                    'PUBDATE' => parse_date($data['p_date'], 'D, d M Y H:i:s') . ' ' . $this->_decal['sign'] . $this->_decal['h'] . $this->_decal['m']
                ));
        }
    }
    
    /**
     * Récupère des informations sur un forum
     *
     * @return void
     */
    private function _fetchForum(){
        $tree = RI::getTree();
        
        if (!isset($tree[$this->_id])){
            message('Le forum choisi n\'existe pas, ou plus, ou ne vous est pas accessible', 'rss.xml', 83, MESSAGE_ERROR);
            exit;
        }
        
        $sql = 'SELECT t_last_time
                    FROM forums_topics t
                    WHERE t_tid = :id;';
        
        #REQ RSS_3
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $tree[$this->_id]['f_last_tid'], SQL::PARAM_INT);
        $res->execute();
        $this->data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        Obj::$tpl->set(array(
                'RSS_TITLE' => $tree[$this->_id]['f_name'] . ' • Talus\' Works',
                'RSS_DESCRIPTION' => $tree[$this->_id]['f_description'],
                'RSS_PUBDATE' => parse_date($this->data['t_last_time'], 'D, d M Y H:i:s') . ' ' . $this->_decal['sign'] . $this->_decal['h'] . $this->_decal['m']
            ));
    }
    
    /**
     * Récupère des informations sur un sujet
     *
     * @return void
     */
    private function _fetchTopic(){
        $sql = 'SELECT t_last_time, t_description, t_title, t_fid
                    FROM forums_topics t
                    WHERE t_tid = :id;';
        
        #REQ RSS_1
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $this->data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        $tree = RI::getTree();
        
        if (!$this->data || !isset($tree[$this->data['t_fid']])){
            message('Le sujet choisi n\'existe pas, ou plus, ou ne vous est pas accessible', 'rss.xml', 83, MESSAGE_ERROR);
            exit;
        }
        
        Obj::$tpl->set(array(
                'RSS_TITLE' => $this->data['t_title'] . ' - ' . $tree[$this->data['t_fid']]['f_name'] . ' • Talus\' Works',
                'RSS_DESCRIPTION' => $this->data['t_description'],
                'RSS_PUBDATE' => parse_date($this->data['t_last_time'], 'D, d M Y H:i:s') . ' ' . $this->_decal['sign'] . $this->_decal['h'] . $this->_decal['m']
            ));
    }
}

/**
 * EOF
 */