<?php
/**
 * Contient les fonctions relatives à Talus' Works.
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
 * @begin 25/12/2007, Talus
 * @last 24/07/2009, Talus
 * @todo Répartir les fonctions selon la catégorie à laquelle elles appartiennent.
 */

/**
 * Chronomètre
 * 
 * @param integer $t  Temps de départ (0 si on veut lancer le chrono)
 * @return integer
 */
function chrono($t = 0){
    $chrono = microtime(true);
    
    if( $t > 0 ){
        $chrono = abs($chrono - $t);
    }
    
    return $chrono;
}

/**
 * Retourne l'ip du visiteur
 * 
 * @param bool $ip2long Retourner au format ip2long ? (défaut : true)
 * @return integer|string
 */
function get_ip($ip2long = true){
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR']) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP']) && $_SERVER['HTTP_CLIENT_IP']) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
    } else {
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif(getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } else {
            $ip = getenv('REMOTE_ADDR');
        }
    }
    
    if ((bool)$ip2long) {
        $ip = ip2long($ip);
    }
    
    return $ip;
}

/**
 * Envoi un cookie
 * 
 * @param string $name Nom du Cookie
 * @param mixed	$value Valeur de celui-ci
 * @param timestamp $expires Dans combien de temps c'est censé expirer (relatif par rapport à Obj::$date->unix())
 * @return bool
 */
function set_cookie($name, $value = '', $expires = 0){
    return setcookie('talus_works_' . $name,  $value, Obj::$date->unix() + $expires, COOKIE_PATH, DOMAIN_COOKIE, false, true);
}

/**
 * Récupère un cookie
 * 
 * @param string $name Nom du cookie
 * @return mixed
 */
function get_cookie($name){
    return isset($_COOKIE['talus_works_' . $name]) ? $_COOKIE['talus_works_' . $name] : NULL;
}

/**
 * Affiche un message.
 * 
 * @param string $message Message à afficher
 * @param string $url Url à rediriger.
 * @param integer $id_message ID du message
 * @param integer $binary Code binaire du type et du temps de redirection
 * @return void
 */
function message($message, $url = '', $id_message = 0, $binary = 0x42){
    $id_message = abs(intval($id_message));
    
    $types = array(
            0x01 => array('error', 'Message d\'erreur'), 
            0x02 => array('neutral', 'Message d\'information'), 
            0x04 => array('confirm', 'Message de confirmation')
        );
        
    // -- Si on souhaite renseigner des headers supplémentaires, c'est le moment :)
    for ($i = 4, $c = func_num_args(); $i < $c; $i++){
        $arg = func_get_arg($i);
        
        if (is_array($arg)) {
            header($arg[0], true, $arg[1]);
        } else {
            header($arg);   
        }
    }
    
    $time = get_redir_time($binary);
    $msg = $types[$time == 0x00 ? $binary : ($binary ^ $time)];
    
    // -- Si on ne force pas le temps de redirection, on prend le global.
    $time = $time == 0x00 ? Sys::$redirection : $time;
    
    // -- Si on a pas de redirection instantanée... Ou alors les header sont envoyés...
    if ($time ^ MESSAGE_REDIRECTION_INSTANT || headers_sent()) {
        Obj::$tpl->set(array(
                'MESSAGE' => $message,
                'ID_MESSAGE' => $id_message,
                'TITLE' => $msg[1],
                'CLASS_CSS' => 'message_' . $msg[0],
                'URL' => $url,
                'BAN' => false,
                'TIME' => $time ^ MESSAGE_REDIRECTION_INSTANT ? $time : 0
            ));
        
        Obj::$tpl->parse('message.html');
    } else {
        header('Location: ' . DOMAIN_REDIRECT . $url);
    }
    
    // -- De toute facon, on arrete le script ici :).
    exit;
}

/**
 * Marque les sujets d'un forum (ou de tous les forums, ou d'une catégorie) comme lus.
 * 
 * @param integer $id Id du forum à marquer comme lu (0 pour tout).
 * @return void
 */
