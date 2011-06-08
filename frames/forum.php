<?php
/**
 * Affichage d'un forum de Talus' Works (sous forums, sujets)
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
 * @copyright ©Talus, Talus' Works 2007+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 02/01/2008, Talus
 * @last 21/06/2009, Talus
 */

/**
 * Listings des sous forums et des sujets d'un forum
 *
 * @ignore
 */
final class Frame_Child extends Frame {
    /**
     * Pagination
     * 
     * @var integer
     */
    private $_page = 1;
    
    /**
     * Nbre total de pages
     * 
     * @var integer
     */
    private $_total = 1;
    
    /**
     * ID du forum
     *
     * @var integer
     */
    private $_id = 0;
    
    /**
     * Arbre de tous les forums
     * 
     * @var array
     */
    private $_tree = array();
    
    /**
     * @ignore
     */
    protected function main(){
        // -- On renseigne l'id du forum actuel.
        $this->_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // -- Pas d'id.. Redirection vers l'index !
        if (!$this->_id) {
            header('Location: index.html');
            exit;
        }
        
        // -- Vérification des non lus ?
        if (isset($_GET['markread'])) {
            markread($this->_id);
        }
        
        // -- Renseignement du TPL à utiliser.
        $this->setTpl('forums/forum.html');
        
        // -- Vérification que le dit forum existe...
        if (!($vars = $this->_fetch())) {
            message('Il y a eu un problème lors de la selection des données de ce forum.', '', 111, MESSAGE_ERROR);
            exit;
        }
        
        // -- On renseigne le numéro de page si il existe, puis le total de pages.
        $this->_page = isset($_GET['p']) ? abs(intval($_GET['p'])) : 1;
        $this->_total = ceil($this->data['f_topics'] / TOPICS_PER_PAGE);
        
        // -- Vérif de la pagination.
        if (!$this->_page || $this->_page > $this->_total){
            $this->_page = 1;
        }
        
        // -- Rensignement de la navigation, et également du titre de la page.
        $this->addToNav(forums_nav(array(
                'Liste des sujets' => false,
                'Page ' . $this->_page => false
            ), $this->_id));
        
        $vars += array('USE_PAGINATION' => $this->_total > 1);
        
        // -- Si il a des enfants, on les récupère.
        if ($vars['SUB_FORUMS']) {
            $this->_listSubForums();
        }
        
        // -- Il y a une description ? On l'ajoute dans la meta
        if ($this->data['f_description']){
            $this->setDesc(htmlspecialchars($this->data['f_description']));
        }
        
        // -- On affiche les sujets que si on est dans un forum !
        if ($this->data['f_type'] == FORUM) {
            $this->_listTopics();
        }
        
        // -- Les styles :)
        $this->addCSS('forums', '', true);
        
        // -- Assignement des variables.
        Obj::$tpl->set($vars);
        
        // -- Ajout des flux RSS
        $this->addRSS('Derniers messages du forum &quot;' . $this->data['f_name'] . '&quot;', 'rss-forum-messages-' . $this->_id . '-' . skip_chars($this->data['f_name']) . '.xml');
        $this->addRSS('Derniers sujets du forum &quot;' . $this->data['f_name'] . '&quot;', 'rss-forum-topics-' . $this->_id . '-' . skip_chars($this->data['f_name']) . '.xml');
        
        get_page_links($this->_page, $this->_total, ('forum-' . $this->_id . '-p%s-' . skip_chars($this->data['f_name']) . '.html'));
    }
    
    /**
     * Récupère les données du forum.
     * 
     * @return array
     * @access protected
     */
    private function _fetch(){
        // -- Selection des données du forum.
        $this->_tree = RI::getTree();
        
        // -- Aucunes données retournées...
        if (!$this->_tree[$this->_id]) {
            return false;
        }
        
        $this->data += $this->_tree[$this->_id];
        
        // -- C'est une catégorie. On agit redirige vers les catégories (on évite les dupplicatas d'url)
        if ($this->data['f_type'] == CATEGORY) {
            header('Location: ' . DOMAIN_REDIRECT . '/cat-' . $this->_id . '.html', true, 301);
            exit;
        }
        
        // -- On vérifie que le type a les droits dessus...
        if ($this->data['f_read'] < Sys::$u_level) {
            message('Vous n\'êtes pas autorisé à consulter ce forum !', '', 112, MESSAGE_ERROR);
            exit;
        }
        
        $vars = array(
                'FORUM_ID' => $this->_id,
                'FORUM_TITLE' => htmlspecialchars($this->data['f_name']),
                'FORUM_DESCRIPTION' => $this->data['f_description'] ? htmlspecialchars($this->data['f_description']) : '',
                'FORUM_OPENED' => $this->data['f_new'] >= Sys::$u_level,
                
                'SUB_FORUMS' => ($this->data['f_right'] - $this->data['f_left'] > 1),
                'TOPICS' => $this->data['f_type'] == FORUM  && $this->data['f_topics'],
                'SHOW_TOPICS' => $this->data['f_type'] == FORUM ,
                
                'URL_MARKREAD' => 'markread-' . $this->_id . '.html'
            );
        
        // -- Tout est passé ; on retourne "$vars".
        return $vars;
    }

