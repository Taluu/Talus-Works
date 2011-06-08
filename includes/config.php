<?php
/**
 * Contient les infos de connexion, en fonction du serveur (localhost / en ligne)
 *
 * Vous êtes libre d'utiliser et de distribuer ce script comme vous l'entendez, en gardant à l'esprit 
 * que ce script est, à l'origine, fait par des développeurs bénévoles : en conséquence, veillez à 
 * laisser le Copyright, par respect de ceux qui ont consacré du temps à la création du script.  
 *
 * @package Talus' Works
 * @author Baptiste "Talus" Clavié <talusch@gmail.com>
 * @copyright ©Talus, Talus' Works 2007, 2009
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 12/01/2008, Talus
 * @last 27/12/2008, Talus
 */

// -- Protection de la page.
if( !( defined('COMMON') && COMMON === true ) ){
    header('Location: ../index.php');
    exit;
}

// -- Instanciation de l'array.
$sql = array();

// -- Si on est en local, on attribue les infos comme étant... locales :)
if ($_SERVER['HTTP_HOST'] == 'localhost' || $_SERVER['HTTP_HOST'] == '127.0.0.1' || $_SERVER['SERVER_NAME'] == 'localhost' || $_SERVER['SERVER_ADDR'] == '127.0.0.1') {
    $sql = array(
            'DSN' => 'mysql:host=localhost;dbname=talus_works',
            'LOGIN' => 'root',
            'PASSWD' => ''
        );
    
    define('DOMAIN_COOKIE', 'localhost');
    define('DOMAIN_REDIRECT', 'http://localhost/talus_works/');
    define('DOMAIN_PATH', 'C:\Utilisateurs\Talus\Talus\Web\Talus\' Works\www');
    define('COOKIE_PATH', '/talus_works/');
    define('IS_LOCAL', true);
} else { // Environnement de déploiment
    $sql = array(
            'DSN' => 'mysql:host=host;dbname=bdd',
            'LOGIN' => 'login_sql',
            'PASSWD' => 'pass_sql'
        );
    
    define('DOMAIN_COOKIE', '.domain.tld');
    define('DOMAIN_REDIRECT', 'http://www.domain.tld/');
    define('DOMAIN_PATH', '/path/to/www/');
    define('COOKIE_PATH', '/');
    define('IS_LOCAL', false);
}

/** EOF /**/