function markread($id = 0){
    if (Sys::$uid == GUEST) {
        return;
    }
    
    // -- WHERE de la requête pour marquer les forums comme lus.
    $where = '';
    
    // -- Message affiché lors de la redirection, et url de redirection.
    $message = 'Tous les sujets ont bien été marqués comme lus.';
    
    // -- Si on veut un forum spécifique, on sélectionne les sous forums. du forum selectionné.. Puis on ajoute un petit where :D
    if ($id != 0){
        // -- Contient les id de forums à chercher...
        $ids_forums = array();
        
        // -- Récupération de tous les forums enfants du forum demandé.
        $tree = RI::getChildren($id);
        
        foreach ($tree as $forum) {
            $ids_forums[] = $forum['f_id'];
        }
        
        $where = 't_fid IN(' . implode(', ', $ids_forums) . ') AND';
        
        $message = 'Tous les sujets de ' . ($ids_forums[$id]['f_type'] == FORUM ? 'ce forum' : 'cette catégorie') . ' ont été marqués comme lus.';
        $url = ($ids_forums[$id]['f_type'] == FORUM ? 'forum' : 'cat') . '-' . $id . '.html';
    }
    
    $sql =  'SELECT t_fid, t_tid, t_last_pid, fr_posted
                FROM forums_topics
                LEFT OUTER JOIN forums_read ON (forums_topics.t_tid = forums_read.fr_tid)
                        AND (forums_read.fr_uid = :uid)
                WHERE ' . $where . ' (fr_pid IS NULL OR fr_pid < t_last_pid);';
    
    #REQ MARKREAD_1
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
    $res->execute();
    $datas = $res->fetchAll(SQL::FETCH_ASSOC);
    $res = null;
    
    // -- On ne marque quelque chose de lu... Que si y'en a !
    if (count($datas) > 0){    
        /*
         * Astuce : Puisque par la suite, on va parcourir l'array $datas avec un
         * foreach, sous la forme $data, on instancie ces deux variables à null pour
         * pouvoir les lier à la requête préparée. 
         * 
         * Ainsi, on aura juste besoin de lancer l'execution de la requête
         * précédemment préparée :)
         * 
         * (Merci Savageman :D)
         */
        $data = array(
                't_tid' => null,
                't_last_pid' => null,
                'fr_posted' => null
            );
        
        $sql = 'REPLACE INTO forums_read (fr_uid, fr_tid, fr_pid, fr_posted)
                        VALUES (:uid, :tid, :pid, :posted);';
        
        $res = Obj::$db->prepare($sql);
        $res->bindParam(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindParam(':tid', $data['t_tid'], SQL::PARAM_INT);
        $res->bindParam(':pid', $data['t_last_pid'], SQL::PARAM_INT);
        $res->bindParam(':posted', $data['fr_posted'], SQL::PARAM_INT);
        
        foreach ($datas as $data) {
            $res->execute();
        }
        
        $res = null;
    }
    
    message($message, $url, 100, MESSAGE_CONFIRM);
    exit;
}

/**
 * Génére un mot aléatoirement.
 * 
 * @param integer $min Nombre minimum de lettres.
 * @param integer $max Nombre maximum de lettres (vaut 0 si on connait le nombre de lettres)
 * @param string $type Types de charactères à choisir.
 * @return string
 */
function rand_word($min = WORDS_MIN, $max = 0, $type = PASSWORD_ALL){
    $chars = '';
    $word = '';
    $nbr_letters = $min;
    
    // -- Si y'a un maximum, on choisit le nbre de lettres au hasard, entre les deux bornes prescrites.
    if ($max > 0) {
        $nbr_letters = mt_rand($min, $max);
    }
    
    // -- On instantie les types de caractères possibles...
    $ary_chars = array(
            PASSWORD_LOWCASE =>	'abcdefghijklmnopqrstuvwxyz',
            PASSWORD_UPPCASE =>	'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            PASSWORD_NUMERIC =>	'0123456789',
            PASSWORD_SPECIAL =>	'&#@{}[]()-+=_!?;:$%*'
        );
    
    // -- Puis, on parcourt les types de caractères possibles, et si le type demandé contient l'indice d'un type de caractères, on les ajoute à la liste.
    foreach ($ary_chars as $key => $value) {
        if ($key & $type) {
            $chars .= $value;
        }
    }
    
    // -- Calcul du nbre d'elements
    $nbr_elements = strlen($chars) - 1;
    
    // -- Génération du mot aléatoire.
    for ($i = 0; $i < $nbr_letters; $i++){
        $word .= $chars[mt_rand(0, $nbr_elements)];
    }
    
    return $word;
}

/**
 *	Renvoi un captcha (question, réponse, solution)
 * 
 *	@return	array
 */
function rand_captcha(){
    $ary = array(
            'a' => array(
                    'nom' => '',
                    'valeur' => 0
                ),
            
            'b' => array(
                    'nom' => '',
                    'valeur' => 0
                ),
            
            'operateur' => '',
            'solution' => 0
        );
    
    // -- Les chiffres possibles.
    $elements = array(
            'un' => 1,
            'deux' => 2,
            'trois' => 3,
            'quatre' => 4,
            'cinq' => 5,
            'six' => 6,
            'sept' => 7,
            'huit' => 8,
            'neuf' => 9,
            'dix' => 10
        );
    
    // -- Tirage au hasard des deux nombres à utiliser
    $ary['a']['nom'] = array_rand($elements);
    $ary['b']['nom'] = array_rand($elements);
    
    $ary['a']['valeur'] = $elements[$ary['a']['nom']];
    $ary['b']['valeur'] = $elements[$ary['b']['nom']];
    
    // -- Opérations possibles + calcul des solutions.
    $ops = array(
            'plus' => $ary['a']['valeur'] + $ary['b']['valeur'],
            'moins' => $ary['a']['valeur'] - $ary['b']['valeur'],
            'fois' => $ary['a']['valeur'] * $ary['b']['valeur'],
            'modulo' => $ary['a']['valeur'] % $ary['b']['valeur']
        );
    
    $ary['operateur'] = array_rand($ops);
    $ary['solution'] = $ops[$ary['operateur']];
    
    // -- Si on effectue une soustraction, avec $b > $a, alors on inverse a et b.. et on adapte la solution.
    if ($ary['operateur'] == 'moins' && ($ary['b']['valeur'] > $ary['a']['valeur'])){
        $c = $ary['b'];
        $ary['b'] = $ary['a'];
        $ary['a'] = $c;
        
        $ary['solution'] *= -1; 
    }
    
    // -- On épure l'array.
    $ary['a'] = $ary['a']['nom'] . ' ';
    $ary['b'] = $ary['b']['nom'] . ' ';
    $ary['operateur'] .= ' ';
    $ary['captcha'] = trim($ary['a'] . $ary['operateur'] . $ary['b']);
    $ary['solution'] = (string) $ary['solution'];
    
    // -- on retourne le tout :)
    return $ary;
}

/**
 * Teste si toutes les variables passées en arguments sont vides (il faut qu'elles soient définies avant !).
 * 
 * @param mixed $arg,... Liste des variables à tester.
 * @return bool
 * @deprecated
 */
function multi_empty(){
    // -- Pas de parametre.. C'est donc vrai :o
    if (func_num_args() == 0){
        return true;
    }
    
    $args = func_get_args();
    
    // -- On teste chacun des arguments. Si y'en a un vide, on retourne true..
    foreach ($args as $arg){
        if (empty($arg)){
            return true;
        }
    }
    
    return false;
}

/**
 * Affiche un formulaire.
 * 
 * @param string $tpl Template à utiliser..
 * @param bool $captcha Utilisation d'un captcha ?
 * @param string $err_msg Message d'erreur.
 * @return void
 */
function form($tpl, $captcha = FORM_NO_CAPTCHA, $err_msg  = ''){
    Obj::$frame->setTpl($tpl . '.html');
    
    // -- Variables pour le tpl.
    $vars = array();
    $vars['ERR_MSG'] = $err_msg;
    
    foreach (Obj::$frame->getDatas() as $key => $value){
        $vars[strtoupper($key)] = $value;
    }
    
    // -- Instanciation du captcha... Si on le demande ;)
    if ($captcha == FORM_USE_CAPTCHA) {
        $vars['CAPTCHA'] = rand_captcha();
        $_SESSION['captcha'] = $vars['CAPTCHA']['solution'];
    }
    
    Obj::$tpl->set($vars);
}

/**
 * Parse un texte pour l'url rewritting.
 * 
 * @param string $str Texte à parser.
 * @param string $separator Séparateur
 * @return string
 */
function skip_chars($str, $separator = '-'){
    // -- Vivement le support unicode natif de PHP6 §§
    $str = utf8_decode($str);
    
    $str = strtolower($str);
    $str = strtr($str, utf8_decode('ßàáâãäåæçèéêëìíîïðñòóôõöøùúûýýþÿŕ'), 'baaaaaaaceeeeiiiidnoooooouuuyybyr'); 
    
    $str = preg_replace('`[^a-z0-9' . $separator . ']+`', $separator, $str);
    $str = preg_replace('`' . $separator . '{2,}`', $separator, $str);

    $str = trim($str, $separator);
    
    return utf8_encode($str);
}

/**
 * Transforme une chaine de caractères en GD (à revoir)
 * 
 * @param string $str chaine à encoder
 * @param string $filename format du fichier à enregistrer
 * @return bool
 * @todo Recoder
 */
function string2gd($str, $filename = './images/mails/%s-0'){
    $filename = sprintf($filename, sha1($str));
    if (!file_exists($filename . '.png')) {
        $img = imagecreate(strlen($str)*7 + MAIL_PLUS_WIDTH, 15 + MAIL_PLUS_HEIGHT);
        imagecolorallocate($img, 255, 255, 255);
        
        imagestring($img, 3, MAIL_POS_WIDTH, MAIL_POS_HEIGHT, $str, imagecolorallocate($img, 0, 0, 0));
        imagepng($img, $filename . '.png');
    }
    
    return '<img src="' . $filename . '.png" class="mail" alt="" />';
}

/**
 * Gère la navigation à travers les forums.
 * 
 * @param array $more Si on veut ajouter des elements à l'array retourné.
 * @param integer $fid Si on veut utiliser un aforum (pour "ailleurs" ^^')
 * @return array
 */
function forums_nav($more = array(), $fid = 0){
    // -- Vérification de l'argument.
    if (!is_array($more)) {
        $more = array($more => false);
    }
    
    // -- Si Pas d'id, alors une filiation tout con :D
    if (!$fid) {
        return array_merge(array(
                'Index des Forums' => false
            ), $more);
    }
    
    // -- Fil à renvoyer
    $fil = array();
    $filiation = array();
    
    // -- On récupère la filière ascendante du forum.
    Sys::$cache['tree'] = RI::getTree();
    $tree = Sys::$cache['tree'][$fid];
    
    while ($tree['f_parent'] != 0){
        $filiation[] = $tree;
        $tree = Sys::$cache['tree'][$tree['f_parent']];
    }
    
    $filiation[] = $tree;
    $filiation = array_reverse($filiation);
    
    foreach ($filiation as $element){	
        $url = $element['f_type'] == CATEGORY ? 'cat-%s-%s' : 'forum-%s-p1-%s';
        $fil[htmlspecialchars($element['f_name'])] = DOMAIN_REDIRECT . sprintf($url, $element['f_id'], skip_chars($element['f_name'])) . '.html';
    }
    
    $fil = array_merge($fil, $more);
    
    //echo '<pre>' . print_r($fil, true) . '</pre>';
    
    Obj::$frame->setTitle(htmlspecialchars($filiation[count($filiation) - 1]['f_name']) . ' - Les Forums');
    
    return $fil;
}

/**
 *	Formate un nombre (flemme :p)
 * 
 *	@param float $nbre Nombre à afficher
 *	@param integer $decimales  Nombre de décimales à afficher
 *	@return string
 */
function nombres($nbre, $decimales = 0){
    return number_format($nbre, $decimales, ', ', ' ');
}
	
/**
 * Retourne le nombre de sujets non lus pour chaque forums
 * 
 * @return array
 */
function get_read_topics(){
    // -- Si on est invité, rien n'est lu.
    if (Sys::$uid == GUEST) {
        return array();
    }
    
    // -- Si le cache pour les topics lus n'est pas renseigné, on le calcule.
    if (!Sys::$cache['unread']) {
        $fid = 0;
        $parent = array();
        
        $date = new Date('@' . (Obj::$date->unix() - READ_MONTHS * ONE_MONTH), Obj::$date->getTimezone());
        
        // -- Un sujet peut avoir le statut non-lu si on l'a pas lu, et si sa dernière réponse date d'il y a 
        // -- -- moins de READ_MONTHS mois. Si il est trop vieux, on s'en fout, on le considère comme lu <3
        $sql = 'SELECT f_id, f_parent, (
                            SELECT COUNT(*)
                                FROM forums_topics t
                                    LEFT JOIN forums_read r ON r.fr_tid = t.t_tid 
                                        AND r.fr_uid = :uid
                                WHERE t_fid = f_id
                                    AND (fr_pid IS NULL OR fr_pid < t_last_pid)
                                    AND :date <= t_last_time
                        ) AS unread
                    FROM forums
                    WHERE f_read >= :lvl
                    ORDER BY f_left;';
        
        #REQ READ_1
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
        $res->bindValue(':lvl', Sys::$u_level, SQL::PARAM_INT);
        $res->bindValue(':date', $date->sql(), SQL::PARAM_STR);
        $res->execute();
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        unset($date);
        
        /*
         * On explore les forums, et on associe pour chaque catégorie "parente"
         * le nombre de sujets non lus.
         */
        foreach ($datas as $data) {
            $fid = $data['f_id'];
            $parent[$fid] = $data['f_parent'];
            Sys::$cache['unread'][$fid] = $data['unread'];
            
            /* 
             * Une deuxieme boucle pour changer de parents, et ajouter le nombre
             * de sujets non lus à ses parents. 
             */
            while ($parent[$fid] != 0) {
                Sys::$cache['unread'][$parent[$fid]] += Sys::$cache['unread'][$fid];
                $fid = $parent[$fid];
            }
        }
    }
    
    return Sys::$cache['unread'];
}

