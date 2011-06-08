<?php
/**
 * Gestion de la (dé)connexion d'un membre.
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
 * @begin 30/12/2007, Talus
 * @last 12/01/2009, Talus
 * @ignore
 */

class Frame_Child extends Frame {
    /**
     * Identifiant
     *
     * @var integer
     */
    private $_id = 0;
    
    /**
     * @ignore 
     */
    protected function main(){
        // -- On demande une déconnexion.
        if (isset($_GET['action']) && $_GET['action'] == 'out') {
            // -- Appel de la bonne méthode.
            $this->_logout();
            return;
        }
        
        // -- LE type est déjà connecté.. ca ne sert à rien de se connecter !
        if (Sys::$uid != GUEST) {
            message('Vous êtes déjà connecté !', '', 1, MESSAGE_ERROR);
            exit;
        }
        
        // -- Si pas de formulaire envoyé...
        if (!isset($_POST['send'])) {
            $this->setDatas('nickname', '');
            form('home/login', FORM_NO_CAPTCHA);
            return;
        }
        
        // -- Sinon, on traite les données
        $this->_login();
        return;
    }
    
    /**
     * Vérifie le formulaire, et connecte l'utilisateur si tout est OK
     * 
     * @return bool
     */
    private function _login(){
        // -- On renseigne $this->data.
        $this->data = array(
                'id' => GUEST,
                'nickname' => $_POST['nickname'],
                'mdp' => $_POST['mdp'],
                'autologin' => isset($_POST['autologin']),
                //'login_referer' => $_POST['login_referer']
            );
        
        // -- C'est parti pour une vérification globale...
        if (multi_empty($this->data['nickname'], $this->data['mdp'])) {
            form('home/login', FORM_NO_CAPTCHA, 'Tous les champs doivent être remplis !');
            return;
        }
        
        // -- On procéde à la vérif des données en BDD.
        $sql = 'SELECT up_uid, up_status
                    FROM users_password
                    WHERE up_login = :login
                        AND up_password = :mdp
                        AND up_uid <> 0;';
        
        #REQ LOG_1
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':login', $this->data['nickname'], SQL::PARAM_STR);
        $res->bindValue(':mdp', sha1($this->data['mdp']), SQL::PARAM_STR);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        if (!$data) {
            form('home/login', FORM_NO_CAPTCHA, 'Il n\'existe aucun membre avec le couple de login / mdp donné.');
            return;
        }
        
        if (!(bool)$data['up_status']) {
            form('home/login', FORM_NO_CAPTCHA, 'Le compte renseigné est inactif. <a href="contact.html">Contactez le webmaster</a> pour plus de détails...');
            return;
        }
        
        Sys::$uid = $this->data['id'] = $data['up_uid'];
        
        /*
         * On demande une connexion automatique ? On génère une clé d'autologin,
         * et on la fixe dans un cookie et dans la BDD.
         */
        if ($this->data['autologin']) {
            $this->data['autologin'] = sha1(uniqid(mt_rand(), true));
            set_cookie('auto', $this->data['autologin'], ONE_YEAR);
            
            // -- MaJ de la BDD.
            $sql = 'UPDATE users_password
                        SET up_autologin_key = :key
                        WHERE up_uid = :uid;';
            
            #REQ LOG_2
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':key', sha1($this->data['autologin']), SQL::PARAM_STR);
            $res->bindValue(':uid', $this->data['id'], SQL::PARAM_INT);
            $res->execute();
            $res = null;
        }
        
        $sql = 'UPDATE users
                    SET u_last_connexion = :date, u_ip = :uip
                    WHERE u_id = :uid;';
        
        #REQ LOG_3
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->bindValue(':uip', Sys::$ip, SQL::PARAM_INT);
        $res->bindValue(':uid', $this->data['id'], SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        message('Vous êtes maintenant connecté sur Talus\' Works ;)', '', 61, MESSAGE_CONFIRM);
        exit;
    }
    
    /**
     * Déconnecte l'utilisateur
     */
    private function _logout(){	
        // -- Anti Sea Surf : Vérification de l'ID !
        if (Sys::$uid == GUEST) {
            message('Vous êtes déjà déconnecté !', '', 2, MESSAGE_ERROR);
            exit;
        }

        $this->_id = (int)(isset($_GET['uid']) ? $_GET['uid'] : GUEST);
        
        if ($this->_id != Sys::$uid) {
            message('Vous avez été victime d\'une attaque <a href="http://fr.wikipedia.org/wiki/CSRF">CSRF ("Sea-Surf")</a>...', '', 3, MESSAGE_ERROR);
            exit;
        }
        
        // -- Si y'a des cookies d'autoconnexion, on les supprimes.... Et on supprime également la clé d'autologin dans la BDD.
        if (($auto = get_cookie('auto')) !== NULL) {
            $sql = 'UPDATE users_password
                        SET up_autologin_key = NULL
                        WHERE up_autologin_key = :key
                            AND up_uid = :uid;';
            
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':key', sha1($auto), SQL::PARAM_STR);
            $res->bindValue(':uid', $this->_id, SQL::PARAM_INT);
            $res->execute();
            $res = null;
            
            // -- Destruction du cookie.
            set_cookie('auto', null, -ONE_MINUTE);
        }
        
        // -- La déconnexion réside juste à foutre l'id de la session en cours à celui du visiteur... Et a faire un refresh (en détruisant la session) :D
        Sys::$uid = GUEST;
        session_destroy();
        $_SESSION = array();
        
        message('Vous vous êtes déconnecté.', '', 62, MESSAGE_CONFIRM);
        exit;
    }
}

/** EOF /**/