    /**
     * Affiche les sous forums de ce forum.
     * @return void
     * @access protected
     */
    private function _listSubForums(){
        // -- On récupère le nbre de forums lus / non lus.
        get_read_topics();
		
        // -- Les sujets à sélectionner
        $list = array(
                'topics' => array(),
                'forums' => array()
            );
		
		// -- Sélection des forums à chercher
		foreach ($this->_tree as &$node){
            // -- Histoire de ne pas se tapper tout l'arbre...
            if ($node['f_left'] <= $this->data['f_left']){
                continue;
            } 
		    
            // -- Si c'est un forum enfant "accessible", on le sélectionne.
		    if ($node['f_left'] > $this->data['f_left']
              && $node['f_right'] < $this->data['f_right']
              && ($node['f_level'] >= ($this->data['f_level'] + 1) 
                  || $node['f_level'] <= ($this->data['f_level'] + 2))){
                        
                $list['topics'][] = $node['f_last_tid'];
                $list['forums'][] = $node['f_id'];
                continue;
            }
            
            /*
             * Pour économiser des ressources, si on a dépassé les enfants
             * possibles, on arrête la boucle ici... 
             * 
             * Ok, certes, finir la boucle, c'est comme un pet de mouche dans
             * l'océan (surtout si y'a peu de forums...), mais c'est toujours un
             * gain appréciable :]
             */
            if ($node['f_right'] >= $this->data['f_right']){
                break;
            }
		}
        
        $sql = 'SELECT  t_title, t_last_pid, t_last_time, t_last_uid, t_tid, t_fid,
                        u_login, u_id
                    FROM forums_topics t
                        LEFT OUTER JOIN users u ON t.t_last_uid = u.u_id
                    WHERE t.t_tid IN (' . implode(', ', $list['topics']) . ');';
        
        #REQ FOR_2
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        $topics = array();
        
        // -- Copie des données des sujets
        foreach ($datas as &$data){
            $topics[$data['t_fid']] = $data;
        }
        
        // -- Au lieu de parcourir les sujets trouvés... Parcourons les forums !
        foreach ($list['forums'] as &$fid) {
            // -- Quelques variables...
            $data['is_read'] = !isset(Sys::$cache['unread'][$fid]) || !Sys::$cache['unread'][$fid];
            $block = 'forums';
            
            // -- On vérifie si c'est un forum ou un sous forum.
            if (($this->_tree[$fid]['f_level'] - $this->data['f_level']) == 1) {
                
                $last_topic = $last_user = '';
                $last_date = '----------';
                
                if (isset($topics[$fid])){
                    $last_topic = 'Dans <a href="topic-' . $topics[$fid]['t_tid'] . '-p1-' . skip_chars($topics[$fid]['t_title']) . '.html">' . htmlspecialchars($topics[$fid]['t_title']) . '</a><br />';
                    $last_user = 'Par ' . ($topics[$fid]['u_id'] ? '<a href="profile-' . $topics[$fid]['u_id'] . '-' . skip_chars($topics[$fid]['u_login']) . '.html">' . htmlspecialchars($topics[$fid]['u_login']) . '</a>' : '<em>Anonyme</em>');
                    $last_date = '<a href="topic-' . $topics[$fid]['t_tid'] . '-' . $topics[$fid]['t_last_pid'] . '.html#p' . $topics[$fid]['t_last_pid'] . '" title="Aller au dernier message de ce sujet">' . parse_date($topics[$fid]['t_last_time']) . '</a><br />';
                }
                
                $vars = array(
                        'IS_READ' => $data['is_read'],
                        'ICON_PREFIX' => !$data['is_read'] ? 'un' : '',
                        'NAME' => htmlspecialchars($this->_tree[$fid]['f_name']),
                        'DESCRIPTION' => htmlspecialchars($this->_tree[$fid]['f_description']),
                        'POSTS' => nombres($this->_tree[$fid]['f_replies'], 0),
                        'TOPICS' => nombres($this->_tree[$fid]['f_topics'], 0),
                        'URL' => 'forum-' . $fid . '-p1-' . skip_chars($this->_tree[$fid]['f_name']) . '.html',
                        'LAST_TOPIC' => $last_topic,
                        //'LAST_POST' => $last_post,
                        'LAST_DATE' => $last_date,
                        'LAST_USER' => $last_user,
                        'ID' => $fid
                    );
            } else {
                $vars = array(
                        'URL' => 'forum-' . $fid . '-p1-' . skip_chars($this->_tree[$fid]['f_name']) . '.html',
                        'NAME' => $this->_tree[$fid]['f_name']
                    );
                
                $block .= '.subs';
            }
            
            // -- Hop, on déclare le tout :)
            Obj::$tpl->setBlock($block, $vars);
        }
        
        return;
    }

