<?php
/**
 * Formulaire de contact
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
 * @begin 29/12/2007, Talus
 * @last 01/05/2009, Talus
 */

final class Frame_Child extends Frame {	
    /**
     * Contient le captcha.
     * @var array
     * @access public
     */
    public $captcha = array();
    
    /**
     * @ignore 
     */
    protected function main(){
        $this->setTitle('Contact du Webmaster');
        $this->setDesc('Contact de Talus, Webmaster de Talus\' Works');
        $this->addToNav('Contact', false);
        
        // -- Si pas de formulaire envoyé...
        if (!isset($_POST['send'])) {
            $this->data = array(
                   'nickname' => '',
                    'email' => '',
                    'captcha' => '',
                    'message' => '',
                    'sujet' => '' 
                );
            
            form('home/contact', FORM_USE_CAPTCHA);
            return;
        }
        
        // -- Sinon, on traite les données
        $this->check_and_submit();
        return;
    }
    
    /**
     * Vérifie le formulaire
     * @return bool
     * @access protected
     */
    protected function check_and_submit(){
        // -- On renseigne $this->data.
        $this->data = array(
                'nickname' => trim($_POST['nickname']),
                'email' => trim($_POST['email']),
                'captcha' => $_POST['captcha'],
                'message' => trim($_POST['message']),
                'sujet' => trim($_POST['sujet'])
            );
        
        // -- C'est parti pour une vérification globale...
        if (multi_empty($this->data['nickname'], $this->data['message'],  $this->data['sujet'],  $this->data['email'])){
            form('home/contact', FORM_USE_CAPTCHA, 'Tous les champs doivent être remplis !');
            return;
        }
        
        if (!preg_match('`^\w[\w_.-]*@(\w[\w.-]*\.[a-zA-Z]{2,})$`', $this->data['email'], $mail_verif) || !checkdnsrr($mail_verif[1])) {
            form('home/contact', FORM_USE_CAPTCHA, 'L\'adresse mail entrée est invalide !');
            return;
        }
        
        if ($this->data['captcha'] !== $_SESSION['captcha']) {
            form('home/contact', FORM_USE_CAPTCHA, 'Le captcha est invalide !');
            return;
        }
        
        // -- Si on arrive ici, c'est que tout s'est bien passé au niveau des vérifs de bases. On peut donc préparer l'envoi du mail :3
        Obj::$tpl->set(array(
                'PSEUDO' => $this->data['nickname'],
                'SUJET' => $this->data['sujet'],
                'MESSAGE' => $this->data['message'],
                'DATE' => strftime('%d/%m/%Y @ %H:%m', Obj::$date->unix()),
                'IP' => long2ip(Sys::$ip),
                'EMAIL' => $this->data['email']
            ));
        
        $headers = array(
                'From : "' . $this->data['nickname'] . '" <' . $this->data['email'] . '>',
                'MIME-Version : 1.0',
                'Content-Type: text/plain; charset=utf-8', 
                'Content-Transfer-Encoding: 8bit'
            );
        
        mail('"Talus" <webmaster@talus-works.net>', $this->data['sujet'], Obj::$tpl->pparse('mails/contact.html'), implode(EOL, $headers)); 
        
        message('Mail envoyé, le webmaster vous répondra sous peu ;)', '', 30, MESSAGE_CONFIRM);
        exit;
    }
}