/**
 * Retourne la fonction SQL qui ajoute le temps, et le formate.
 * 
 * @param string $field Le champ à utiliser.
 * @param string $format Le format à utiliser.
 * @return string
 * @deprecated 
 */
function dateformat($field){
    return $field;
}

/**
 * Morceau de requete pour DATEDIFF (parce que c'est gros >_>)
 * 
 * @param string $field_a Le premier champ à utiliser.
 * @param string $field_b Le deuxième champ à utiliser.
 * @return string
 * @deprecated ?
 */
function datediff($field_a, $field_b){
    return('DATEDIFF(DATE_ADD(' . $field_a. ', INTERVAL \'' . Sys::$time_shift . '\' HOUR_SECOND), DATE_ADD(' . $field_b . ', INTERVAL \'' . Sys::$time_shift . '\' HOUR_SECOND))');
}

/**
 *	Génére la pagination
 * 
 * @param integer $cur Page courante.
 * @param integer $total Nombre total de pages.
 * @param string $url Url préformattée (un un %s) pour la la page.
 * @param string $parent Utilisation d'un bloc parent ?
 * @param integer $flag Quelle pagination utiliser ?
 * @return void
 */
function pagination($cur, $total, $url, $parent = null, $flag = PAGINATION_ALL){
    // -- Le bloc parent :)
    $bloc = ($parent ?($parent . '.') : '') . 'pagination';
    $cur = abs(intval($cur));
        
    // -- Les Pages en elle même.
    $pages = array();
    
    // -- Veut-on afficher les pages precedentes / la premiere page ?
    if ($cur != 1){
        if ($flag & PAGINATION_FIRST_LAST){
            $pages[] = '<a href="' . sprintf($url, 1) . '" class="pagination" title="Première Page">&lt;&lt;</a>';
        }
        
        if (($flag & PAGINATION_PREV_NEXT) && $cur != 0){
            $pages[] = '<a href="' . sprintf($url, ($cur - 1)) . '" class="pagination" title="Page Précédente">&lt;</a>';
        }
    }
    
    // -- Si $cur != 0 (selection de pages), alors on affiche toutes les pages aux alentours de celle ci.
    if ($cur != 0){
        // -- On s'occuppe du commencement de la pagination, et de la fin de celle ci.
        $begin = ( $cur > ( AROUND_PAGE + 1 ) ) ? ($cur - AROUND_PAGE) : 1;
        $end = ( $cur > ( $total - ( AROUND_PAGE ) ) ) ? $total: ( $cur + AROUND_PAGE );
        
        for( $i = $begin; $i <= $end; $i++ ){
            $pages[] = (($i == $cur ) ? ('<span class="pagination current">' . $i . '</span>') : ('<a href="' . sprintf($url, $i) . '" class="pagination">' . $i . '</a>'));
        }
    } else{
        // -- On affiche toutes les pages "que" si y'a un total inférieur à 2 * AROUND_PAGE pages.
        if( $total <= ( 2 * AROUND_PAGE ) ){
            for( $i = 1; $i <= $total; $i++ ){
                $pages[] = '<a href="' . sprintf($url, $i) . '" class="pagination">' . $i . '</a>';
            }
        } else{
            for( $i = 1; $i <= AROUND_PAGE; $i++ ){
                $pages[] = '<a href="' . sprintf($url, $i) . '" class="pagination">' . $i . '</a>';
            }
            
            $pages[] = '<span class="pagination">...</span>';
            
            for( $i = (AROUND_PAGE - 1); $i >= 0; $i-- ){
                $pages[] = '<a href="' . sprintf($url, ($total - $i)) . '" class="pagination">' . ($total - $i) . '</a>';
            }
        }
    }
    
    if ($cur < $total){
        // -- Veut-on afficher les pages suivantes / la dernière page ?
        if( ($flag & PAGINATION_PREV_NEXT) && $cur){
            $pages[] = '<a href="' . sprintf($url, ($cur + 1)) . '" class="pagination" title="Page Suivante">&gt;</a>';
        }
        
        if ($flag & PAGINATION_FIRST_LAST){
            $pages[] = '<a href="' . sprintf($url, $total) . '" class="pagination" title="Dernière Page (' . $total . ')">&gt;&gt;</a>';
        }
    }
    
    foreach ($pages as $page) {
        Obj::$tpl->setBlock($bloc, 'PAGE', $page);
    }
}

