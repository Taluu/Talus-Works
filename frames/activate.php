<?php
/**
 * Activation des clés.
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
 * @last 21/06/2009, Talus
 * @ignore
 * @todo Repenser la gestion des mdp
 */

final class Frame_Child extends Frame {
    /**
     * @ignore
     */
    protected function main(){
        // -- Le type est déjà connecté.. Il n'y a donc rien à activer...
        if (Sys::$uid != GUEST) {
            message('Il n\'y a rien à activer, car vous êtes déjà connecté !', '', 1, MESSAGE_ERROR);
            exit;
        }
        
        /*
         * Il y a 3 cas de figures : soit on demande un chgment de mdp, soit on
         * demande une activation, soit un nouveau mail d'activation.
         */
        if (Obj::$router['frame'] == 'forgot') {
            $this->setTitle('Perte de mot de passe');
            $this->addToNav('Perte de Mot de Passe');

            Obj::$router->name('step', 'id');

            /*
             * Si l'utilisateur commence la demande, on lui affiche le formulaire
             * de génération ; sinon, il a recu le mail, et doit alors entrer
             * la clé d'activation du changement de mot de passer pour procéder
             * au changement
             */
            if (Obj::$router['step'] == 'request') {
                $this->addToNav('Génération d\'une nouvelle clé', false);


                $this->data = array(
                        'email' => '',
                        'captcha' => '',
                        'key' => '',
                        'nickname' => '',
                        'id' => GUEST
                    );

                if (!isset($_POST['send'])) {
                    form('home/forgot', FORM_USE_CAPTCHA);
                    return;
                }

                $this->_send_mdp_key();
                return;
            }

            if (!Obj::$router['id']) {
                message('Pas d\'id renseignée...', '', 46, MESSAGE_ERROR);
                exit;
            }

            $this->addToNav('Changement du mot de passe', false);

            // -- Formulaire de réactivation si les données n'ont pas été saisies
            if (!isset($_POST['send'])) {
                form('home/chg_mdp', FORM_USE_CAPTCHA);
                return;
            }

            // -- Changement du mot de passe
            $this->_chg_mdp(Obj::$router['id']);
            return;
        }

        Obj::$router->name('type', 'id', 'key');
         
        if (Obj::$router['type'] == 'activate') {
            $this->_activate(Obj::$router['id'], Obj::$router['key']);
            return;
        }
        
        // -- Formulaire de réactivation pas encore envoyé...
        if (!isset($_POST['send'])) {
            $this->data['email'] = '';
            form('home/get_activation', FORM_USE_CAPTCHA);
            return;
        }
        
        $this->_resend_activation();
        return;
    }
    
    /**
     * Envoie la nouvelle clée pour un nouveau MDP
     * 
     * @return bool
     * @access private
     */
    private function _send_mdp_key(){
        $this->data = array(
                'email' => $_POST['email'],
                'captcha' => $_POST['captcha'],
                'key' => '',
                'nickname' => '',
                'id' => GUEST
            );
        
        // -- Vérification que rien n'est vide..
        if (multi_empty($this->data['email'], $this->data['captcha'])) {
            form('home/forgot', FORM_USE_CAPTCHA, 'Tous les champs sont obligatoires !');
            return;
        }
        
        // -- Vérification "basique" du mail.
      $mail_verif = array();
        if (!preg_match('`^\w[\w_.-]*@(\w[\w.-]*\.[a-zA-Z]{2,})$`', $this->data['email'], $mail_verif) || !checkdnsrr($mail_verif[1])) {
            form('home/forgot', FORM_USE_CAPTCHA, 'L\'adresse mail entrée est invalide !');
            return;
        }
        
        // -- Vérification du captcha.
        if ($this->data['captcha'] != $_SESSION['captcha']) {
            form('home/forgot', FORM_USE_CAPTCHA, 'Le captcha est invalide !');
            return;
        }
        
        // -- On procéde maintenant à la vérification en BDD (voir si le mail existe).
        $sql = 'SELECT u_id, u_login
                    FROM users
                    WHERE u_email = :mail
                        AND u_id <> ' . GUEST . ';';

        #REQ ACT_5
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':mail', $this->data['email'], SQL::PARAM_STR);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Aucunes données trouvées...
        if (!$data) {
            form('home/forgot', FORM_USE_CAPTCHA, 'Aucunes correspondances trouvées dans la table des membres pour l\'email donné.');
            return;
        }
        
