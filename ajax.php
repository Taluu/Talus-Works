<?php
/**
 * Liste des évènements AJaX de Talus' Works
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
 * @begin 01/02/2008, Talus
 * @last 11/04/2009, Talus
 */

// -- Qqs constantes
define('ROOT', './');
define('PHP_EXT', substr(__FILE__, strrpos(__FILE__, '.')+1));
define('COMMON', true);

// -- Inclusion du démarrage de tout le schmilblik
include(ROOT . 'includes/start.php');

// On force l'encodage, au cas ou, et on vire le cache, et on force un encodage en utf8
header('Content-Type: text/html;charset=utf8');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Expires: 0');
header('Pragma: no-cache');

/**
 * Retourne le balisage d'une citation d'un post.
 * 
 * @return string
 */
function AJaX_quote(){
    $pid = intval($_GET['pid']);
    
    $sql = 'SELECT p_content,
                u_login
            FROM forums_posts p
                LEFT JOIN users u ON p.p_uid = u.u_id
            WHERE p_pid = :pid;';
    
    #REQ AJAX_1
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':pid', $pid, SQL::PARAM_INT);
    $res->execute();
    
    $data = $res->fetch(SQL::FETCH_ASSOC);
    $res = null;
    
    if (!$data) {
        return null;
    }
    
    $data = '[quote=' . $data['u_login'] . ',' . $pid . ']' . $data['p_content'] . '[/quote]';
    
    // -- Pour le formulaire pré rempli.
    $_SESSION['quote'] .= $data;
    
    return $data;
}

/**
 * Récupère les infos d'un post, pour l'éditer via un formulaire "edit_in_place".
 * 
 * @return string
 */
function AJaX_edit(){
    $pid = intval($_GET['pid']);
    
    if (!$pid) {
        return null;
    }
    
    // -- On selectionne les infos du post selectionné
    $sql = 'SELECT p_content, p_uid,
                t_tid, t_title, t_closed, t_description, t_first_pid,
                f_modo
            FROM forums_posts p
                LEFT JOIN forums_topics t ON p.p_tid = t_tid
                LEFT JOIN forums f ON t.t_fid = f_id
            WHERE p_pid = :pid;';
    
    #REQ AJAX_2
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':pid', $pid, SQL::PARAM_INT);
    $res->execute();
    
    $data = $res->fetch(SQL::FETCH_ASSOC);
    $res = null;
    
    // -- Vérification des données, puis envoi de celles-ci si elles sont correctes
    if (!$data) {
        return null;
    }
    
    if (($data['p_uid'] != Sys::$uid || $data['t_closed']) && $data['f_modo'] < Sys::$u_level) {
        return null;
    }
    
    Obj::$tpl->set(array(
            'PID' => $pid,
            'TYPE' => (Sys::$u_level <= $data['f_modo'] && $pid == $data['t_first_pid']) ? 'text' : 'hidden',
            'TITLE' => htmlspecialchars($data['t_title']),
            'DESCRIPTION' => htmlspecialchars($data['t_description']),
            'CONTENT' => htmlspecialchars($data['p_content'])
        ));
    
    //return str_replace('&', '&amp;', Obj::$tpl->pparse('forums/edit_in_place.html'));
    return Obj::$tpl->pparse('forums/edit_in_place.html');
}


/**
 * Envoi le formulaire d'eip.... Et retourne ce qu'il faut afficher à la place du formulaire eip.
 * 
 * @return string
 */
function AJaX_submit_eip(){
    $pid = intval($_GET['pid']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $content = trim($_POST['edit']);
    $err = array();
    
    if (!$pid) {
        return null;
    }
    
    $sql = 'SELECT p_pid, t_tid, f_modo
                FROM forums_posts p
                    LEFT JOIN forums_topics t ON p.p_tid = t.t_tid
                    LEFT JOIN forums f ON t.t_fid = f.f_id
                WHERE p_pid = :pid;';
    
    #REQ AJAX_3
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':pid', $pid, SQL::PARAM_INT);
    $res->execute();
    
    $data = $res->fetch(SQL::FETCH_ASSOC);
    $res = null;
    
    // -- Vérification des données
    if (!$data) {
        $err[] = 'Aucune donnée trouvée...';
    }
    
    if (multi_empty($title, $content)) {
        $err[] = 'Erreur : Le contenu ' . (Sys::$u_level <= $data['f_modo'] ?  '(ainsi que le titre)':'') . ' est obligatoire !';
    }
    
    if (strlen($content) < MIN_LENGTH) {
        $err[] = 'Erreur : Le contenu que vous avez spécifié est trop court !';
    }
    
    if (!$err) {
        // -- On met à jour le message et le sujet
        $sql = 'UPDATE forums_posts
                    SET p_content = :content, p_edit_uid = :edit_uid, p_edit_times = p_edit_times + 1, p_edit_date = :date
                    WHERE p_pid = :pid;';
        
        #REQ AJAX_4
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':content', parse_direct_urls($content), SQL::PARAM_STR);
        $res->bindValue(':date', Obj::$date->sql(), SQL::PARAM_STR);
        $res->bindValue(':edit_uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':pid', $pid, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        $sql = 'UPDATE forums_topics
                    SET t_title = :title, t_description = :description
                    WHERE t_tid = :tid;';
        
        #REQ AJAX_5
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':title', $title, SQL::PARAM_STR);
        $res->bindValue(':description', $description, SQL::PARAM_STR);
        $res->bindValue(':tid', intval($data['t_tid']), SQL::PARAM_INT);
        $res->execute();
        $res = null;
    } else {
        $content = implode("<br />\n", $err);
    }
    
    // -- Les niveaux.
    $levels = array('grp_admin', 'grp_modo', 'grp_user', 'grp_ls', 'grp_guest', 'grp_banned');
    
    // -- Génération d'un XML (pour transférer plusieurs données)
    $dom = new DOMDocument('1.0', 'utf8');
    
    $root = $dom->createElement('root');
    
    $ary_elements = array(
            'titre' => $title, 
            'description' => $description, 
            'message' => bbcode($content),
            'author' => '<a href="profile-' . Sys::$uid . '-' . skip_chars(Sys::$u_data['u_login']) . '.html" class="' . $levels[Sys::$u_data['u_level']] . '">' . htmlspecialchars(Sys::$u_data['u_login']) . '</a>'
        );
    
    foreach ($ary_elements as $element => $value){
        $item = $dom->createElement($element);
        $item->appendChild($dom->createCDATASection($value));
        $root->appendChild($item); 
    }
    
    $dom->appendChild($root);
    
    return $dom->saveXML();
}

/**
 * Génère la prévisualisation AJaX d'un message.
 * 
 * @return string
 */
function AJaX_prev(){
    $reply = trim($_POST['reply']);
    
    // -- On fait qqch que si $reply est pas vide :)
    return !empty($reply) ? bbcode($reply) : null;
}

// -- Instanciation de la liste des évenements...
AJaX::add(AJaX::TXT, 'quote');
AJaX::add(AJaX::TXT, 'edit');
AJaX::add(AJaX::XML, 'submit_eip');
AJaX::add(AJaX::TXT, 'prev');

// -- Déclenchement de l'évenement :)
AJaX::trigger($_GET['mode']);

/** EOF /**/