/**
 * Ajoute les liens rapides pour la navigation à travers les pages.
 * 
 * @param integer $cur Page courante
 * @param integer $total Total de pages
 * @param string $url URL à formatter
 * @return void
 */
function get_page_links($cur, $total, $url){
    $cur = (int) $cur;
    $tot = (int) $total;
    
    // -- Le tout est valable que si on a plus d'une page !
    if ($tot > 1){
        // -- Première page
        Obj::$tpl->setBlock('orphan_tags', 'NAME', 'link ');
        
        Obj::$tpl->setBlock('orphan_tags.attr', array(
                'NAME' => 'rel',
                'VALUE' => 'first'
            ));
            
        Obj::$tpl->setBlock('orphan_tags.attr', array(
                'NAME' => 'href',
                'VALUE' => sprintf($url, 1)
            ));
        
        // -- Page précédente
        if ($cur > 1){
            Obj::$tpl->setBlock('orphan_tags', 'NAME', 'link ');
            
            Obj::$tpl->setBlock('orphan_tags.attr', array(
                    'NAME' => 'rel',
                    'VALUE' => 'previous'
                ));
                
            Obj::$tpl->setBlock('orphan_tags.attr', array(
                    'NAME' => 'href',
                    'VALUE' => sprintf($url, ($cur - 1))
                ));
        }
        
        // -- Page suivante
        if ($cur < $tot){
            Obj::$tpl->setBlock('orphan_tags', 'NAME', 'link ');
            
            Obj::$tpl->setBlock('orphan_tags.attr', array(
                    'NAME' => 'rel',
                    'VALUE' => 'next'
                ));
                
            Obj::$tpl->setBlock('orphan_tags.attr', array(
                    'NAME' => 'href',
                    'VALUE' => sprintf($url, ($cur + 1))
                ));
        }
            
        // -- Dernière page
        Obj::$tpl->setBlock('orphan_tags', 'NAME', 'link ');
                
        Obj::$tpl->setBlock('orphan_tags.attr', array(
                'NAME' => 'rel',
                'VALUE' => 'last'
            ));
            
        Obj::$tpl->setBlock('orphan_tags.attr', array(
                'NAME' => 'href',
                'VALUE' => sprintf($url, $tot)
            ));
    }
}

