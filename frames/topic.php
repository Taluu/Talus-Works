<?php
/**
 * Affichage d'un sujet
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
 * @copyright ©Talus, Talus' Works 2007, 2008
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 05/01/2008, Talus
 * @last 21/06/2009, Talus
 * @ignore
 */

final class Frame_Child extends Frame {	
    /**
     * Pagination
     * 
     * @var integer
     * @access protected
     */
    private $_page = 1;
    
    /**
     * Nbre total de pages
     * 
     * @var integer
     * @access protected
     */
    private $_total = 1;
    
    /**
     * ID du sujet
     *
     * @var integer
     */
    private $_id = 0;
    
    /**
     * Arbre des forums
     * 
     * @var array
     */
    private $_tree = array();
    
    /**
     * @ignore
     */
    protected function main(){
        if (isset($_GET['id'])) {
            $this->_id = intval($_GET['id']);
        } elseif (isset($_GET['pid'])) {
            $this->_id = intval($_GET['pid']);
        } 
        
        // -- Si elle n'existe pas, ou vaut 0, et pareil pour l'id du post, alors erreur.
        if (!$this->_id) {
            message('Aucun sujet selectionné !', '', 120, MESSAGE_ERROR);
            exit;
        }
        
        $this->_tree = RI::getTree();
        
        $this->setTpl('forums/topic.html');
        
        // -- Vérification du sujet...
        if (!$this->_fetch()) {
            message('Le sujet que vous avez selectionné est invalide.', '', 121, MESSAGE_ERROR);
            exit;
        }
        
        $this->_page();
        
        $this->addToNav(forums_nav(array(
                htmlspecialchars($this->data['t_title']) => (DOMAIN_REDIRECT . 'topic-' . $this->_id . '-p1-' . skip_chars($this->data['t_title']) . '.html'),
                'Lecture du Sujet' => false,
                ('Page ' . $this->_page) => false
            ), $this->data['t_fid']));
        
        // -- Titre de la page.
        $this->setTitle(htmlspecialchars($this->data['t_title']) . ' - Les Forums');
        
        // -- Jumpbox
        make_jumpbox($this->data['t_fid'], isset($_POST['jump_to']));
        
        // -- Les données...
        $this->_listPosts();
        
        // -- Les styles :)
        $this->addCSS('syntax_highlighter');
        $this->addCSS('forums');
        $this->addCSS('bbcode');
        
        // -- Les Flux RSS
        $this->addRSS('Derniers messages du forum &quot;' . $this->data['forum']['f_name'] . '&quot;', 'rss-forum-messages-' . $this->data['t_fid'] . '-' . skip_chars($this->data['forum']['f_name']) . '.xml');
        $this->addRSS('Derniers sujets du forum &quot;' . $this->data['forum']['f_name'] . '&quot;', 'rss-forum-topics-' . $this->data['t_fid'] . '-' . skip_chars($this->data['forum']['f_name']) . '.xml');  
        $this->addRSS('Derniers messages du sujet &quot;' . $this->data['t_title'] . '&quot;, du forum &quot;' . $this->data['forum']['f_name'] . '&quot;', 'rss-topic-messages-' . $this->_id . '-' . skip_chars($this->data['t_title']) . '.xml');
        
        // -- Le JS :)
        $this->addJs('ajax');
        $this->addJs('form');
        
        get_page_links($this->_page, $this->_total, ('topic-' . $this->_id . '-p%s-' . skip_chars($this->data['t_title']) . '.html'));
        
        return;
    }
    