        // -- Tout est OK :)
        $this->data['id'] = $data['u_id'];
        $this->data['nickname'] = $data['u_login'];
        $this->data['key'] = sha1(uniqid(mt_rand(), true));
        
        // -- Enregistrement de la clé, de la date de demande.
        $sql = 'UPDATE users_password
                    SET	up_password_key = :key, up_password_date = :date
                    WHERE up_uid = :id;';
        
        #REQ ACT_6
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->data['id'], SQL::PARAM_INT);
        $res->bindValue(':key', $this->data['key'], SQL::PARAM_STR);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        // -- Envoi d'un mail de récapitulation
        Obj::$tpl->set(array(
                'TITLE' => 'Mot de passe perdu ?',
                'NICKNAME' => htmlspecialchars($this->data['nickname']),
                'KEY' => $this->data['key'],
                'DATE' => Obj::$date->format('d/m/Y, \à H:i'),
                'ID' => $this->data['id']
            ));
        
        $headers = array(
                'From : "Talus\' Works" <contact@talus-works.net>',
                'MIME-Version : 1.0',
                'Content-Type: text/html; charset="utf-8"', 
                'Content-Transfer-Encoding: 8bit',
                'X-Mailer: PHP/' . PHP_VERSION
            );
        
        // -- On envoi le mail...
        mail($this->data['email'], 'Perte d\'un mot de passe sur Talus\' Works',
             Obj::$tpl->pparse('mails/forgot.html'), implode(PHP_EOL, $headers));
        
        message('Le mail contenant votre nouvelle clé a été envoyé.<br />
                 Suivez les instructions du mail pour changer votre mot de passe.', '', 49, MESSAGE_CONFIRM);
        return true;
    }
    
    /**
     * Change un mot de passe.
     *
     * @param integer $id Id de l'utilisateur
     * @return bool
     * @access private
     */
    private function _chg_mdp($id){
        $this->data = array(
                'key' => $_POST['key'],
                'mdp' => $_POST['mdp'],
                'confirm_mdp' => $_POST['confirm_mdp'],
                'captcha' => $_POST['captcha'],
                'id' => intval($id),
                'nickname' => '',
                'email' => ''
            );
        
        // -- On vérifie si les données fournies sont correctes
        if (multi_empty($this->data['key'], $this->data['mdp'], $this->data['confirm_mdp'], $this->data['captcha'], $this->data['id'])) {
            form('home/chg_mdp', FORM_USE_CAPTCHA , 'Tous les champs sont obligatoires.');
            return;
        }
        
        if (!preg_match('`^[a-z0-9]{40}$`', $this->data['key'])) {
            form('home/chg_mdp', FORM_USE_CAPTCHA , 'La clé n\'est pas valide !');
            return;
        }
        
        if ($this->data['mdp'] != $this->data['confirm_mdp']) {
            form('home/chg_mdp', FORM_USE_CAPTCHA , 'Les deux mots de passes entrés sont différents !');
            return;
        }
        
        if ($this->data['captcha'] != $_SESSION['captcha']) {
            form('home/chg_mdp', FORM_USE_CAPTCHA , 'Les captcha entré n\'est pas valide !');
            return;
        }
        
        // -- On passe à la vérification SQL...
        $sql = 'SELECT FROM_UNIXTIME(up_password_date) AS date_valid, up_login, u_email
                    FROM users_password
                        LEFT OUTER JOIN users ON users_password.up_uid = users.u_id
                    WHERE up_password_key = :key
                        AND up_uid = :id;';
        
        #REQ ACT_7
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->data['id'], SQL::PARAM_INT);
        $res->bindValue(':key', $this->data['key'], SQL::PARAM_STR);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Aucunes données retrouvées.
        if (!$data) {
            form('home/chg_mdp', FORM_USE_CAPTCHA , 'Aucune correspondance trouvées dans la table des utilisateurs.');
            return;
        }
        
        $this->data['nickname'] = $data['up_login'];
        $this->data['email'] = $data['u_email'];
        
        // -- La clé a expiré. On nullifie la clé, et on affiche un message pour l'utilisateur.
        if (($data['date_valid'] + ONE_DAY) > Obj::$date->unix()) {
            $sql = 'UPDATE users_password
                        SET up_password_key = NULL, up_password_date = NULL
                        WHERE up_password_key = :key
                            AND up_uid = :id;';
            
            #REQ ACT_8
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':id', $this->data['id'], SQL::PARAM_INT);
            $res->bindValue(':key', $this->data['key'], SQL::PARAM_STR);
            $res->execute();
            $res = null;
            
            // -- Affichage du message.
            message('Votre clé a expiré ; Votre mot de passe n\'a donc pas été changé.', '', 44, MESSAGE_ERROR);
            exit;
        }
        
        // -- Tous les tests ont été passés ; On active met donc à jour le compte :3
        $sql = 'UPDATE users_password
                    SET up_password = :mdp, up_password_key = NULL, up_password_date = :date
                    WHERE up_uid = :id
                    AND up_password_key = :key;';
        
        #REQ ACT_9
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->data['id'], SQL::PARAM_INT);
        $res->bindValue(':mdp', sha1($this->data['mdp']), SQL::PARAM_STR);
        $res->bindValue(':key', $this->data['key'], SQL::PARAM_STR);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        // -- Envoi d'un mail de récapitulation
        Obj::$tpl->set(array(
                'PSEUDO' => htmlspecialchars($this->data['nickname']),
                'MDP' => $this->data['mdp'],
                'KEY' => $this->data['key'],
                'TITLE' => 'Mot de passe sur Talus\' Works changé',
                'DATE' => Obj::$date->format('d/m/Y, \à H:i')
            ));
        
        $headers = array(
                'From : "Talus\' Works" <contact@talus-works.net>',
                'MIME-Version : 1.0',
                'Content-Type: text/html; charset=utf-8', 
                'Content-Transfer-Encoding: 8bit',
                'X-Mailer: PHP/' . PHP_VERSION
            );
        
        mail($this->data['email'], 'Votre mot de passe a été changé sur Talus\' Works', Obj::$tpl->pparse('mails/new_password.html'), implode(PHP_EOL, $headers)); 
        
        message('Votre mot de passe a bien été changé.<br />Vous allez recevoir un mail récapitulant de nouveau vos informations ;)', '',  47, MESSAGE_CONFIRM);
        return;
    }
    
    /**
     * Renvoi un mail d'activation.
     * 
     * @return bool
     * @access private
     */
    protected function _resend_activation(){
        $this->data = array(
                'email' => $_POST['email'],
                'captcha' => $_POST['captcha'],
                'nickname' => '',
                'id' => 0,
                'key' => ''
            );
        
        // -- Vérification que rien n'est vide..
        if (multi_empty($this->data['email'], $this->data['captcha'])) {
            form('home/get_activation', FORM_USE_CAPTCHA, 'Tous les champs sont obligatoires !');
            return;
        }
        
        // -- Vérification "basique" du mail.
        $mail_verif = array();
        if (!preg_match('`^\w[\w_.-]*@(\w[\w.-]*\.[a-zA-Z]{2,})$`', $this->data['email'], $mail_verif) || !checkdnsrr($mail_verif[1])) {
            form('home/get_activation', FORM_USE_CAPTCHA, 'L\'adresse mail entrée est invalide !');
            return;
        }
        
        // -- Vérification du captcha.
        if ($this->data['captcha'] != $_SESSION['captcha']) {
            form('home/get_activation', FORM_USE_CAPTCHA,  'Le captcha est invalide !');
            return;
        }
        
        // -- On récupère les données.
        $sql = 'SELECT u_id, u_email, u_login
                    FROM users
                    WHERE u_email = :email
                        AND u_id <> ' . GUEST . ';';
        
        #REQ ACT_10
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':email', $this->data['email'], SQL::PARAM_STR);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Aucunes données retrouvées.
        if (!$data) {
            message('Aucune correspondance trouvées dans la table des utilisateurs.', '', 42, MESSAGE_ERROR);
            exit;
        }
        
        // -- Tout est benef ; on renvoit le mail, et on met à jour la date d'expiration de la clé.
        $this->data['key'] =  sha1(uniqid(mt_rand(), true));
        $this->data['nickname'] = $data['u_login'];
        $this->data['id'] = $data['u_id'];
        
        $sql = 'UPDATE users_password
                     SET up_activation_key = :key, up_activation_date = :date
                     WHERE up_uid = :id
                        AND up_uid <> ' . GUEST . ';';
        
        #REQ ACT_11
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', $this->data['id'], SQL::PARAM_INT);
        $res->bindValue(':key', $this->data['key'], SQL::PARAM_STR);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->execute();
        $res = null;
        
        // -- Envoi d'un mail de confirmation
        Obj::$tpl->set(array(
                'PSEUDO' => htmlspecialchars($this->data['nickname']),
                'KEY' => $this->data['key'],
                'ID' => $this->data['id'],
                'TITLE' => 'Votre clé d\'activation de compte',
                'DATE' => parse_date(Obj::$date->sql(), '%d/%m/%Y @ %H:%M')
            ));
        
        $headers = array(
                'From : "Talus\' Works" <inscriptions@talus-works.net>',
                'MIME-Version : 1.0',
                'Content-Type: text/html; charset=utf-8', 
                'Content-Transfer-Encoding: 8bit',
                'X-Mailer: PHP/' . PHP_VERSION
            );
        
        mail($this->data['email'], 'Votre clé d\'activation de votre compte sur Talus\' Works', Obj::$tpl->pparse('mails/activation.html'), implode(PHP_EOL, $headers)); 
        
        message('La clé d\'activation a été renvoyée à l\'adresse ' . $this->data['email'], 'http://www.talus-works.net', 48, MESSAGE_CONFIRM);
        return true;
    }
    
    /**
     * Active un compte.... Ou pas :D
     * 
     * @return void
     * @access private
     */
    private function _activate(){
        // -- La clé et trop courte, ou pas valide.
        if (!preg_match('`^[a-z0-9]{40}$`', Obj::$router['key'])) {
            message('La clé d\'activation est invalide.', '', 41, MESSAGE_ERROR);
            exit;
        }
        
        // -- On vérifie que l'entrée est OK, que ce n'est toujours pas validé, et que la date est OK aussi (moins d'une semaine)
        $sql = 'SELECT FROM_UNIXTIME(up_activation_date) AS date_valid, up_status
                    FROM users_password
                    WHERE up_activation_key = :key
                        AND up_uid = :id;';
        
        #REQ ACT_1
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':id', Obj::$router['id'], SQL::PARAM_INT);
        $res->bindValue(':key', Obj::$router['key'], SQL::PARAM_STR);
        $res->execute();
        $data = $res->fetch(SQL::FETCH_ASSOC);
        $res = null;
        
        // -- Aucunes données retrouvées.
        if (!$data) {
            message('Aucune correspondance trouvées dans la table des utilisateurs.', '', 42, MESSAGE_ERROR);
            exit;
        }
        
        // -- L'utilisateur est déjà activé...
        if ((bool) $data['up_status']) {
            message('Votre compte est déjà activé', '', 43, MESSAGE_ERROR);
            exit;
        }
        
        // -- La date est expirée. On supprime le compte, et on affiche un message d'erreur.
        if (($data['date_valid'] + ONE_WEEK) >= Obj::$date->unix()) {
            // -- Suppresion du compte.
            $sql = 'DELETE FROM users_password
                        WHERE up_uid = :id;';
            
            #REQ ACT_2
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':id', Obj::$router['id'], SQL::PARAM_INT);
            $res->execute();
            $res = null;
            
            $sql = 'DELETE FROM users
                        WHERE u_id = :id;';
            
            #REQ ACT_3
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':id', Obj::$router['id'], SQL::PARAM_INT);
            $res->execute();
            $res = null;
            
            // -- Affichage du message.
            message('Votre clé d\'activation a expiré ; Par conséquent, votre compte a été détruit.<br />
                    Repassez par la procédure d\'inscription SVP', '', 44, MESSAGE_ERROR);
            exit;
        }
        
        // -- Tous les tests ont été passés ; On active donc le compte :3
        $sql = 'UPDATE users_password
                    SET up_status = 1
                    WHERE up_uid = :id;';
        
        #REQ ACT_4
            $res = Obj::$db->prepare($sql);
            $res->bindValue(':id', Obj::$router['id'], SQL::PARAM_INT);
            $res->execute();
            $res = null;
        
        // -- Affichage du message :3
        message('Votre compte a bien été activé.<br />
                 Vous pouvez dès à présent vous connecter sur Talus\' Works !', '', 45, MESSAGE_CONFIRM);
    }
}

/** EOF /**/