/**
 * Gère le BBCode.
 * 
 * @param string $string Chaine à parser.
 * @param integer $type Les types de bbcode à parser
 * @return string
 */
function bbcode($string, $type = BBCODE_ALL){
    if (!$string) {
        return $string;
    }
    
    $string = linebreaks(htmlspecialchars($string));
    
    // -- On parse les balises "code" (note :: faire un "meilleur" colorateur syntaxique... Si pas la flemme >.<)
    $string = preg_replace_callback('`\[code(?:=(php|xml|tpl))?](.+?)\[/code]`s', 'parse_codes', $string);
    
    // -- Balises "basiques".
    $string = preg_replace('`\[b](.+?)\[/b]`s', '<strong>$1</strong>', $string);
    $string = preg_replace('`\[i](.+?)\[/i]`s', '<em>$1</em>', $string);
    $string = preg_replace('`\[u](.+?)\[/u]`s', '<span class="souligne">$1</span>', $string);
    $string = preg_replace('`\[s](.+?)\[/s]`s', '<span class="barre">$1</span>', $string);
    $string = preg_replace('`\[titre](.+?)\[/titre]`s', '<h3 class="titre">$1</h3>', $string);
    $string = preg_replace('`\[soustitre](.+?)\[/soustitre]`s', '<h4 class="sous_titre">$1</h4>', $string);
    $string = preg_replace('`\[img]((?:ht|f)tps?://(?:[a-zA-Z0-9./_-]{3,}))\[/img]`s', '<img src="$1" alt="Image" />', $string);
    
    // -- Balises plus complexes ?
    $string = preg_replace_callback('`\[url]((?:ht|f)tps?://(?:.{3,}?))\[/url]`s', 'shorten_urls', $string);
    $string = preg_replace('`\[url=((?:ht|f)tps?://(?:.{3,}?))](.+?)\[/url]`s', '<a href="$1" title="$1">$2</a>', $string);
    
    while (preg_match('`\[quote(?:=([^]]+))?](.+?)\[/quote]`s', $string)) {
        $string = preg_replace_callback('`\[quote(?:=([^]]+))?](.+?)\[/quote]`s', 'parse_quotes', $string);
    }
    
    // -- Balises de script.
    $string = preg_replace_callback('`\[file=([0-9]+)(?: /|]\[/file)]`', 'parse_file', $string);
    
    $string = str_replace(array('&#091', '&#093'), array('[', ']'), $string);
    
    return $string;
}

/**
 * Supprime le BBCode d'une chaine de caractères
 *
 * @param string $str
 * @return string
 */
function void_bbcode($str){
    if (!$str) {
        return $str;
    }
    
    $str = remove_linebreaks(htmlspecialchars($str));
    
    // -- On parse les balises "code" (transformation des [] en &#91 et &#93)
    $str = preg_replace_callback('`\[code(?:=(?:php|xml|tpl))?](.+?)\[/code]`s', 'remove_parse_code', $str);
    
    // -- Balises "basiques".
    $str = preg_replace(array(
            '`\[b](.+?)\[/b]`s', 
            '`\[i](.+?)\[/i]`s', 
            '`\[u](.+?)\[/u]`s', 
            '`\[s](.+?)\[/s]`s', 
            '`\[titre](.+?)\[/titre]`s', '`\[soustitre](.+?)\[/soustitre]`s', 
            '`\[img]((?:ht|f)tps?://(?:[a-zA-Z0-9./_-]{3,}))\[/img]`s', 
            '`\[url]((?:ht|f)tps?://(?:.{3,}?))\[/url]`s'), '$1', $str);

    $str = preg_replace('`\[url=((?:ht|f)tps?://(?:.{3,}?))](.+?)\[/url]`s', '$2 [$1]', $str);
    
    while(preg_match('`\[quote(?:=([^]]+))?](.+?)\[/quote]`s', $str)){
        $str = preg_replace('`\[quote(?:=([^]]+))?](.+?)\[/quote]`s', '$2', $str);
    }
    
    // -- Balises de script.
    $str = preg_replace('`\[file=([0-9]+) /]`', 'http://www.talus-works.net/download-$1.html', $str);
    
    $str = str_replace(array('&#091', '&#093'), array('[', ']'), $str);
    
    return $str;
}

/**
 * Parse les balises attach
 * 
 * @param array $matches Matches de la capture des regex
 * @return string
 */
function parse_file($matches){
    // -- On récupère les données des attachements
    get_attach();
    
    // -- On ne retourne un truc viable que si le fichier existe !
    if( isset(Sys::$cache['attach'][$matches[1]]) ){
        return '<a href="' . DOMAIN_REDIRECT . 'download-' . Sys::$cache['attach'][$matches[1]]['s_id'] . '-' . skip_chars(Sys::$cache['attach'][$matches[1]]['s_nom'] . '-' . Sys::$cache['attach'][$matches[1]]['s_version']) . '.html">' . htmlspecialchars(Sys::$cache['attach'][$matches[1]]['s_nom']) . ' - ' . Sys::$cache['attach'][$matches[1]]['s_version'] . '</a> (<strong>Taille :</strong> ' . Sys::$cache['attach'][$matches[1]]['s_size'] . '; Téléchargé ' . Sys::$cache['attach'][$matches[1]]['s_hits'] . ' fois)';
    }
    
    return '';
}

/**
 * Callback de [code]
 * 
 * @param array $matches
 * @return string
 */
function parse_codes(array $matches){
    $source = remove_linebreaks(htmlspecialchars_decode($matches[2]));
    
    $code = '<div class="code_overall">
            <span class="code_top">Code %s</span>
            <code class="code_main%s">%s</code>
        </div>';
    
    if ($matches[1] == 'php'){
        $source =  substr(highlight_string(trim($source), true), 6, -7);
        $source = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;', '<span class="highlight_tabs">&nbsp;&nbsp;&nbsp;&nbsp;</span>', $source);
        //$source = str_replace(array('<br />', '<br>'), '', $source);
        
        $type = 'php';
    }
    elseif ($matches[1] == 'xml' || $matches[1] == 'tpl') {
        //$source = highlight_tpl($source, $matches[1] == 'tpl', false); // Buggé ?
        $source = highlight_tpl($source, false, false);
        $type = $matches[1];	
    }
    else {
        $source = htmlspecialchars($source);
        $type = '';
    }
    
    $source = str_replace(array('[', ']'), array('&#91;', '&#93;'), $source);
    
    return sprintf($code, strtoupper($type), (!empty($type) ? ' code_' . $type : ''), $source);
}

