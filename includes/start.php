<?php
/**
 * Démarre le bordel
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
 * @package Talus' TPL
 * @author Baptiste "Talus" Clavié <talusch@gmail.com>
 * @copyright ©Talus, Talus' Works 2007+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 24/12/2007, Talus
 * @last 15/09/2009, Talus
 */

// -- Protection de la page.
if (!defined('COMMON') || COMMON !== true) {
    header('Location: ../index.php');
    exit;
}

// -- Gestion des erreurs PHP.
//require(ROOT . 'includes/class/exceptions.php');

error_reporting(E_ALL | E_STRICT);

/*
 * Envoi de l'header UTF-8. Si le naviageteur accepte l'encodage standard, alors
 * on l'utilise (application/xhtml+xml) ; sinon, on utilise simplement le text/html.
 */
if (isset($_SERVER['HTTP_ACCEPT']) && stristr($_SERVER['HTTP_ACCEPT'], 'application/xhtml+xml')) {
    $contentType = 'application/xhtml+xml';
    $doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN"
                    "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
} else {
    $contentType = 'text/html';
    $doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
                    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
}

header("Content-Type: {$contentType};encoding=utf8");

// -- Si on est en PHP6, on indique que l'on souhaite passer complètement en utf8 :3
if( version_compare(PHP_VERSION, '6.0.0DEV') >= 0 ){
    @ini_set('unicode.runtime_encoding', 'utf-8');
}

// -- Les fichiers de config
require ROOT . 'includes/config.php';
require ROOT . 'includes/constantes.php';
require ROOT . 'includes/functions.php';

// -- On est en plein travaux si on est pas en local, et si le fichier ./cache/maintenance existe.
if (!IS_LOCAL && is_file(ROOT . 'cache/maintenance')){
    $msg = '<?xml version="1.0" encoding="utf-8" ?>' . $doctype . '

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="fr">
    <head>
        <title>Maintenance en cours...</title>
        <meta http-equiv="Content-Type" content="' . $contentType . '; charset=utf-8" />
    </head>
    <body>
        <h1>Maintenance en cours...</h1>';
    
    $maintenance = file_get_contents(ROOT . 'cache/maintenance');
    
    if (!empty($maintenance) ){
        $msg .= "<h2>Raison :</h2>{$maintenance}";
    }
    
    echo "{$msg}</body></html>";
    exit;
}

/*
 * On récupéres les données des cookies de sessions, et par la même occasion,
 * on change la durée de vie de ceux ci... Et aussi, on indique qu'il faut
 * utiliser *que* les cookies.
 *
 * Puis, on démarre la session et on la regénère.
 */
$session_params = session_get_cookie_params();
session_set_cookie_params(TIME_CONNECTED, $session_params['path'], $session_params['domain'], false, true);
unset($session_params);

session_name('talus_works_sid');
session_start();
session_regenerate_id(true);

$chrono = chrono(0);

/**
 * Méthode magique de PHP5, qui permet de charger le fichier de définition des classes.
 * 
 * @param string $classname Nom de la classe à renvoyer
 * @return void
 */
function __autoload($classname){
    if (file_exists(ROOT . 'includes/class/' . strtolower($classname) . '.php')) {
        require_once ROOT . 'includes/class/' . strtolower($classname) . '.php';
        return;
    }
    
    if (file_exists(ROOT . 'includes/namespaces/' . strtolower($classname) . 'php')) {
        require_once ROOT . 'includes/namespaces/' . strtolower($classname) . 'php';
        return;
    }
    
    return false;
}

/**
 * Contient les principaux objets du site
 * 
 * @access  public
 * @package Talus' Works
 * @final
 */
final class Obj {
    /**
     * Interface pour la gestion SQL
     *
     * @var SQL
     */
    public static $db = null;

    /**
     * Interface pour la gestion de la Frame actuelle
     *
     * @var Frame
     */
    public static $frame = null;

    /**
     * Interface pour la gestion TPL
     *
     * @var Talus_TPL
     */
    public static $tpl = null;

