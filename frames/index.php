<?php
/**
 * Affichage de l'index de Talus' Works : Les forums !
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
 * @copyright ©Talus, Talus' Works 2007, 2009
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 31/12/2007, Talus
 * @last 17/07/2009, Talus
 * @ignore
 */

final class Frame_Child extends Frame {	
    /**
     * Mode utilise (mettre tous les messages commes lus, etc)
     * 
     * @var string
     */
    protected $type = 'index';
    
    /**
     * ID utilise
     * 
     * @var integer
     */
    private $_id = 0;
    
    /**
    * Affiche les forums qu'il faut.
    * 
    * @return void
    */
    protected function main(){
        // -- On récupère l'id du forum exploré.
        $this->_id = isset($_GET['id']) ? $_GET['id'] : 0;
        
        if (isset($_GET['markread'])) {
            markread($this->_id);
        }
        
        $this->setTpl('forums/cat.html');
        
        $this->_listForums();
        
        $vars = array(
                'TITRE' => $this->_id ? ('<h1>' . htmlspecialchars($this->data[$this->_id]['f_name']) . '</h1>') : '',
                'DESCRIPTION' => $this->_id && $this->data[$this->_id]['f_description'] ?  htmlspecialchars($this->data[$this->_id]['f_description']) : '',
            );
        
        $nav = $this->_id ? array('Liste des Forums de cette catégorie' => false) : array();
        
        // -- Si un ID est selectionne, alors on affiche les flus RSS correspondants
        if ($this->_id){
            $this->addRSS('Derniers messages du forum &quot;' . $this->data[$this->_id]['f_name'] . '&quot;', 'rss-forum-messages-' . $this->_id . '-' . skip_chars($this->data[$this->_id]['f_name']) . '.xml');
            $this->addRSS('Derniers sujets du forum &quot;' . $this->data[$this->_id]['f_name'] . '&quot;', 'rss-forum-topics-' . $this->_id . '-' . skip_chars($this->data[$this->_id]['f_name']) . '.xml');
        
            if ($this->data[$this->_id]['f_description']) {
            	$this->setDesc($this->data[$this->_id]['f_description']);
            }
        }
        
        $this->addCSS('forums', '', true);
        
        Obj::$tpl->set($vars);
        
        $this->addToNav(forums_nav($nav, $this->_id));
        return;
    }
    
    /**
     * Affiche la liste des forums.
     * 
     * @return	void
     */
    private function _listForums(){
        // - On récupère le nbre de sujets lus / non lus.
        get_read_topics();
        
        // -- Quelques variables pour la requête SQL qui va suivre... :)
        $join = '';
        $where = '';
        $alias = 'f';
        $level = 0;
        
        if ($this->_id != 0){
            $join =	 '
                            LEFT OUTER JOIN forums f ON parent.f_left <= f.f_left
                                AND parent.f_right >= f.f_right
                                AND (parent.f_level + 2) >= f.f_level';
            
            $where = 'parent.f_id = ' . Obj::$db->quote($this->_id) . ' AND ';
            $level = 'parent.f_level';
            $alias = 'parent';
        }
        
        // -- Récupération des forums.
        $sql = 'SELECT 
                        f.f_id, f.f_level, f.f_name, f.f_type, f.f_replies, f.f_topics, f.f_description, f.f_parent,' . $level . ' AS lvl,
                        t_title, t_last_pid, t_last_time, t_last_uid, t_tid,
                        u_login, u_id
                    FROM forums ' . $alias . $join . '
                        LEFT OUTER JOIN forums_topics t ON f.f_last_tid = t.t_tid
                        LEFT OUTER JOIN users u ON t.t_last_uid = u.u_id
                    WHERE ' . $where . $alias . '.f_level <= 2 
                        AND f.f_read >= :ulevel
                    ORDER BY f.f_left;';
        
        #REQ CAT_2
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':ulevel', Sys::$u_level, SQL::PARAM_INT);
        $res->execute();
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        foreach ($datas as &$data) {
            $this->data[$data['f_id']] = $data;
            $this->data[$data['f_id']]['is_read'] = !(isset(Sys::$cache['unread'][$data['f_id']]) && Sys::$cache['unread'][$data['f_id']]);
            $block = 'cat';
            
            // -- C'est une catégorie... 
            if ($data['f_type'] == CATEGORY){
                $vars = array(
                        'NAME' => htmlspecialchars($data['f_name']),
                        'URL' => 'cat-' . $data['f_id'] . '-' . skip_chars($data['f_name']) . '.html',
                        'POSTS' => nombres($data['f_replies'], 0),
                        'TOPICS' => nombres($data['f_topics'], 0),
                        'IS_READ' => $this->data[$data['f_id']]['is_read'],
                        'ID' => $data['f_id']
                    );
            } elseif (($data['f_level'] - $data['lvl']) ==  1){ // C'est un forum
                $last_topic = 'Dans <a href="topic-' . $data['t_tid'] . '-p1-' . skip_chars($data['t_title']) . '.html">' . htmlspecialchars($data['t_title']) . '</a><br />';
                $last_user = 'Par ' . ($data['u_id'] ? '<a href="profile-' . $data['u_id'] . '-' . skip_chars($data['u_login']) . '.html">' . htmlspecialchars($data['u_login']) . '</a>' : '<em>Anonyme</em>');
                
                $last_date = '<a href="topic-post-' . $data['t_last_pid'] . '.html#p' . $data['t_last_pid'] . '" title="Aller au dernier message de ce sujet">' . parse_date($data['t_last_time']) . '</a><br />';
                
                if (!$data['t_tid']) {
                    $last_topic = '';
                    $last_date = '----------';
                    $last_user = '';
                }
                
                $vars = array(
                        'IS_READ' => $this->data[$data['f_id']]['is_read'],
                        'ICON_PREFIX' => !$this->data[$data['f_id']]['is_read'] ? 'un' : '',
                        'NAME' => htmlspecialchars($data['f_name']),
                        'DESCRIPTION' => htmlspecialchars($data['f_description']),
                        'POSTS' => nombres($data['f_replies'], 0),
                        'TOPICS' => nombres($data['f_topics'], 0),
                        'URL' => 'forum-' . $data['f_id'] . '-p1-' . skip_chars($data['f_name']) . '.html',
                        'LAST_TOPIC' => $last_topic,
                        //'LAST_POST' => $last_post,
                        'LAST_DATE' => $last_date,
                        'LAST_USER' => $last_user,
                        'ID' => $data['f_id']
                    );
                
                $block .= '.forums';
            } else { // C'est un sous-forum
                $vars = array(
                        'URL' => 'forum-' . $data['f_id'] . '-p1-' . skip_chars($data['f_name']) . '.html',
                        'NAME' => htmlspecialchars($data['f_name'])
                    );
                
                $block .= '.forums.subs';
            }
            
            // -- On assigne les variables au bloc :3
            Obj::$tpl->setBlock($block, $vars);
        }
    }
}

/** EOF /**/