/**
 * Parse le contenu des [code], pour... virer :)
 *
 * @param array $matches
 * return string
 */
function remove_parse_code(array $matches){
    return str_replace(array('[', ']'), array('&#91', '&#93'), $matches[1]);
}

/**
 * Parse les citations
 * 
 * @param array $matches Captures des regex
 * @return string
 */
function parse_quotes($matches = array()){
    $string = 'Citation';
    
    if (!empty($matches[1])) {
        $string = '%s a écrit';
        
        // -- plusieurs arguments ?
        if (strpos(trim($matches[1], ','), ',') !== false){
            // -- $from contient l'auteur, $id contient l'id du post.
            list($from, $id) = explode(',', trim($matches[1], ','));
            $matches[1] = empty($from) ? 'Anonyme' : $from;
            
            $string = '<a href="topic-post-' . $id . '.html#p' . $id . '">' . $string . '</a>';
        }
        
        $string = sprintf($string, $matches[1]);
    }
    
    
    // -- On formate la chaine de texte....
    $string = '<div class="quote_overall">
            <span class="quote_top">' . $string . ' :</span>
            <blockquote class="quote_main"><p>' . trim($matches[2]) . '</p></blockquote>
        </div>';
    
    // -- On retourne le tout :)
    return $string;
}

/**
 * Transforme les liens directs en url cliquables par la suite (à revoir)
 * 
 * @param string $txt Chaine à parser
 * @return string
 */
function parse_direct_urls($txt){
    return preg_replace('`(?<![]"=])((?:ht|f)tps?://(?:[a-zA-Z0-9./_-]{3,}))`i', '[url]$1[/url]', $txt);
}

/**
 * Raccourcis les urls trop longues (à revoir)
 * 
 * @param array $match Tableau de capture.
 * @return string
 */
function shorten_urls($match){
    $url = $match[1];
    
    if (strlen($match[1]) > URLS_MAX) {
        $url = substr($match[1], 0, URLS_NB_FIX) . URLS_SEPARATOR . substr($match[1], URLS_NB_FIX * (-1));
    }
    
    return '<a href="' . $match[1] . '" title="' . $match[1] . '">' . $url . '</a>';
}

/**
 * Détermine si un utilisateur est banni.
 * 
 * @return bool
 */
function is_banned(){
    // -- Si des donées utilisateurs existent, on vérifie le groupe du membre...
    if (Sys::$uid != GUEST && Sys::$u_data['u_level'] == GRP_BANNED) {
        set_cookie('ban', true, ONE_YEAR);
        return true;
    }
    
    // -- Requête de vérification !
    $sql = 'SELECT b_valid
                FROM banned
                WHERE b_uid = :uid
                    OR b_uip = :uip
                ORDER BY b_date DESC
                LIMIT 1;';
    
    #REQ BAN_1
    $res = Obj::$db->prepare($sql);
    $res->bindValue(':uid', Sys::$uid, SQL::PARAM_INT);
    $res->bindValue(':uip', Sys::$ip, SQL::PARAM_INT);
    $res->execute();
    $data = $res->fetch(SQL::FETCH_ASSOC);
    $res = null;
    
    // -- On a trouvé des données...
    if ($data) {
        // -- Ban valide ? Si oui, on renouvelle le cookie. Sinon, on le détruit...
        if ($data['b_valid']) {
            set_cookie('ban', true, ONE_YEAR);
            return true;
        } else {
            set_cookie('ban', false, -ONE_MINUTE);
            return false;
        }
    }
    
    // -- Sinon, on regarde le cookie.
    return get_cookie('ban');
}

/**
 * Récupère les données des attachements
 * 
 * @param boolean $update Forcer la mise à jour ?
 * @return void
 */
function get_attach($update = false){
    if (Sys::$cache['attach'] == array() || $update) {
        $sql = 'SELECT s_id, s_nom, s_hits, s_version, s_path
                    FROM scripts';
        
        #REQ ATTACH_1
        $res = Obj::$db->query($sql, 'ATTACH', 1, E_USER_ERROR);
        $datas = $res->fetchAll(SQL::FETCH_ASSOC);
        $res = null;
        
        foreach ($datas as $data) {
            Sys::$cache['attach'][$data['s_id']] = $data;
            Sys::$cache['attach'][$data['s_id']]['s_size'] = get_size(filesize('../downloads/' . $data['s_path']));
        }
    }
}

/**
 * Convertit la taille d'un fichier, et la préformate correctement.
 * 
 * @param integer $size Taille en octets du fichier
 * @return string
 */
function get_size($size){
    $format = '';
    
    $possibilities = array('Ki', 'Mi', 'Gi', 'Ti');
    
    $i = 0;
    while ($size >= 1024 && isset($possibilities[$i])){ 
        $size /= 1024;
        $format = $possibilities[$i++];
    }
    
    return nombres($size, 3) . ' ' . $format . 'o';
}

/**
 * Génère une jumpbox.
 * 
 * @param integer $id Id du forum en cours : Vaut 0 si pas de sélection.
 * @param bool $redirect Est-on en pleine redirection ?
 * @return void
 */
function make_jumpbox($id = 0, $redirect = false){
    $id = abs(intval($id));
    
    if ($redirect) {
        if (ctype_digit($_POST['jump_to'])) {
            $url = 'forum-' . $_POST['jump_to'] . '.html';
        } else {
            $url = $_POST['jump_to'];
        }
        
        header('Location: ' . $url);
        exit;
    }
    
    // Récupération de l'arbre.
    Sys::$cache['tree'] = RI::getTree();
    
    foreach (Sys::$cache['tree'] as $node) {
        if ($node['f_read'] < Sys::$u_level) {
            continue;
        }
        
        $bloc = 'jumpbox_cat' . ($node['f_type'] == CATEGORY ? '' : '.forums');
        
        Obj::$tpl->setBlock($bloc, array(
                'ID' =>$node['f_id'],
                'NAME' => htmlspecialchars($node['f_name']),
                'LEVEL' => str_repeat('&nbsp;&nbsp;&nbsp;|', (($node['f_level'] - 1) < 0 ? 0 : ($node['f_level'] - 1))),
                'TYPE' => $node['f_type']
            ));
    }
    
    Obj::$tpl->set('CURRENT_JPBX', $id);
}