    /**
     * Récupère les données des sujets.
     * 
     * @return boolean
     */
    private function _fetch(){
        $from = 'forums_topics t';
        $join = '';
        $where = 't_tid';
        
        // -- Si on recherche selon l'id du post....
        if (!isset($_GET['id'])){
            $from = 'forums_posts p';
            $join = '
                            LEFT OUTER JOIN forums_topics t ON p.p_tid = t.t_tid';
            $where = 'p.p_pid';
        }
        
        // -- Selection des données du forum.
        $sql = 'SELECT  t_tid, t_fid, t_closed, t_posts, t_solved, t_title, t_description, t_last_pid, t_last_time, t_first_uid, t_postit,
                        pf.p_content AS first_content,
                        COALESCE(fr_posted, 0) as fr_posted
                    FROM ' . $from . $join . '
                        LEFT OUTER JOIN forums_posts pf ON t.t_first_pid = pf.p_pid
                        LEFT OUTER JOIN forums_read r ON t.t_tid = r.fr_tid
                            AND r.fr_uid = :uid
                    WHERE ' . $where . ' = :id;';
        
        #REQ TOP_1
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $this->data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Aucunes données retournées...
        if (!$this->data) {
            return false;
        }
        
        // -- Pas d'accès !
        if (!isset($this->_tree[$this->data['t_fid']])){
            message('Vous n\'êtes pas autorisé à consulter les sujets de ce forum !', '', 112, MESSAGE_ERROR);
            exit;
        }
        
        $this->_id = $this->data['t_tid'];
        $this->data['forum'] = $this->_tree[$this->data['t_fid']];
        
        // -- Nombre total de pages.
        $this->_total = ceil($this->data['t_posts'] / POSTS_PER_PAGE);
        
        // -- Qqs variables switchs
        $vars = array(
                'TOPIC_ID' => $this->_id,
                'TOPIC_TITLE' => htmlspecialchars($this->data['t_title']),
                'TOPIC_DESCRIPTION' => $this->data['t_description'] ? htmlspecialchars($this->data['t_description']) : '',
                'TOPIC_OPENED' => ($this->data['forum']['f_answer'] >= Sys::$u_level && !$this->data['t_closed']) || ($this->data['forum']['f_modo'] >= Sys::$u_level),
                'TOPIC_CLOSED' => (bool) $this->data['t_closed'],
                'TOPIC_SOLVED' => (bool) $this->data['t_solved'],
                'TOPIC_POSTIT' => (bool) $this->data['t_postit'],
                'FORUM_ID' => $this->data['t_fid'],
                'FORUM_OPENED' => $this->data['forum']['f_new'] >= Sys::$u_level,
                'IS_FORUM_MODO' => $this->data['forum']['f_modo'] >= Sys::$u_level,
                'IS_AUTHOR' => $this->data['t_first_uid'] == Sys::$uid,
                'USE_PAGINATION' => $this->_total > 1
            );
        
        Obj::$tpl->set($vars);
        
        // -- Mises à jour du nbre de vues.
        $sql = 'UPDATE forums_topics
                    SET t_views = (t_views + 1)
                    WHERE t_tid = :id;';
       
        #REQ TOP_2
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        // -- On met à jour la table des vues.... Que si il est connecté (ca sert à rien sinon ^o^)
        if (Sys::$uid != GUEST) {
            $sql = 'REPLACE INTO forums_read (fr_uid, fr_tid, fr_pid, fr_posted)	
                        VALUES (:uid, :id, :pid, :posted);';
            
            #REQ TOP_3
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
            $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
            $res->bindValue(':pid', $this->data['t_last_pid'], SQL::PARAM_INT);
            $res->bindValue(':posted', (bool)$this->data['fr_posted'], SQL::PARAM_BOOL);
            $res->execute();
            $res = null;
        }
        
        // -- On met dans la meta description le contenu tronqué du premier post
        $this->setDesc(cut_str(void_bbcode($this->data['first_content']), META_DESCRIPTION_STRLEN));
        
        // -- Tout est passé ; on retourne "true".
        return true;
    }
    
