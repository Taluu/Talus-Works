<?php
/**
 * Affichage d'un formulaire de réponse (création de sujets, réponse à un sujet, à un MP, etc).
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
 * @begin 08/01/2008, Talus
 * @last 17/07/2009, Talus
 * @ignore
 */

final class Frame_Child extends Frame {
    /**
     * Contient la revue d'un sujet.
     * 
     * @var array
     */
    private $_review = array();
    
    /**
     * Mode choisi (new | reply | edit)
     * 
     * @var string
     */
    private $_mode = '';
    
    /**
     * Type choisi (topic | pm)
     * 
     * @var string
     */
    private $_type = '';
    
    /**
     * Identifiant (forum, message, sujet, ...)
     * 
     * @var integer
     */
    private $_id = 0;
    
    /**
     * @ignore
     */
    protected function main(){
        // - Ca sert à rien si le type est pas connecté :)
        if (Sys::$uid == GUEST) {
            message('Vous devez être connecté pour entreprendre une telle action !', '', 2, MESSAGE_ERROR);
            exit;
        }
        
        $this->_mode = isset($_GET['mode']) ? $_GET['mode'] : false;
        $this->_type = isset($_GET['type']) ? $_GET['type'] : false;
        $this->_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // -- Mode non choisi ?
        if (!$this->_mode) {
            message('Aucun mode choisi !', '', 200, MESSAGE_ERROR);
            exit;
        }
        
        // -- Mode valide ?
        if (!in_array($this->_mode, array('new', 'reply', 'edit'))) {
            message('Mode choisi incorrect !', '', 201, MESSAGE_ERROR);
            exit;
        }
        
        // -- Type non choisi ?
        if (!$this->_type) {
            message('Aucun type de message choisi !', '', 202, MESSAGE_ERROR);
            exit;
        }
        
        // -- Type valide ?
        if (!in_array($this->_type, array('topic', 'pm'))) {
            message('Type de message choisi incorrect !', '', 203, MESSAGE_ERROR);
            exit;
        }
        
        //  -- Id valide ? (si seulement le mode choisi est l'edition ou bien un nouveau message)
        if (!$this->_id && ($this->_mode == 'new' || $this->_mode == 'edit')){
            message('ID invalide !', '', 204, MESSAGE_ERROR);
            exit;
        }
        
        // -- Appel de la méthode.
        call_user_func(array(&$this, '_' . strtolower($this->_mode) . ucfirst(strtolower($this->_type))));
        
        // -- Dans le cadre d'une réponse, on affiche les anciens messages.
        if ($this->_mode == 'reply'){
            $this->_review();	
        }
        
        // -- Ajout d'un style : Celui du colorateur !
        $this->addCSS('syntax_highlighter');
        $this->addCSS('bbcode');
        $this->addCSS('forums');
        
        // -- On ajoute l'AJaX
        $this->addJs('ajax');
        $this->addJs('form');
    }
    
    /**
     * Récupère les données d'un message.
     * 
     * @param integer id Donnée du post $id
     * @return void
     */
    private function _fetchMsg($id) {
        $return = array();
        
        // -- Tout dépend si on est dans une convesion MP ou un sujet !
        if ($this->_type == 'topic') {
            $sql = 'SELECT p_content,
                            u_login
                        FROM forums_posts p
                            LEFT JOIN users u ON p.p_uid = u.u_id
                        WHERE p_pid = :id;';
            
            #REQ POST_8
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':id', $id, SQL::PARAM_INT);
            $res->execute();
            $data = $res->fetch(SQL::FETCH_ASSOC);
            $res = null;
            
            if ($data) {
                $return = array(
                        'username' => $data['u_login'],
                        'pid' => $id,
                        'content' => $data['p_content']
                    );
            }
        }
        