/**
 * Colorateur Syntaxique XML (et Talus' TPL au passage :3) (à revoir)
 * 
 * @param string $string	Chaine à colorer
 * @param bool $tpl Coloration TPL ?
 * @param bool $inclus Parser les inclusions ?
 * @return bool|string
 */
function highlight_tpl($string, $tpl = true, $inclus = false){
    $string = htmlspecialchars($string);
    $string = str_replace(array('\\\'', '\\&quot;'), array('\\&#039;', '\\&#034;'), $string);
    
    // -- Les commentaires XML
    $string = preg_replace('`&lt;!(?!\[CDATA\[)(?:--|\[).+?(?://)?(?:--)?&gt;`s', '<span class="highlight_xml_comments">$0</span>', $string);
    
    // -- Les CDATAs
    $string = preg_replace('`&lt;!\[CDATA\[.+?]]&gt;`s', '<span class="highlight_xml_cdata">$0</span>', $string);
    
    preg_match_all('`&lt;(?!!).+?&gt;`s', $string, $matches);
    
    foreach($matches[0] as $match ){
        $new_match = $match;
        $new_match = preg_replace('`(\b[\w:-]+\b)=`s', '<span class="highlight_word">$1</span>=', $new_match);
        
        preg_match_all('`(&quot;|\').+?\1`s', $new_match, $quotes_matches);
        
        foreach( $quotes_matches[0] as $quote_match ){
            $quote = $quote_match;
            
            if( (bool) $tpl ){
                $quote = str_replace('{', '<strong>{%', $quote); 
                $quote = str_replace('}', '}</strong>', $quote); 
            }
            
            $new_match = str_replace($quote_match, '<span class="highlight_quotes">' . $quote . '</span>', $new_match);
        }
        
        $string = str_replace($match, '<span class="highlight_tags">' . $new_match . '</span>', $string);
    }
    
    // -- On souhaite coloriser en TPL ?
    if ((bool) $tpl) {
        // -- On remplace les inclusions.... Que si on le souhaite.
        if ((bool) $inclus) {
            $string = preg_replace('`&lt;include <span class="highlight_word">tpl</span>=<span class="highlight_quotes">&quot;(.+?).html&quot;</span>( <span class="highlight_word">once</span>=<span class="highlight_quotes">&quot;(?:true|false)&quot;</span>)? /&gt;`s', '&lt;include <span class="highlight_word">tpl</span>=<span class="highlight_quotes">&quot;<a href="wall.html?type=html&amp;file=$1.html">$1.html</a>&quot;</span>$2 /&gt;', $string);
        }
        
        // -- Les tags de variables ({VAR}, {$bloc.VAR}, etc)
        $string = preg_replace('`\{\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.,]*(?:\[(?!]})(?:.*?)])?}`s', '<span class="highlight_brackets_return">$0</span>', $string);
        $string = preg_replace('`\{(?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.,]*)(?:\[(?!]})(?:.*?)])?}`s', '<span class="highlight_brackets">$0</span>', $string);
        
        // -- Hop, on retourne les echappements à la normale !
        $string = str_replace('{%', '{', $string);
        
        // -- Les commentaires...
        $string = preg_replace('`/\*.+?\*/`s', '<span class="highlight_comments">$0</span>', $string);
    }
    
    // -- Les tabulations.
    $string = preg_replace('`\t+\n`', "\n", $string);
    $string = str_replace("\t",  '<span class="highlight_tabs">&nbsp;&nbsp;&nbsp;&nbsp;</span>', $string);
    
    return $string;
}

/**
 *	Si la fonction checkdnsrr n'est pas définie (ex : Windows), on la redéfinie.
 * 
 *	@param string $domain Domaine à vérifier
 *	@return true
 */
if (!function_exists('checkdnsrr')) {
    function checkdnsrr(){
        return true;
    }
}


/**
 * Ajoute une ligne de script JS.
 * 
 * @param array $lines Ligne(s) de code à insérer.
 * @return void
 */
function js($lines){
    $lines = (array) $lines;
    
    foreach ($lines as $line) {
        Obj::$tpl->setBlock('js', 'LINE', $line);
    }
}

/**
 * Génère des onglets.
 * 
 * @param array $tabs Array associatif pour les tabs (tab (lien)=> array(visibilité, nom visible, url))
 * @param mixed $current Tab selectionnée.
 * @return void
 */
function get_tabs($tabs = array(), $current = 0){
    $tabs = (array) $tabs;
    
    // -- Parcourt de la boucle...
    foreach ($tabs as $tab => $title){
        if ($title[TABS_STATUS]){
            Obj::$tpl->setBlock('tabs', array(
                    'TAB' => $tab,
                    'TITLE' => $title[TABS_TITLE],
                    'URL' => $title[TABS_URL]
                ));
        }
    }
    
    // -- Puis, on indique l'onglet couramment utilisé.
    Obj::$tpl->set('SELECTED_TAB', $current);
}

/**
 * Parse une date
 *
 * @param mixed $timestamp Timestamp de la date à parser
 * @param string $format Format de la date ("default" par défaut)
 * @param boolean $hours Afficher les heures ?
 * @param boolean $relative Dates relatives ?
 * @return string
 */