    /**
     * Génère la liste des messages.
     * 
     * @return	void
     */
    protected function _listPosts(){
        // -- On ne génère la pagination "que" si elle est necessaire (plus d'une page).
        if ($this->_total > 1){
            pagination($this->_page, $this->_total, ('topic-' . $this->_id . '-p%s-' . skip_chars($this->data['t_title']) . '.html'), null, PAGINATION_ALL);
        }
        
        // -- Es t-on à plus de la première page ? Si oui, on active la possibilité de consulter le message précédent.
        $offset = (int) ($this->_page > 1);
        
        // -- Les niveaux.
        $levels = array(
                array('Administrateur', 'grp_admin'), // -- Admins
                array('Modérateur', 'grp_modo'), // -- Modos
                array('Membre', 'grp_user'), // -- Membres
                array('Lecture Seule', 'grp_ls'), // -- LS
                array('Visiteur', 'grp_guest'), // -- Visiteurs
                array('Banni', 'grp_banned') // -- Bannis
            );
        
        // -- Numéro de Post, indication si c'est le premier post.
        $i = $offset ^ 1;
        
        // -- Cache des donées des utilisateurs.
        $cache = array();
        
        $sql = 'SELECT p_pid, p_uid, p_date, p_edit_uid, p_edit_times, p_edit_date, p_content, p_uip,
                        u.u_login as u_login, u.u_level as u_level, u.u_posts as u_posts, u.u_email as u_email, u.u_signature as u_signature, u.u_quote as u_quote,
                        ue.u_login as edit_login, ue.u_level as edit_level,
                        s.s_date as session_date
                    FROM forums_posts p
                        LEFT OUTER JOIN users u ON p.p_uid = u.u_id
                        LEFT OUTER JOIN users ue ON p.p_edit_uid = ue.u_id
                        LEFT OUTER JOIN sessions s ON p.p_uid = s.s_uid
                    WHERE p_tid = :id
                    GROUP BY p_pid
                    ORDER BY p_pid ASC
                    LIMIT :limit OFFSET :offset;';
        
        #REQ TOP_4
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':limit', POSTS_PER_PAGE + $offset, SQL::PARAM_INT);
        $res->bindValue(':offset', (($this->_page - 1) * POSTS_PER_PAGE) - $offset, SQL::PARAM_INT);
        $res->execute();
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        foreach ($datas as $data) {
            $vars = array(
                    // -- Qqs Vars conditions
                    'IS_PREVIOUS' => (bool)$offset,
                    'IS_AUTHOR' => $data['p_uid'] == Sys::$uid,
                    
                    // -- Varis du post
                    'ID' => $data['p_pid'],
                    'ID_POST' => (($this->_page - 1) * POSTS_PER_PAGE) + $i++,
                    'CONTENT' => bbcode($data['p_content']),
                    'DATE' => parse_date($data['p_date']),
                    
                    // -- Variables pour l'edition
                    'EDIT_TIMES' => $data['p_edit_times'],
                    'EDIT_DATE' => strtolower(parse_date($data['p_edit_date'])),
                    'EDIT_USER' => $data['edit_login'] ? ('<a href="profile-' . $data['p_edit_uid'] . '-' . skip_chars($data['edit_login']) . '.html" class="' . $levels[$data['edit_level']][1] . '">' . htmlspecialchars($data['edit_login']) . '</a>') : '<em>Anonyme</em>',
                );
            
            if (!isset($cache[$data['p_uid']])) {
                if (!$data['p_uid']) {
                    $data['u_level'] = GRP_GUEST;
                }
                
                $cache[$data['p_uid']] = array(	
                        'U_AVATAR' => $data['u_email'] ? md5(strtolower($data['u_email'])) : 'ad516503a11cd5ca435acc9bb6523536',
                        'U_NAME' => $data['u_login'] ? ('<a href="profile-' . $data['p_uid'] . '-' . skip_chars($data['u_login']) . '.html" class="' . $levels[$data['u_level']][1] . '">' . htmlspecialchars($data['u_login']) . '</a>') : '<em>Anonyme</em>',
                        'U_QUOTE' => htmlspecialchars($data['u_quote']),
                        'U_IP' => Sys::$u_level <= 1 ? long2ip($data['p_uip']) : null,
                        'U_NBR_MESSAGES' => (Sys::$u_level <= 1 && $data['p_pid']) ? $data['u_posts'] : null,
                        'U_LOGGED_STATUS' => (((int) $data['session_date'] + TIME_CONNECTED) >= Obj::$date->unix()) ? 'on' : 'off',
                        'U_STATUS' => (((int) $data['session_date'] + TIME_CONNECTED) >= Obj::$date->unix()) ? 'En Ligne' : 'Hors-Ligne',
                        'U_SIGNATURE' => bbcode($data['u_signature']),
                        'U_GRP' => $levels[$data['u_level']][0],
                        'U_CLASS' => $levels[$data['u_level']][1],
                        'U_LVL' => $data['u_level']
                    );
            }
            
            $vars += $cache[$data['p_uid']];
            
            Obj::$tpl->setBlock('posts', $vars);
        }
    }
    
    /**
     * Récupère la bonne page.
     * 
     * @return boolean
     */
    protected function _page(){
        // -- On récupère les variables de pages... 
        $this->_page = isset($_GET['p']) ? abs(intval($_GET['p'])) : 1;
        
        // -- Si la page est nulle, ou alors torp grande, on la fixe à 1.
        if (!$this->_page || $this->_page > $this->_total) {
            $this->_page = 1;
        }
        
        // -- Si la variable 'pid' est pas définie, et que la page est selectionnée, alors oui, on retourne notre résultat (total etc) :)
        if (!isset($_GET['pid']) || !intval($_GET['pid'])) {
            return;
        }
        
        // -- Requête SQL pour récupérer le nbre de posts avant l'id selectionné.
        $sql = 'SELECT COUNT(*)
                    FROM forums_posts
                    WHERE p_tid = :id
                        AND p_pid <= :pid
                    ORDER BY p_pid ASC;';
        
        #REQ TOP_5
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':pid', intval($_GET['pid']), SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_NUM);
        $res = null;
        
        // -- Pas de données retrouvées ; On retourne au cas "habituel".
        if (!$data) {
            return;
        }
        
        // -- Sinon, on peut procéder au "filtre" (si on trouve qu'il y a pas mal de posts.)
        if ($data[0] > 0) {
            $this->_page = ceil($data[0] / POSTS_PER_PAGE);
        }
        
        return true;
    }
}

/** EOF /**/