    //public static $cfg = null; // A voir :p

    /**
     * Interface pour la gestion des Dates
     *
     * @var Date
     */
    public static $date = null;

    /**
     * Routeur
     *
     * @var Router
     */
    public static $router = null;
}

/**
 * Les données essentielles à l'appli
 * 
 * @package Talus' Works
 * @final
 */
final class Sys {
    // -- Info sur la connexion
    public static $sid = ''; // Session ID du visiteur
    public static $ip = 0; // IP (format ip2long()) du visiteur
    public static $uid = GUEST; // Id du visiteur
    public static $u_level = 4; // Niveau d'accès [[a recoder]]
    
    // -- Gestion horaire
    public static $utc = 0.0; // Décalage UTC
    public static $dst = 0; // Mode DST ou non ?
    public static $decal = array();
    
    // -- Données, préférences de l'utilisateur
    public static $u_data = array('u_login' => 'Visiteur'); // Données sur l'utilisateur
    public static $redirection = MESSAGE_REDIRECTION_ENABLED; // Type de redirection
    
    // -- Info diverses
    public static $nbr_connectes = 0; // Nombre de connectés
    public static $where = ''; // Ou est le visiteur ?
    public static $debug = false; // Mode Debug
    
    /**
     * Cache Simple des différentes données
     * Contient les caches suivant :
     *  -> attach : Cache des attachements
     *  -> unread : Cache des données lues / non lues
     *  -> jumpbox : Cache de la jumpbox
     *
     * @var array
     */
    public static $cache = array(
            'attach' => array(),
            'unread' => array(),
            'jumpbox' => array()
        );
}

Sys::$debug = IS_LOCAL;
//Sys::$debug = true; // Si on veut absolument débugger...

/*
 * Définition des différentes instances des objets importants de Talus' Works,
 * ainsi que ses parametres globaux (éléments de la classe Sys)
 *
 * On profite aussi, une fois le moteur TPL démarré, de renseigner le bon doctype
 * et le bon content-type pour les TPLs
 */
Obj::$date = new Date('now', new DateTimeZone(TIME_ZONE));

define('NOW', Obj::$date->unix());

Obj::$db = new SQL($sql['DSN'], $sql['LOGIN'], $sql['PASSWD']);
Obj::$tpl = Talus_TPL::__init('./tpl/files/', './tpl/cache/');
Obj::$router = new Router;

/*
header('Content-Type: text/plain');
Obj::$router->name('caca', 'prout');
print_r(Obj::$router['extra']);
exit;//*/

unset($sql);

Obj::$tpl->set(array(
        'DOCTYPE' => $doctype,
        'CONTENT_TYPE' => $contentType
    ));

Sys::$sid = session_id();
Sys::$ip = get_ip(true);
Sys::$dst = (int) Obj::$date->isDST();

if (!isset($_SESSION['uid'])) {
    $_SESSION['uid'] = GUEST;
}

if (!isset($_SESSION['quote'])) {
    $_SESSION['quote'] = '';
}

Sys::$uid = &$_SESSION['uid'];
Sys::$where = '?' . ($_SERVER['QUERY_STRING'] ? $_SERVER['QUERY_STRING'] : 'frame=index');

