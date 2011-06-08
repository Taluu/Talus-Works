<?php
/** 
 * Génèration d'une erreur Apache
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
 * @begin 12/02/2008, Talus
 * @last 21/06/2009, Talus
 * @ignore
 */

final class Frame_Child extends Frame {
    /**
     * Contient les headers à utiliser.
     * 
     * @var array
     * @access private
     */
    private $_headers = array(
            // -- 1XX
            100 => array('Cette partie de requête a déjà été recue, et est valide.', MESSAGE_NEUTRAL, 'HTTP/1.0 100 Continue'),
            101 => array('Le protocole a été changé.', MESSAGE_NEUTRAL, 'HTTP/1.0 101 Switching protocol'),
            
            // -- 2XX
            200 => array('Tout est OK !', MESSAGE_CONFIRM, 'HTTP/1.0 200 OK'),
            201 => array('Le document a bien été uploadé.', MESSAGE_CONFIRM, 'HTTP/1.0 201 Created'),
            204 => array('Le corps de la réponse est vide...', MESSAGE_CONFIRM, 'HTTP/1.0 204 No Content'),
            206 => array('Utilisation du cache OK !', MESSAGE_CONFIRM, 'HTTP/1.0 206 Partial Content'),
            
            // -- 3XX
            300 => array('Utilisation du cache OK !', MESSAGE_NEUTRAL, 'HTTP/1.0 300 Multiple Choice'),
            301 => array('Redirection Définitive...', MESSAGE_NEUTRAL, 'HTTP/1.0 301 Moved Permanently'),
            302 => array('Redirection Temporaire...', MESSAGE_NEUTRAL, 'HTTP/1.0 302 Moved Temporarly'),
            304 => array('Ce document n\'a pas été modifié.', MESSAGE_NEUTRAL, 'HTTP/1.0 304 Not Modified'),
            
            // -- 4XX
            400 => array('Erreur générique (Mauvaise requête ?)', MESSAGE_ERROR, 'HTTP/1.0 400 Bad Request'),
            401 => array('Vous devez vous identifier pour consulter ce contenu !', MESSAGE_ERROR, 'HTTP/1.0 401 Authorization required'),
            403 => array('Vous n\'avez pas les permissions pour consulter ce contenu !', MESSAGE_ERROR, 'HTTP/1.0 403 Forbidden'),
            404 => array('Le fichier demandé n\'existe pas !', MESSAGE_ERROR, 'HTTP/1.0 404 Not Found'),
            405 => array('La méthode envoyée n\'est pas autorisée...', MESSAGE_ERROR, 'HTTP/1.0 405 Not Allowed'),
            
            // -- 5XX
            500 => array('Erreur interne au serveur !', MESSAGE_ERROR, 'HTTP/1.0 500 Server Error'),
            501 => array('Votre navigateur ne supporte pas ce type de requête !', MESSAGE_ERROR, 'HTTP/1.0 501 Not Implemented'),
            503 => array('Le serveur est surchargé, veuillez patientez un peu !', MESSAGE_ERROR, 'HTTP/1.0 503 Service Unavailable'),
            505 => array('Votre navigateur ne supporte pas cette version de HTTP...', MESSAGE_ERROR, 'HTTP/1.0 505 Version Not Supported')
        );
    
    /**
     * Les indices pour l'array headers.
     * 
     * @var integer
     */
    const MESSAGE = 0;
    const TYPE = 1;
    const HEADER = 2;
    
    /**
     * @ignore
     */
    protected function main(){
        Obj::$router->name('error');
        
        // -- Pas d'erreur trouvée... On renvoi bouler !
        if (!Obj::$router['error'] || !isset($this->_headers[Obj::$router['error']])) {
            header('Location: index.html');
            exit;
        }

        $err = &Obj::$router['error'];
        $class = array('error', 'neutral', 'confirm');
        
        // -- Envoi du header
        header($this->_headers[$err][self::HEADER], true, $err);
        
        Obj::$tpl->set(array(
                'MESSAGE' => $this->_headers[$err][self::MESSAGE] . '<br />Si le problème persiste, <a href="contact.html">contactez-moi</a> !',
                'ID_MESSAGE' => "Apache_{$err}",
                'TITLE' => "Message d\'erreur Apache {$err} - Talus\' Works",
                'CLASS_CSS' => "message_{$class[$this->_headers[$err][self::TYPE]]}",
                'URL' => 'index.html',
                'TIME' => 0,
                'BAN' => false
            ));

        Obj::$tpl->parse('message.html');
        exit;
    }
}

/** EOF /**/
