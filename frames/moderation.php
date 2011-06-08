<?php
/**
 * Gère le pannel de modération.
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
 * @begin 12/02/2008, Talus
 * @last 19/07/2009, Talus
 * @ignore
 */

final class Frame_Child extends Frame {
    /**
     * Module à executer.
     * 
     * @var string
     * @access private
     */
    private $_module = 'index';
    
    /**
     * Constantes pour la liste des modules
     * 
     * @var const
     * @access public
     */
    const SHOW = 0;
    const NAME = 1;
    
    /**
     * Identifiant utilisé
     * 
     * @var integer
     */
    private $_id= 0;
    
    /**
     * Identifiant d'un forum
     * 
     * @var integer
     */
    private $_fid = 0;
    
    /**
     * Identifiant d'un sujet
     * 
     * @var integer
     */
    private $_tid = 0;
    
    /**
     * Liste des modules possibles
     * 
     * @var array
     * @access private
     */
    private $_list = array(
            'index' => array(SHOW_MODULE, 'Accueil', 'moderation.html'), 
            'abuse' => array(SHOW_MODULE, 'Signalements', 'moderation-abuse.html'), 
            'warn' => array(SHOW_MODULE, 'Avertissements', 'moderation-warn.html'),
            'ban' => array(SHOW_MODULE, 'Bannissements', 'moderation-ban.html'),
            'move' => array(SHOW_MODULE, 'Déplacement de Sujets', 'moderation-move.html'), 
            'user' => array(SHOW_MODULE, 'Utilisateurs', 'moderation-user.html'), 
            'ip' => array(SHOW_MODULE, 'Recherche d\'IP', 'moderation-ip.html'), 
            
            'lock' => array(HIDE_MODULE, 'Fermeture d\'un sujet', null), 
            'post_it' => array(HIDE_MODULE, 'Post-it d\'un sujet', null), 
            'solve' => array(HIDE_MODULE, 'Résolution d\'un sujet', null),
            'delete' => array(HIDE_MODULE, 'Suppression d\'un message', null),
            'report' => array(HIDE_MODULE, 'signalement d\'un sujet', null)
        );
    
    /**
     * @ignore
     */
    protected function main(){
        if (Sys::$uid == GUEST) {
            message('Vous devez être connecté pour acceder à cette rubrique !', '', 1, MESSAGE_ERROR);
            exit;
        }
        
        $this->_module = isset($_GET['module']) ? str_replace('-', '_', $_GET['module']) : 'index';
        $this->_module = isset($this->_list[$this->_module])? $this->_module : 'index';
        
        // -- Génération des onglets.... Visibles :)
        get_tabs($this->_list, $this->_module);
        
        // -- De la bagatelle, telle que le titre, le fil d'arianne, le module courant, ....
        $this->setTitle($this->_list[$this->_module][self::NAME] . ' - Panel de Modération');
        
        $this->addToNav('Modération', 'moderation.html');
        $this->addToNav($this->_list[$this->_module][self::NAME], false);
        
        // -- On lance le bon module :)
        call_user_func(array(&$this, '_' . $this->_module));
    }
    