function parse_date($timestamp, $format = 'default', $hours = true, $relative = true){
    // -- Formattage du décalage horaire, en format "intelligible"
    $decal = Sys::$decal['sign'] . Sys::$decal['h'] . ' hours ' . Sys::$decal['sign'] . Sys::$decal['m'] . ' minutes';
    
    // -- Date d'aujourd'hui
    $now = new Date(NOW + (Sys::$utc + Sys::$dst) * ONE_HOUR, Obj::$date->getTimeZone());
    
    // -- Trois manières de définir $cur : $timestamp peut soit etre un objet Date, soit une chaine de caractère, soit un timestamp.
    if ($timestamp instanceof Date){
        $cur = clone $timestamp;
    } else {
        $cur = new Date(!ctype_digit((string) $timestamp) ? ($timestamp . ' ' . $decal) : ($timestamp + (Sys::$utc + Sys::$dst) * ONE_HOUR), Obj::$date->getTimezone());
    }
    
    // -- On exige un format particulier ? on le retourne, et "pi f'est tout" !
    if ($format !== null && $format != 'default'){
        return $cur->format($format);
    }
    
    $clone_cur = clone $cur;
    
    $clone_cur->setTime(7, 7, 7); // Pourquoi 07h07m07s ? "Parce que" :3.
    $now->setTime(7, 7, 7);
    
    $diff = $now->unix() - $clone_cur->unix();
    
    unset($clone_cur);
    
    // -- Calcul du jour d'aujourd'hui, du jour du timestamp.. Si on veut afficher le format "extra".
    if ($relative && $diff < (2 * ONE_DAY)){
        if ($diff < ONE_DAY) {
            $str = 'Aujourd\'hui';
        } else {
            $str = 'Hier';
        }
    } else {
        $str = 'Le ' . $cur->format('d/m/Y');
    }
    
    // -- Si on souhaite afficher les heures...
    if ($hours) {
        // -- Gestion des heures "super relatives" (il y a une heure, X minutes, Y secondes, ....)
        if ($relative && $diff < ONE_DAY){
            // -- A cause de la gestion du décalage horaire, clone Obj::$date ne marche pas.
            $now = new Date(NOW + (Sys::$utc + Sys::$dst) * ONE_HOUR, Obj::$date->getTimeZone());
            $diff = $now->unix() - $cur->unix();
    
            //if (ctype_digit($timestamp)) echo $timestamp . ' : ' . $diff . ' : ' . (TIME_RELATIVE_HOURS * ONE_HOUR) . '<br />';
             
            if ($diff < ONE_MINUTE) {
                $str = 'Il y a ' . $diff . ' seconde' . ($diff != 1 ? 's' : '');
            } elseif ($diff < ONE_HOUR){
                $nbm = floor($diff / ONE_MINUTE);
                $str = 'Il y a ' . $nbm . ' minute' . ($nbm != 1 ? 's' : '');
            } elseif ($diff < (TIME_RELATIVE_HOURS * ONE_HOUR)) {
                $nbh = floor($diff / ONE_HOUR);
                $nbm = floor(($diff - ($nbh * ONE_HOUR)) / ONE_MINUTE);
                $str = 'Il y a ' . $nbh . ' heure' . ($nbh != 1 ? 's' : '') . ' et ' . $nbm . ' minute' . ($nbm != 1 ? 's' : '');
            } else {
                $str .= ', à ' . $cur->format(DATE_TIME);
            } 
        } else {
            $str .= ', à ' . $cur->format(DATE_TIME);
        }
    }
    
    // -- Libérons un peu de mémoire !
    unset($cur);
    unset($now);
    
    return $str;
}

/**
 * Transforme une heure flottante (1.3) au format HH:MM:SS
 *
 * @param float $time Décalage à transformer (1.3, etc)
 * @return string
 */
function float_to_time($time){
    $sign = $time >= 0 ? '+' : '-';
    
    $time = abs((float)$time);
    $h = floor($time);
    
    $time = ($time - $h) * 60;
    $m = floor($time);
    
    $s = floor(($time - $m) * 60);
        
    return array(
            'h' => str_pad($h, 2, '0', STR_PAD_LEFT), 
            'm' => str_pad($m, 2, '0', STR_PAD_LEFT), 
            's' => str_pad($s, 2, '0', STR_PAD_LEFT), 
            'sign' => $sign
        );
}

/**
 * Récupère le temps de redirection
 * 
 * @param integer $bit Nombre binaire à filtrer
 * @return integer
 */
function get_redir_time($bit){
    // -- Exploration de tout l'array ; Si on y trouve le type, on le retourne.
    foreach (array(MESSAGE_REDIRECTION_DISABLED, MESSAGE_REDIRECTION_INSTANT, MESSAGE_REDIRECTION_ENABLED) as $type){
    	if ($bit & $type){
    	    return $type;
    	}
    }
    
    // -- Rien n'a été trouvé ; on retourne alors 0x00
    return 0x00;
}

/**
 * Parse une chaine  ; deux sauts de lignes ==> un paragraphe, et un saut de ligne
 * donnera un simple... saut de ligne (<br />). 
 * Adapté de python à php d'après le package "utils.html" de Django.
 *
 * @param string $str Texte à parser
 * @return string
 */
function linebreaks($str){
    $str = nl2br($str);
    $lines = preg_split('`(?:<br />(?:\r\n|\r|\n)){2,}`si', $str);
    
    $str =  '<p>' . implode('</p>' . PHP_EOL . PHP_EOL . '<p>', $lines) . '</p>';
    
    // -- Suppression pour les balises codes, citations
    //$str = preg_replace('`<br />((?:\r\n|\r|\n)<div class="(?:quote|code)_overall">)`si', '</p>$1', $str);
    
    return $str;
}

/**
 * Vire les sauts de lignes créés par linebreaks().
 *
 * @param string $str
 * @return string
 */
function remove_linebreaks($str){
    return str_replace(array('<p>', '</p>', '<br />'), '', $str);
}

/**
 * Coupe une chaine de caractères (sans interrompre un mot)
 *
 * @param string $str chaine à couper
 * @param integer $max nombre maximum de caractères
 * @param string $finish chaine de caractère à appliquer en fin si $str est coupée.
 * @return string
 */
function cut_str($str, $max = 50, $finish = '...'){
    if (strlen($str) <= $max){
        return $str;
    }
    
    $max = intval($max) - strlen($finish);
    
    /* 
     * On coupe la chaine au nombre de caractères... +1, et on récupére toute
     * la chaine... jusqu'au dernier caractère blanc.
     */
    $str = substr($str, 0, $max + 1);
    $str = strrev(strpbrk(strrev($str), " \t\n\r\0\x0B"));

    return rtrim($str) . $finish;
}

/** EOF /**/
