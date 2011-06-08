<?php
/**
 * Procède à l'inscription d'un membre.
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
 * @begin 28/12/2007, Talus
 * @last 01/05/2009, Talus
 * @ignore
 */


final class Frame_Child extends Frame {	    
    /**
     * @ignore
     */
    protected function main(){
        // -- Le type est connecté ; pourquoi donc s'inscrire ? :p
        if (Sys::$uid != GUEST){
            message('Vous êtes déjà enregistré et connecté !', '', 1, MESSAGE_ERROR);
            exit;
        }
        
        // -- Si pas de formulaire envoyé...
        if (!isset($_POST['send'])){
            $this->data += array(
                    'nickname' => '',
                    'email' => ''
                );
            form('home/register', FORM_USE_CAPTCHA);
            return;
        }
        
        // -- Sinon, on traite les données
        $this->_register();
        return;
    }
    
    /**
     * Vérifie et envoie le formulaire
     * 
     * @return bool
     */
    private function _register(){
        // -- On renseigne $this->data.
        $this->data = array(
                'nickname' => trim($_POST['nickname']),
                'mdp' => trim($_POST['mdp']),
                'confirm_mdp' => trim($_POST['confirm_mdp']),
                'email' => trim($_POST['email']),
                'captcha' => $_POST['captcha'],
                'rules' => isset($_POST['rules'])
            );
        
        // -- C'est parti pour une vérification globale...
        // -- Tout d'abord, on vérifie qu'aucun des champs ne sont vides...
        if (multi_empty($this->data['nickname'], $this->data['mdp'], $this->data['confirm_mdp'], $this->data['email'])){
            form('home/register', FORM_USE_CAPTCHA, 'Tous les champs doivent être remplis !');
            return;
        }
        
        // -- Vérification des arguments
        // -- Login non valide
        if (!preg_match('`^[\w][\w-_ ]{1,28}[\w]$`', $this->data['nickname'])){
            form('home/register', FORM_USE_CAPTCHA, 'Login non valide !');
            return;
        }
        
        // -- Mots de passes différents...
        if ($this->data['mdp'] != $this->data['confirm_mdp']){
            form('home/register', FORM_USE_CAPTCHA, 'Les deux mots de passes entrés sont différents !');
            return;
        }
        
        // -- Vérification du mail..
        $mail_verif = array();
        if (!preg_match('`^\w[\w_.-]*@(\w[\w.-]*\.[a-zA-Z]{2,})$`', $this->data['email'], $mail_verif) || !checkdnsrr($mail_verif[1])){
            form('home/register', FORM_USE_CAPTCHA, 'L\'adresse mail entrée est invalide !');
            return;
        }
        
        // -- Vérification du captcha...
        if ($this->data['captcha'] !== $_SESSION['captcha']){
            form('home/register', FORM_USE_CAPTCHA,  'Le captcha est invalide !');
            return;
        }
        
        // -- Dernière vérification, mais pas des moindres...
        if ($this->data['rules'] == false){
            form('home/register', FORM_USE_CAPTCHA, 'Vous devez accepter <a href="cgu.html#rules">les règles</a> avant de continuer !');
            return;
        }
        
        // -- Si on arrive ici, c'est que tout s'est bien passé au niveau des vérifs de bases. On peut donc procéder la vérif plus poussée, et pourquoi pas l'enregistrement en BDD !
        // -- On vérifie les doublons de mail ou de logins....
        $sql = 'SELECT COUNT(*)
                    FROM users
                    WHERE u_login = :login
                        OR u_email = :mail;';
        
        #REQ REG_1
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':login', $this->data['nickname'], SQL::PARAM_STR);
        $res->bindValue(':mail', $this->data['email'], SQL::PARAM_STR);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_NUM);
        $res = null;
        
        // -- Doublons detectés...
        if ($data[0] != 0){
            form('home/register', FORM_USE_CAPTCHA, 'Il existe déjà un membre avec cet email ou ce login !');
            return;
        }
        
        /*
         * Phew, Si on est arrivé là, on peut procéder à l'enregistrement en
         * BDD (sous reserves d'activation... Huhuhu).
         */
        $this->data['activation_key'] = sha1(uniqid(mt_rand(), true));
        
        $sql = 'INSERT INTO users_password (up_login, up_password, up_activation_key, up_activation_date)
                    VALUES (:login, :mdp, :key, :date);';
        
        #REQ REG_2
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':login', htmlspecialchars($this->data['nickname']), SQL::PARAM_STR);
        $res->bindValue(':mdp', sha1($this->data['mdp']), SQL::PARAM_STR);
        $res->bindValue(':key', $this->data['activation_key'], SQL::PARAM_STR);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        // -- On récupère l'identifiant, et on insère dans la table des users
        $this->data['id'] = Obj::$db->lastInsertId();
        
        $sql = 'INSERT INTO users (u_id, u_login, u_email, u_register, u_last_connexion)
                    VALUES (:id, :login, :email, :date, :date);';
        
        #REQ REG_3
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->data['id'], SQL::PARAM_INT);
        $res->bindValue(':login', htmlspecialchars($this->data['nickname']), SQL::PARAM_STR);
        $res->bindValue(':email', $this->data['email'], SQL::PARAM_STR);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        $sql = 'INSERT INTO users_pref (up_id)
                    VALUES (:id);';
        
        #REQ REG_4
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->data['id'], SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        // -- Variables à parser...
        Obj::$tpl->set(array(
                'PSEUDO' => htmlspecialchars($this->data['nickname']),
                'MDP' => $this->data['mdp'],
                'ACTIVATION_KEY' => $this->data['activation_key'],
                'ID' => $this->data['id'],
                'TITLE' => 'Votre Inscription à Talus\' Works'
            ));
        
        $headers = array(
                'From: "Talus\' Works" <contact@talus-works.net>',
                'Reply-To: "Talus\' Works" <contact@talus-works.net>',
                'MIME-Version: 1.0',
                'Content-Type: text/html;charset=utf-8', 
                'Content-Transfer-Encoding: 8bit',
                'X-Mailer: PHP/' . PHP_VERSION
            );
        
        // -- On envoi le mail...
        mail($this->data['email'], 'Votre inscription sur Talus\' Works', Obj::$tpl->pparse('mails/register.html'), implode(PHP_EOL, $headers)); 
        
        // -- Affichage d'un message de confirmation.
        message('Votre inscription a été enregistré. Toutefois, vous devez activer votre compte pour pouvoir l\'utiliser.<br />Pour cela, une clé d\'activation vous a été envoyé par mail.', '', 60, MESSAGE_CONFIRM);
        exit;
    }
}

/** EOF /**/