// -- On tente une connexion automatique... Si le type est pas connecté, et qu'il a un cookie pour :p
if (Sys::$uid == GUEST && $auto = get_cookie('auto')) {
    $sql = 'SELECT up_uid
                FROM users_password
                WHERE up_autologin_key = :key
                    AND up_status = 1
                    AND up_uid <> :guest;';
    
    #REQ START_1
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':key', sha1($auto), SQL::PARAM_STR);
    $res->bindValue(':guest', GUEST, SQL::PARAM_INT);
    $res->execute();
    $data = $res->fetch(SQL::FETCH_NUM);
    $res = null;
    
    // -- Si on a trouvé un membre... On actualise le tout, pour un an :)
    if ((bool) $data) {
        Sys::$uid = $data[0];
        $new_autologin_key = sha1(uniqid(mt_rand(), true));
        set_cookie('auto', $new_autologin_key, ONE_YEAR);
        
        $sql = 'UPDATE users_password
                    SET up_autologin_key = :key
                    WHERE up_uid = :uid;';
        
        #REQ START_2
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':key', sha1($new_autologin_key), SQL::PARAM_STR);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        // -- MaJ de la dernière connexion.
        $sql = 'UPDATE users
                    SET u_last_connexion = :date, u_ip = :uip
                    WHERE u_id = :uid;';
        
        #REQ LOG_3
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->bindValue(':uip', Sys::$ip, SQL::PARAM_INT);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->execute();
        $res = null;
    } else {// -- Sinon, on détruit le vilain cookie :D
        set_cookie('auto', null, -ONE_MINUTE);
    }
}

// -- Si ce n'est pas un visiteur... On répertorie les infos pour :D
if( Sys::$uid != GUEST ){
    $sql = 'SELECT u_id, u_login, u_email, u_ip, u_level, u_register, u_last_connexion, u_posts, u_avatar, u_signature, u_quote, 
                    up_utc, up_redirection, up_mail_status
                FROM users
                LEFT JOIN users_pref ON users.u_id = users_pref.up_id
                WHERE u_id = :uid;';
    
    #REQ START_4
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
    $res->execute();
    $data = $res->fetch(SQL::FETCH_ASSOC);
    $res = null;
    
    // -- Banzaï, on le connecte :)
    if ((bool)$data) {
        Sys::$u_data = $data;
        
        Sys::$utc = (float)Sys::$u_data['up_utc'];
        Sys::$redirection = Sys::$u_data['up_redirection'];
        Sys::$u_level = (int)Sys::$u_data['u_level'];
    } else {
        Sys::$uid = GUEST;
    }
}

Sys::$decal = float_to_time(Sys::$dst + Sys::$utc);
Sys::$debug = IS_LOCAL || Sys::$u_level <= GRP_ADMIN;

// -- Le type est banni ; dans ce cas, on affiche un zouli message d'erreur :D
if (is_banned()) {
    Obj::$tpl->set(array(
            'MESSAGE' => 'Vous êtes banni de Talus\' Works !',
            'ID_MESSAGE' => 10,
            'TITLE' => 'Message d\'erreur',
            'CLASS_CSS' => 'message_error',
            'URL' => '',
            'BAN' => true
        ));
    
    Obj::$tpl->parse('message.html');
    exit;
}

// -- Mise à jour de la table des sessions.
$sql = 'REPLACE INTO sessions (s_sid, s_uip, s_uid, s_date, s_where)
            VALUES(:sid, :uip, :uid, :date, :where);';

#REQ START_5
$res = Obj::$db->prepare($sql);
$res->bindValue(':sid', sha1(Sys::$sid), SQL::PARAM_STR);
$res->bindValue(':where', Sys::$where, SQL::PARAM_STR);
$res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
$res->bindValue(':uip', Sys::$ip, SQL::PARAM_INT);
$res->bindValue(':date', Obj::$date->unix(), SQL::PARAM_INT);
$res->execute();
$res = null;

// -- On ne vide la table de sessions que toutes les 5 min.
if ((file_get_contents(ROOT . 'cache/sessions') + TIME_CONNECTED) <= NOW){
    $sql = 'DELETE
                FROM sessions
                WHERE s_date <= :date;';
    
    #REQ START_6
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':date', (NOW - TIME_CONNECTED), SQL::PARAM_INT);
    $res->execute();
    $res = null;
    
    // -- Regénération du cache
    file_put_contents(ROOT . 'cache/sessions', NOW);
}

// -- Compte des connectés.
$sql = 'SELECT COUNT(*)
            FROM sessions;';

#REQ START_7
$res = Obj::$db->query($sql);
$data = $res->fetch(SQL::FETCH_NUM);
Sys::$nbr_connectes = $data[0];
$res = null;

/** EOF /**/