    /**
     * Affiche l'index du panel de modération, soit les 5 derneirs signalements et les 5 derniers avertos
     * 
     * @return void
     * @access private
     * @todo 5 derniers logs modos ? Systemes de logs ?
     */
    private function _index(){		
        if (Sys::$u_level > GRP_MODO) {
            message('Vous n\'avez pas les droits suffisants pour consulter cette rubrique', '', 403, MESSAGE_ERROR, '403 Forbidden');
            exit;
        }
        
        $sql = 'SELECT a_id, a_uid, a_mid, a_datetime, a_solved_datetime,
                        u.up_login AS user_login,
                        m.up_login AS modo_login
                    FROM abuse a
                        LEFT JOIN users_password u ON a.a_uid = u.up_uid
                        LEFT JOIN users_password m ON a.a_mid = m.up_uid
                    ORDER BY a_id DESC
                    LIMIT 5;';
        
        #REQ MODO_1
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Mise en cache pour les membres
        $cache = array(
                'user' => array(),
                'modo' => array()
            );
        
        // -- On explore chaque données :)
        foreach ($datas as $data) {
            if (!isset($cache['user'][$data['a_uid']])) {
                $cache['user'][$data['a_uid']] = ($data['a_uid'] && $data['user_login']) ? ('<a href="profile-' . $data['a_uid'] . '-' . skip_chars($data['user_login']) . '.html">' . htmlspecialchars($data['user_login']) . '</a>') : '<em>Anonyme</em>';
            }
            
            if (!isset($cache['modo'][$data['a_mid']])) {
                $cache['modo'][$data['a_mid']] = ($data['a_mid'] && $data['modo_login']) ? ('<a href="profile-' . $data['a_mid'] . '-' . skip_chars($data['modo_login']) . '.html">' . htmlspecialchars($data['modo_login']) . '</a>') : '<em>Anonyme</em>';
            }
            
            Obj::$tpl->setBlock('abuse', array(
                    'ID' => $data['a_id'],
                    
                    'USER' => $cache['user'][$data['a_uid']],
                    'MODO' => $cache['modo'][$data['a_mid']],
                    
                    'DATE' => parse_date($data['a_datetime']),
                    'DATE_SOLVED' => parse_date($data['a_solved_datetime']),
                    
                    'IS_SOLVED' => $data['a_mid'] != GUEST
                ));
        }
        
        $sql = 'SELECT w_uid, w_mid, w_date, w_value,
                        u.up_login AS user_login,
                        m.up_login AS modo_login
                    FROM warn w
                        LEFT JOIN users_password u ON w.w_uid = u.up_uid
                        LEFT JOIN users_password m ON w.w_mid = m.up_uid
                    ORDER BY w_date DESC
                    LIMIT 5;';
        
        #REQ MODO_2
        $res = Obj::$db->query($sql);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- On explore chaque données :)
        foreach ($datas as $data){
            if (!isset($cache['user'][$data['w_uid']])){
                $cache['user'][$data['w_uid']] = ($data['w_uid'] && $data['user_login']) ? ('<a href="profile-' . $data['w_uid'] . '-' . skip_chars($data['user_login']) . '.html">' . htmlspecialchars($data['user_login']) . '</a>') : '<em>Anonyme</em>';
            }
            
            if (!isset($cache['modo'][$data['w_mid']])){
                $cache['modo'][$data['w_mid']] = ($data['w_mid'] && $data['modo_login']) ? ('<a href="profile-' . $data['w_mid'] . '-' . skip_chars($data['modo_login']) . '.html">' . htmlspecialchars($data['modo_login']) . '</a>') : '<em>Anonyme</em>';
            }
            
            if ($data['value'] > 0) {
                $type = 'Ajout d\'un avertissement';
            } elseif ($data['value'] < 0) {
                $type = 'Retrait d\'un avertissement';
            } else {
                $type = 'Ajout d\'une note';
            }
            
            Obj::$tpl->setBlock('warn', array(
                    'TYPE' => $type,
                    'UID' => $data['w_uid'],
                    
                    'USER' => $cache['user'][$data['w_uid']],
                    'MODO' => $cache['modo'][$data['w_mid']],
                    
                    'DATE' => parse_date($data['w_date'])
                ));
        }
        