    /**
     * Affiche les sujets du forum.
     * 
     * @return	void
     * @access	protected
     */
   private function _listTopics(){
        // -- Si la pagination est nécessaire, on la génère.
        if ($this->_total > 1) {
            pagination($this->_page, $this->_total,('forum-' . $this->_id . '-p%s-' . skip_chars($this->data['f_name']) . '.html'), null, PAGINATION_ALL);
        }
        
        $sql = 'SELECT t_tid, t_last_uid, t_first_uid, t_first_pid, t_last_pid, UNIX_TIMESTAMP(t_last_time) AS last_time, t_first_time, t_postit, t_closed, t_views, t_posts, t_solved, t_title, t_description,
                        fr_pid, fr_tid,
                        uf.u_login AS first_login, uf.u_id AS first_uid, ul.u_login AS last_login, ul.u_id AS last_uid
                    FROM forums_topics t
                        LEFT OUTER JOIN users uf ON t.t_first_uid = uf.u_id
                        LEFT OUTER JOIN users ul ON t.t_last_uid = ul.u_id
                        LEFT OUTER JOIN forums_read r ON t.t_tid = r.fr_tid
                            AND fr_uid = :uid
                    WHERE t_fid = :id
                    ORDER BY t.t_postit DESC, t.t_last_time DESC
                    LIMIT :limit OFFSET :offset;';
        
        #REQ FOR_3
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':limit', TOPICS_PER_PAGE, SQL::PARAM_INT);
        $res->bindValue(':offset', ($this->_page - 1) * TOPICS_PER_PAGE, SQL::PARAM_INT);
        $res->execute();
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        //$three_months = new Date(Obj::$date->unix() - (READ_MONTHS * ONE_MONTH));
        
        foreach ($datas as $data){
            $is_read = (!Sys::$uid) || (($data['last_time'] + READ_MONTHS * ONE_MONTH) <= Obj::$date->unix()) || ($data['fr_pid'] == $data['t_last_pid']);
            
            $nbr_pages = ceil($data['t_posts'] / POSTS_PER_PAGE);
            $last_user = $data['last_uid'] ? 'Par <a href="profile-' . $data['last_uid'] . '-' . skip_chars($data['last_login']) . '.html">' . htmlspecialchars($data['last_login']) . '</a>' : '<em>Anonyme</em>';
            $first_user = $data['first_uid'] ? htmlspecialchars($data['first_login']) : 'Anonyme';
            $last_read = (!$is_read && $data['fr_pid']) ? ('<a href="topic-post-' . $data['fr_pid'] . '.html#p' . $data['fr_pid'] . '"><img src="./images/icones/last_post_read.png" alt="Aller au dernier post lu" /></a>&nbsp;') : '';
            
            $last_post = '<a href="topic-post-' . $data['t_last_pid'] . '.html#p' . $data['t_last_pid'] . '">' . parse_date($data['last_time']) . '</a><br />';
            
            // -- Si y'a pas de derneir post (même id de départ et de dernier)
            if( !$data['t_last_pid'] || $data['t_last_pid'] == $data['t_first_pid'] ){
                $last_post = '<em>Pas de réponses</em>';
                $last_user = '';
            }
            
            Obj::$tpl->setBlock('topics', array(
                    'IS_READ' => $is_read,
                    'ICON_PREFIX' => (!$is_read ? 'un' : ''),
                    'IS_SOLVED' => $data['t_solved'],
                    'IS_LOCKED' => $data['t_closed'],
                    'IS_POSTIT' => $data['t_postit'],
                    'TITLE' => htmlspecialchars($data['t_title']),
                    'U_TITLE' => 'topic-' . $data['t_tid'] . '-p1-' . skip_chars($data['t_title']) . '.html',
                    'DESCRIPTION' => htmlspecialchars($data['t_description']),
                    'FIRST_USER' => $first_user,
                    'FIRST_TIME' => strtolower(parse_date($data['t_first_time'])),
                    'VIEWS' => $data['t_views'],
                    'REPLIES' => ($data['t_posts'] - 1),
                    'LAST_USER' => $last_user,
                    'LAST_POST' => $last_post,
                    'LAST_READ' => $last_read
                ));
        
            // -- Pagination... !
            pagination(0, $nbr_pages, ('topic-' . $data['t_tid'] . '-p%s-' . skip_chars($data['t_title']) . '.html'), 'topics', PAGINATION_ACTIVATED);
        }
    }
}

/** EOF /**/