        return $return;
    }
    
    /**
     * Récupère les 10 derniers messages, et le premier message d'un sujet.
     * 
     * @return void
     * @todo A revoir... :(
     */
    private function _review() {
        // -- Selon le type, tout diffère !
        if ($this->_type == 'topic'){
            // -- On fait 2 requêtes : Une pour le premier post, une pour les 9 derniers :)
            $sql = 'SELECT p_pid, p_uid, p_date, p_content,
                            u_id, u_login, u_level, u_email
                        FROM forums_posts p
                            LEFT JOIN users u ON p.p_uid = u.u_id
                        WHERE p_tid = :tid
                        ORDER BY p_date DESC
                        LIMIT 9;';
            
            #REQ POST_3
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':tid', $this->_id, SQL::PARAM_INT);
            $res->execute();
            $datas = $res->fetchAll(SQL::FETCH_ASSOC);
            $res = null;
            
            // -- Pas de données trouvées...
            if (!$datas) {
                message('Aucunes données retrouvées !', '', 220, MESSAGE_ERROR);
                exit;
            }
            
            // -- On récupère le premier message (err...)
            $sql = 'SELECT p_pid, p_uid, p_date, p_content,
                            u_id, u_login, u_level, u_email
                        FROM forums_posts p
                            LEFT JOIN users u ON u.u_id = p.p_uid
                        WHERE p_tid = :tid
                        ORDER BY p_date ASC
                        LIMIT 1;';
            
            #REQ POST_4
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':tid', $this->_id, SQL::PARAM_INT);
            $res->execute();
            $first = $res->fetch(SQL::FETCH_ASSOC);
            $res = null;
            
            // -- On ajoute le dernier post QUE si il est différent du dernier post récolté
            if ($datas[count($datas) - 1]['p_pid'] != $first['p_pid']) {
                $datas[] = $first;
            }
            
            // -- Les niveaux.
            $levels = array(
                    array('Administrateur', 'grp_admin'),
                    array('Modérateur', 'grp_modo'),
                    array('Membre', 'grp_user'),
                    array('Lecture Seule', 'grp_ls'),
                    array('Visiteur', 'grp_guest'),
                    array('Banni', 'grp_banned')
                );
            
            // -- cache des données des utilisateurs
            $cache = array();
            
            // -- On parse le tout :3
            foreach ($datas as $data) {
                $vars = array(
                        'ID' => $data['p_pid'],
                        'CONTENT' => bbcode($data['p_content']),
                        'DATE' => parse_date($data['p_date'])
                    );
                
                if( !isset($cache[$data['p_uid']]) ){
                    $cache[$data['p_uid']] = array(
                            'U_AVATAR' => $data['u_email'] ? md5(strtolower($data['u_email'])) : 'ad516503a11cd5ca435acc9bb6523536',
                            'U_NAME' => $data['u_login'] ? ('<a href="profile-' . $data['p_uid'] . '-' . skip_chars($data['u_login']) . '.html" class="' . $levels[$data['u_level']][1] . '">' . htmlspecialchars($data['u_login']) . '</a>') : '<em>Anonyme</em>',
                            'U_GRP' => $levels[$data['u_level']][0],
                            'U_CLASS' => $levels[$data['u_level']][1]
                        );
                }
                
                $vars += $cache[$data['p_uid']];
                
                Obj::$tpl->setBlock('review', $vars);
            }
        }
    }
    
    /**
     * Gère la création d'un nouveau sujet
     * 
     * @return boolean
     */
    private function _newTopic(){
        // -- Vérification du forum
        $tree = RI::getTree();
        
        // -- Pas de données retrouvées... ?
        if (!isset($tree[$this->_id])) {
            message('Vous savez poster dans un forum qui n\'existe pas ?', '',  241, MESSAGE_ERROR);
            exit;
        }
        
        // -- On vérifie le type du forum...
        if ($tree[$this->_id]['f_type'] != FORUM) {
            message('On ne poste pas autrement que dans un forum :o', '', 242, MESSAGE_ERROR);
            exit;
        }
        
        // -- Forum fermé ? Droits insuffisants ?
        if (Sys::$u_level > $tree[$this->_id]['f_modo'] && Sys::$u_level > $tree[$this->_id]['f_new']) {
            message('Vous n\'avez pas les droits nécessaires pour créer un nouveau sujet ici !', '', 243, MESSAGE_ERROR);
            exit;
        }
        
        // -- Tout baigne : on initilaise l'array !
        $this->data = array(
                'id' => $this->_id,
                'content' => '',
                't_title' => '',
                't_description' => '',
                't_postit' => '',
                'f_left' => $tree[$this->_id]['f_left'],
                'f_right' => $tree[$this->_id]['f_right'],
                'f_name' => $tree[$this->_id]['f_name'],
                'f_new' => $tree[$this->_id]['f_new'],
                'is_forum_modo' => Sys::$u_level <= $tree[$this->_id]['f_modo']
            );
        
        // -- Fil d'arianne
        $this->addToNav(forums_nav('Création d\'un sujet', $this->_id));
        //$this->addToNav(, false);
        
        // -- Le formulaire n'a pas été envoyé ; on l'affiche.
        if (!isset($_POST['send'])) {
            form('forums/new', FORM_NO_CAPTCHA);
            return;
        }
        
        // -- On traite les données :)
        $this->data = array_merge($this->data, array(
                'content' => trim($_POST['content']),
                't_title' => trim($_POST['t_title']),
                't_description' => trim($_POST['t_description']),
                't_postit' => (isset($_POST['t_postit']) && $this->data['is_forum_modo']),
            ));
        
        // -- Controle des champs obligatoires...
        if (multi_empty($this->data['content'], $this->data['t_title'])) {
            form('forums/new', FORM_NO_CAPTCHA, 'Le titre et le contenu du sujet sont obligatoires !');
            return;
        }
        
        // -- On insère le tout, et on met à jour les forums, les lus, les comptes de messages, ...
        $sql = 'INSERT INTO forums_topics (t_fid, t_first_uid, t_last_uid, t_first_time, t_last_time, t_postit, t_title, t_description)
                    VALUES (:fid, :uid, :uid, :date, :date, :postit, :title, :desc);';
        
        #REQ POST_15
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':fid', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':postit', $this->data['t_postit'], SQL::PARAM_BOOL);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->bindValue(':title', $this->data['t_title'], SQL::PARAM_STR);
        $res->bindValue(':desc', $this->data['t_description'], SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        $tid = Obj::$db->lastInsertId();
        
        $sql = 'INSERT INTO forums_posts (p_tid, p_uid, p_date, p_uip, p_content)
                    VALUES (:tid, :uid, :date, :uip, :content);';
        
        #REQ POST_16
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $tid, SQL::PARAM_INT);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':uip', Sys::$ip, SQL::PARAM_INT);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->bindValue(':content', $this->data['content'], SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        $pid = Obj::$db->lastInsertId();
        
        $sql = 'UPDATE forums_topics
                    SET t_first_pid = :pid, t_last_pid = :pid, t_posts = 1
                    WHERE t_tid = :tid;';
        
        #REQ POST_17
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $tid, SQL::PARAM_INT);
        $res->bindValue(':pid', $pid, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        $sql = 'UPDATE forums
                    SET f_last_tid = :tid, f_replies = f_replies + 1, f_topics = f_topics + 1
                    WHERE f_left <= :fleft AND f_right >= :fright;';
        
        #REQ POST_18
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $tid, SQL::PARAM_INT);
        $res->bindValue(':fleft', $this->data['f_left'], SQL::PARAM_INT);
        $res->bindValue(':fright', $this->data['f_right'], SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        $sql = 'INSERT INTO forums_read (fr_uid, fr_tid, fr_pid, fr_posted)
                    VALUES (:uid, :tid, :pid, 1);';
        
        #REQ  POST_19
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':tid', $tid, SQL::PARAM_INT);
        $res->bindValue(':pid', $pid, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        $sql = 'UPDATE users
                    SET u_posts = u_posts + 1
                    WHERE u_id = :uid;';
        
        #REQ POST_20
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        message('Sujet correctement créé !', 'topic-' . $tid . '-p1-' . skip_chars($this->data['t_title']) . '.html#p' . $pid, 244, MESSAGE_CONFIRM);
        
        return;
    }
    
    /**
     * Répond à un sujet
     * 
     * @return boolean
     * @todo anti flood
     */
    private function _replyTopic(){	
        // -- On Vérifie si le sujet existe, on vérifie si le type a des droits d'ecritures, etc.
        $sql = 'SELECT t_closed, t_title, t_solved, t_last_uid, UNIX_TIMESTAMP(t_last_time) AS last_time, t_first_uid, t_fid
                    FROM forums_topics
                    WHERE t_tid = ' . $this->_id . ';';
        
        #REQ POST_7
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        $tree = RI::getTree();
        
        // -- Si pas de données...
        if (!$data){
            message('Vous savez répondre à un sujet qui n\'existe pas ?', '',  231, MESSAGE_ERROR);
            exit;
        }
        
        // -- On vérifie le statut du sujet et du forum...
        if(Sys::$u_level > $tree[$data['t_fid']]['f_modo'] && ($data['t_closed'] || $tree[$data['t_fid']]['f_answer'] < Sys::$u_level)){
            message('Ce sujet (ou ce forum) est fermé à toutes réponses !', '', 232, MESSAGE_ERROR);
            exit;
        }	
        
        // -- Si une tentative de double post, et que c'est pas un modo, on l'envoi chier :). Penser à faire un anti flood...
        if (Sys::$u_level > $tree[$data['t_fid']]['f_modo'] && $data['t_last_uid'] == Sys::$uid && ($data['last_time'] + 12 * ONE_HOUR) > Obj::$date->unix()){
            message('Halte aux double posts en moins de 12h !', '', 234, MESSAGE_ERROR);
            exit;
        }
        
        // -- Initialisation de l'array
        $this->data = array(
                'id' => $this->_id,
                'content' => '',
                't_solved' => (bool) $data['t_solved'],
                't_closed' => (bool) $data['t_closed'],
                't_title' => $data['t_title'],
                't_first_uid' => $data['t_first_uid'],
                'f_id' => $tree[$data['t_fid']]['f_id'],
                'f_left' => $tree[$data['t_fid']]['f_left'],
                'f_right' => $tree[$data['t_fid']]['f_right'],
                'is_forum_modo' => Sys::$u_level <= $tree[$data['t_fid']]['f_modo'],
                'can_solve' => $data['t_first_uid'] == Sys::$uid || Sys::$u_level <= $tree[$data['t_fid']]['f_modo'],
            );
        
        $this->addToNav(forums_nav(array(), $this->data['f_id']));
        $this->addToNav(htmlspecialchars($this->data['t_title']), 'topic-' . $this->_id . '-p1-' . skip_chars($this->data['t_title']) . '.html');
        $this->addToNav('Ajout d\'une réponse', false);
        
        // -- Pas de formulaire envoyé / validé ==> Affichage du formulaire
        if (!isset($_POST['send']) || $_POST['send'] != 'Répondre') {
            // -- On récupère les données du post à citer, si demandé.
            if (isset($_GET['pid']) && ($quote = $this->_fetchMsg(abs(intval($_GET['pid']))))) {
                $this->data['content'] = '[quote=' . $quote['username'] . ',' . $quote['pid'] . ']' . $quote['content'] . '[/quote]';
            } elseif (!empty($_SESSION['quote'])) { // Si par JS, on souhaite citer plusieurs posts.... et que l'on ne vient pas de la RR :p
                $this->data['content'] = $_SESSION['quote'];
            } elseif (isset($_POST['send'])) { // -- Sinon, si on arrive par la réponse rapide....
                $this->data['content'] = $_POST['reply'];
            }
            
            // -- On vide la session des données.
            $_SESSION['quote'] = '';
            
            // -- Affichage du formulaire
            form('forums/reply', FORM_NO_CAPTCHA);
            return;
        }
        
        // -- MaJ de l'array $this->data
        $this->data = array_merge($this->data, array(
                'from_fast_reply' => isset($_POST['from_fast_reply']),
                'content' => trim($_POST['reply'])
            ));
        
        // -- Vérification du champ principal...
        if (empty($this->data['content'])) {
            form('forums/reply', FORM_NO_CAPTCHA, 'Tous les champs sont obligatoires !');
            return;
        }
        
        // -- On vérifie si le champ n'est pas trop court....
        if (strlen($this->data['content']) < MIN_LENGTH) {
            form('forums/reply', FORM_NO_CAPTCHA, 'Message trop court !');
            return;
        }
        
        // -- On vérifie mainteannt pour les chamsp à cocher (fermeture, ouverture)... Et qu'il ne vient pas de la réponse rapide !
        if (($this->data['t_first_uid'] == Sys::$uid || $this->data['is_forum_modo']) && !$this->data['from_fast_reply']) {
            $this->data['t_solved'] = isset($_POST['t_solved']) ;
            
            // -- Seul un modo peut fermer un sujet !
            if ($this->data['is_forum_modo']){
                $this->data['t_closed'] = isset($_POST['t_closed']);
            }
        }
        
        // -- On insère le tout, et on met à jour le tout
        $sql = 'INSERT INTO forums_posts (p_tid, p_uid, p_date, p_uip, p_content)
                    VALUES (:tid, :uid, :date, :uip, :content);';
        
        #REQ POST_9
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $this->_id, SQL::PARAM_INT); 
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT); 
        $res->bindValue(':uip', Sys::$ip, SQL::PARAM_INT); 
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR); 
        $res->bindValue(':content', parse_direct_urls($this->data['content']), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        $pid = Obj::$db->lastInsertId();
        
        
        $sql = 'UPDATE forums_topics
                    SET t_last_uid = :uid, t_last_pid = :pid, t_last_time = :date, t_posts = t_posts + 1, t_solved = :solved, t_closed = :closed
                    WHERE t_tid =:tid;';
        
        #REQ POST_10
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':pid', $pid, SQL::PARAM_INT); 
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT); 
        $res->bindValue(':tid', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR); 
        $res->bindValue(':closed', $this->data['t_closed'], SQL::PARAM_BOOL); 
        $res->bindValue(':solved', $this->data['t_solved'], SQL::PARAM_BOOL);  
        $res->execute();
        $res = null;
        
        
        $sql = 'UPDATE forums
                    SET f_replies = f_replies + 1, f_last_tid = :tid
                    WHERE f_left <= :fleft AND f_right >= :fright;';
        
        #REQ POST_11
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':fleft', $this->data['f_left'], SQL::PARAM_INT); 
        $res->bindValue(':fright', $this->data['f_right'], SQL::PARAM_INT); 
        $res->execute();
        $res = null;
        
        $sql = 'REPLACE INTO forums_read (fr_uid, fr_tid, fr_pid, fr_posted)
                    VALUES (:uid, :tid, :pid, 1);';
        
        #REQ POST_12
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':pid', $pid, SQL::PARAM_INT); 
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT); 
        $res->bindValue(':tid', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        
        $sql = 'UPDATE users
                    SET u_posts = u_posts + 1
                    WHERE u_id = :uid;';
        
        #REQ POST_13
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT); 
        $res->execute();
        $res = null;
        
        // -- Message de redirection, tout s'est bien passé :)
        message('Message correctement inséré !', 'topic-post-' . $pid . '.html#p' . $pid, 235, MESSAGE_CONFIRM);
        return;
    }
    
    /**
     * Edite un message
     * 
     * @return void
     * @access private
     */
    private function _editTopic(){
        // -- On selectionne les infos du post selectionné
        $sql = 'SELECT p_content, p_uid,
                        t_tid, t_title, t_closed, t_solved, t_description, t_first_pid, t_first_uid, t_fid
                    FROM forums_posts p
                        LEFT JOIN forums_topics t ON p.p_tid = t_tid
                    WHERE p_pid = :pid;';
        
        #REQ POST_20
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':pid', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        $tree = RI::getTree();
        
        // -- On vérifie si le post existe...
        if (!$data) {
            message('Le post selectionné n\'existe pas !', '', 210, MESSAGE_ERROR);
            exit;
        }
        
        // -- Est-il l'auteur ? Est-il modo ? Est-ce fermé ?
        if( ($data['p_uid'] != Sys::$uid || $data['t_closed']) &&  Sys::$u_level > $tree[$data['t_fid']]['f_modo']){
            message('Vous n\'avez pas les droits nécessaires à l\'édition de ce post !', 'topic-post-' . $this->_id . '.html#p' . $this->_id, 211, MESSAGE_ERROR);
            exit;
        }
        
        // -- Tout baigne, on met tout dans $this->data !
        $this->data = array(
                'id' => $this->_id,
                'content' => $data['p_content'],
                'p_uid' => (int) $data['p_uid'],
                't_tid' => (int) $data['t_tid'],
                't_solved' => (bool) $data['t_solved'],
                't_closed' => (bool) $data['t_closed'],
                't_title' => $data['t_title'],
                't_description' => $data['t_description'],
                't_first_pid' => $data['t_first_pid'],
                't_first_uid' => $data['t_first_uid'],
                'f_id' => $tree[$data['t_fid']]['f_id'],
                'f_modo' => $tree[$data['t_fid']]['f_modo'],
                'is_forum_modo' => Sys::$u_level <= $tree[$data['t_fid']]['f_modo'],
                'prev' => bbcode($data['p_content']),
                'edit_title' => $data['t_first_pid'] == $this->_id && $tree[$data['t_fid']]['f_modo'] >= Sys::$u_level,
                'can_solve' => Sys::$u_level <= $tree[$data['t_fid']]['f_modo'] || $data['t_first_uid'] == Sys::$uid
            );
        
        // -- Fil d'arianne
        $this->addToNav(forums_nav(array(), $this->data['f_id']));
        $this->addToNav(htmlspecialchars($this->data['t_title']), 'topic-' . $this->data['t_tid'] . '-p1-' . skip_chars($this->data['t_title']) . '.html');
        $this->addToNav('Edition d\'une réponse', false);
        
        // -- Le form est pas envoyé ? On l'affiche :)
        if (!isset($_POST['send']) || $_POST['send'] == 'Edition Avancée') {
            if (isset($_POST['send'])) {
                $this->data['content'] = $_POST['edit'];
            }
            
            form('forums/edit', FORM_NO_CAPTCHA);
            return;
        }
        
        // -- Sinon, on traite les données !
        $this->data = array_merge($this->data, array(
                'content' => trim($_POST['reply']),
                'prev' => bbcode(trim($_POST['reply']))
            ));
        
        // -- Titre et description ?
        if (isset($_POST['t_title'], $_POST['t_description'])) {
            $this->data['t_title'] = trim($_POST['t_title']);
            $this->data['t_description'] = trim($_POST['t_description']);
        }
        
        // -- On vérifie mainteannt pour les chamsp à cocher (fermeture, ouverture).. Si on ne vient pas de l'édition rapide !
        if (($this->data['t_first_uid'] == Sys::$uid || Sys::$u_level <= $this->data['f_modo']) && !isset($_POST['from_fast_edition'])) {
            $this->data['t_solved'] = isset($_POST['t_solved']) ;
            
            // -- Seul un modo peut fermer un sujet !
            if (Sys::$u_level <= $this->data['f_modo']) {
                $this->data['t_closed'] = isset($_POST['t_closed']);
            }
        }
        
        // -- Vérification du champ principal...
        if (multi_empty($this->data['content'], $this->data['t_title'])) {
            $this->data['t_title'] = $data['t_title'];
            $this->data['t_description'] = $data['t_description'];
            $this->data['content'] = $data['p_content'];
            
            form('forums/edit', FORM_NO_CAPTCHA, 'Tous les champs sont obligatoires !');
            return;
        }
        
        // -- On vérifie si le champ n'est pas trop court....
        if (strlen($this->data['content']) < MIN_LENGTH) {
            form('forums/reply', FORM_NO_CAPTCHA, 'Message trop court !');
            return;
        }
        
        // -- On fait les requêtes de MaJ.
        $sql = 'UPDATE forums_posts
                    SET p_content = :content, p_edit_uid = :uid, p_edit_times = p_edit_times + 1, p_edit_date = :date
                    WHERE p_pid = :pid;';
        
        #REQ POST_21
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':pid', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->bindValue(':content', parse_direct_urls($this->data['content']), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        $sql = 'UPDATE forums_topics
                    SET t_closed = :closed, t_solved = :solved, t_title = :title, t_description = :desc
                    WHERE t_tid = :tid;';
        
        #REQ POST_22
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $this->data['t_tid'], SQL::PARAM_INT);
        $res->bindValue(':closed', $this->data['t_closed'], SQL::PARAM_BOOL);
        $res->bindValue(':solved', $this->data['t_solved'], SQL::PARAM_BOOL);
        $res->bindValue(':title', $this->data['t_title'], SQL::PARAM_STR);
        $res->bindValue(':desc', $this->data['t_description'], SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        message('Message correctement édité !', 'topic-post-' . $this->_id . '.html#p' . $this->_id, 212, MESSAGE_CONFIRM);
        
        return;
    }
    
    /**
     * Envoi un nouveau MP
     * 
     * @return bool
     * @access private
     */
    private function _newPm(){
    }
    
    /**
     * Répond à un MP
     * 
     * @return	bool
     * @access	private
     */
    private function _replyPm(){
    }
    
    /**
     * Edite un MP
     * 
     * @return bool
     * @access private
     */
    private function _editPm(){
    }
    
    /**
     * Ajoute un participant à une conversation
     * 
     * @return bool
     */
    private function _addUserToPm(){
    }
}

/** EOF /**/