        // -- On alloue le TPL.
        $this->setTpl('modo/home.html');
    }
    
    /**
     * Gère les abus.
     * 
     * @return void
     * @access private
     */
    private function _abuse(){}
    
    /**
     * Gère les avertissements
     * 
     * @return void
     * @access private
     */
    private function _warn(){}
    
    /**
     * Bannit un utilisateur
     * 
     * @return void
     * @access private
     */
    private function _ban(){}
    
    /**
     * Supprime un message.
     * 
     * @return void
     * @access private
     */
    private function _delete(){
        $this->_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        if (!$this->_id) {
            message('I see dead posts.', 'moderation.html', 75, MESSAGE_ERROR);
            exit;
        }
        
        // -- Requete de vérification.
        $sql = 'SELECT p_tid, p_uid, t_first_pid, t_fid
                    FROM forums_posts p
                        LEFT JOIN forums_topics t ON p.p_tid = t.t_tid
                    WHERE p_pid = :pid;';
        
        #REQ MODO_11
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':pid', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        if (!$data) {
            message('I see dead posts.', 'moderation.html', 75, MESSAGE_ERROR);
            exit;
        }
        
        $tree = RI::getTree();
        $tree = $tree[$data['t_fid']];
        
        if ($tree['f_modo'] < Sys::$u_level) {
            message('Vous n\'avez pas les droits nécessaires à la suppression de ce message !', 'topic-post-' . $this->_id . '.html#' . $this->_id, 403, MESSAGE_ERROR);
            exit;
        }
        
        if ($data['t_first_pid'] == $this->_id) {
            message('Vous ne pouvez pas supprimer le premier message d\'un sujet !', 'topic-post-' . $this->_id . '.html#' . $this->_id, 403, MESSAGE_ERROR);
            exit;
        }
        
        // -- Tout est OK, on procède à la suppression.
        $sql = 'DELETE FROM forums_posts
                    WHERE p_pid = :pid;';
        
        #REQ MODO_12
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':pid', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $res = null;

        // -- Synchronisation !
        Sync::sujets();
        Sync::forums();
        Sync::clean();
        
        // -- On décrémente le nombre de message de l'utilisateur concerné.
        $sql = 'UPDATE users
                    SET u_posts = u_posts - 1
                    WHERE u_id = :uid;';
        
        #REQ MODO_13
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':uid', $data['p_uid'], SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        message('Le message a été supprimé.', 'topic-' . $data['p_tid'] . '-p1.html', 76, MESSAGE_CONFIRM);
    }
    
    /**
     * Bouge un sujet
     * 
     * @return boolean
     * @access private
     */
    private function _move(){
        $this->_id = isset($_POST['send']) && !empty($_POST['id']) ? intval($_POST['id']) : 0;
        $this->_id = !$this->_id && isset($_GET['id']) ? intval($_GET['id']) : $this->_id;
        
        $this->addToNav('Déplacement d\'un sujet', false);
        
        $this->_fid = isset($_POST['send']) && !empty($_POST['fid']) ? intval($_POST['fid']) : 0;
        
        Obj::$tpl->set(array(
                'VIEW' => $this->_fid,
                'ID' => $this->_id  
            ));
        
        // On affiche un formulaire... Si il n'y a pas eu d'id renseigné.
        if (!$this->_id) {
            form('modo/move', FORM_NO_CAPTCHA);
            return false;
        }
        
        // -- On récupère le forum actuel du sujet à déplacer.
        $sql = 'SELECT t_fid, t_title
                    FROM forums_topics t
                    WHERE t_tid = :tid;';
        
        #REQ MODO_9
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':tid', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        if (!$data) {
            message('Le sujet spécifié n\'existe pas !', 'moderation-move.html', 70, MESSAGE_ERROR);
            exit;
        }
        
        $tree = RI::getTree();
        
        // -- Contrôle du pouvoir du membre :)
        if (Sys::$u_level > $tree[$data['t_fid']]['f_modo']) {
            message('Vous n\'avez pas les droits nécessaires pour déplacer ce sujet !', 'topic-' . $this->_id . '-p1.html', 403, MESSAGE_ERROR);
            exit;
        }
        
        Obj::$tpl->set(array(
                'CURRENT' => $data['t_fid'],
                'VIEW' => !$this->_fid
            ));
        
        /*
         * Si y' a pas un ID pour le forum destinataire, on le demande... Parmis
         * les forums où le modérateur a des droits d'écriture.
         */
        if ((bool) !$this->_fid){
            // -- On choisit que les forums ou le type a un droit d'écriture et de lecture.
            foreach($tree as $node){
                if ($node['f_new'] >= Sys::$u_level) {
                    $bloc = 'select' . ($node['f_type'] == CATEGORY ? '' : '.forums');
                    
                    Obj::$tpl->setBlock($bloc, array(
                            'ID' =>$node['f_id'],
                            'NAME' => htmlspecialchars($node['f_name']),
                            'LEVEL' => str_repeat('&nbsp;&nbsp;&nbsp;|', (($node['f_level'] - 1) < 0 ? 0 : ($node['f_level'] - 1))),
                            'TYPE' => $node['f_type']
                        ));
                }
            }
            
            form('modo/move', FORM_NO_CAPTCHA);
            return false;
        }
        
        // -- On ne bouge... Que si le nouveau forum est différent de celui actuel !
        if ($data['t_fid'] != $this->_fid){
            // -- On bouge le sujet.
            $sql = 'UPDATE forums_topics
                        SET t_fid = :fid
                        WHERE t_tid = :id;';
            
            #REQ MODO_10
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':fid', $this->_fid, SQL::PARAM_INT);
            $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
            $res->execute();
            $res = null;
            
            // -- On met à jour les forums.
            Sync::forums();
        }
        
        message('Le sujet a bien été déplacé :)', 'topic-' . $this->_id . '-' . skip_chars($data['t_title']) . '.html', 74, MESSAGE_CONFIRM);
        return true;
    }
    
    /**
     * Modifie le profil d'un utilisateur
     * 
     * @return void
     * @access private
     */
    private function _user(){}
    
    /**
     * Gère les IPs
     * 
     * @return void
     * @access private
     */
    private function _ip(){}
    
    /**
     * Ferme un sujet.
     * 
     * @return void
     */
    private function _lock(){
        $this->_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // -- Pas d'ID ==> pas de fermeture !
        if (!$this->_id) {
            message('Aucun id de sujet sélectionné !', 'moderation.html', 70, MESSAGE_ERROR);
            exit;
        }
        
        // -- Requête SQL pour sélectionner les données...
        $sql = 'SELECT t_title, t_closed, t_fid
                    FROM forums_topics t
                    WHERE t_tid = :id;';
        
        #REQ MODO_3
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Pas de données retrouvées...
        if (!$data) {
            message('Aucunes données retrouvées !', 'moderation.html', 71, MESSAGE_ERROR);
            exit;
        }
        
        $tree = RI::getTree();
        $tree = $tree[$data['t_fid']];
        
        // -- Pas de perm...
        if (Sys::$u_level > $tree['f_modo']) {
            message('Vous n\'avez aucun droit pour effectuer cette action.', 'moderation.html', 403, MESSAGE_ERROR, '403 : Forbidden');
            exit;
        }
        
        // -- Sinon c'est OK : on actualise le sujet !
        $sql = 'UPDATE forums_topics
                    SET t_closed = :closed
                    WHERE t_tid = :id;';
        
        #REQ MODO_4
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':closed', $data['t_closed'] ^ 1, SQL::PARAM_BOOL);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        // - On redirige vers le sujet :)
        message('Ce sujet a bien été ' . ($data['t_closed'] ? 'ouvert' : 'fermé'), 'topic-' . $this->_id . '-p1-' . skip_chars($data['t_title']) . '.html', 72, MESSAGE_CONFIRM);
    }
    
    /**
     * Met un sujet en post it
     * 
     * @return void
     */
    private function _post_it(){
        $this->_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // -- Pas d'ID ==> pas de fermeture !
        if (!$this->_id) {
            message('Aucun id de sujet !', 'moderation.html', 70, MESSAGE_ERROR);
            exit;
        }
        
        // -- Requête SQL pour sélectionner les données...
        $sql = 'SELECT t_title, t_postit, t_fid
                    FROM forums_topics t
                    WHERE t_tid = :id;';
        
        #REQ MODO_5
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Pas de données retrouvées...
        if (!$data) {
            message('Aucunes données retrouvées pour ce sujet !', 'moderation.html', 71, MESSAGE_ERROR);
            exit;
        }
        
        $tree = RI::getTree();
        $tree = $tree[$data['t_fid']];
        
        // -- Pas de perm...
        if (Sys::$u_level > $tree['f_modo']) {
            message('Vous n\'avez aucun droit pour effectuer cette action.', 'moderation.html', 403, MESSAGE_ERROR, '403 : Forbidden');
            exit;
        }
        
        // -- Sinon c'est OK : on actualise le sujet !
        $sql = 'UPDATE forums_topics
                    SET t_postit = :postit
                    WHERE t_tid = :id;';
        
        #REQ MODO_6
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':postit', $data['t_postit'] ^ 1, SQL::PARAM_BOOL);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        // - On redirige vers le sujet :)
        message('Le sujet a bien été ' . (!$data['t_postit'] ? 'ajouté' : 'retiré') . ' des Post-it', 'topic-' . $this->_id . '-p1-' . skip_chars($data['t_title']) . '.html', 73, MESSAGE_CONFIRM);
    }
    
    /**
     * Marque un sujet comme 'résolu'.
     * 
     * @return void	
     */
    private function _solve(){
        $this->_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        
        // -- Pas d'ID ==> pas de fermeture !
        if (!$this->_id) {
            message('Aucun id de sujet !', 'moderation.html', 70, MESSAGE_ERROR);
            exit;
        }
        
        // -- Requête SQL pour sélectionner les données...
        $sql = 'SELECT t_title, t_solved, t_first_uid, t_fid
                    FROM forums_topics t
                    WHERE t_tid = :id;';
        
        #REQ MODO_7
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Pas de données retrouvées...
        if (!$data) {
            message('Aucunes données retrouvées pour ce sujet !', 'moderation.html', 71, MESSAGE_ERROR);
            exit;
        }
        
        $tree = RI::getTree();
        $tree = $tree[$data['t_fid']];
        
        // -- Pas de perm...
        if (Sys::$u_level > $tree['f_modo'] && Sys::$uid != $data['t_first_uid']) {
            message('Vous n\'avez aucun droit pour effectuer cette action.', 'topic-' . $this->_id . '-p1-' . skip_chars($data['t_title']) . '.html', 403, MESSAGE_ERROR, '403 : Forbidden');
            exit;
        }
        
        // -- Sinon c'est OK : on actualise le sujet !
        $sql = 'UPDATE forums_topics
                    SET t_solved = :solved
                    WHERE t_tid = :id;';
        
        #REQ MODO_8
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->_id, SQL::PARAM_INT);
        $res->bindValue(':solved', $data['t_solved'] ^ 1, SQL::PARAM_BOOL);
        $res->execute();
        $res = null;
        
        message((!$data['t_solved'] ? 'Ajout' : 'Retirement') . ' du marqueur "Résolu" accompli', 'topic-' . $this->_id . '-p1-' . skip_chars($data['t_title']) . '.html', 73, MESSAGE_CONFIRM);
    }
    
    /**
     * Signale un truc douteux
     * 
     * @return void
     */
    private function _report(){}
}

/** EOF /**/
