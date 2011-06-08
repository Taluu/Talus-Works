<?php
/**
 * Gère les Pseudos Frames de Talus' Works.
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
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 3+
 * @begin 25/12/2007, Talus
 * @last 17/07/2009, Talus
 */

// -- Qqs constantes
define('ROOT', dirname(__FILE__) . '/');
define('PHP_EXT', substr(__FILE__, strrpos(__FILE__, '.')+1));
define('COMMON', true);

// -- Inclusion du démarrage de tout le schmilblik
include('includes/start.php');

/**
 * Gère les Pseudos Frames de Talus' Works.
 *
abstract class Frame {
 * @abstract
 */
abstract class Frame {
    /**
     * La frame en cours d'utilisation
     * 
     * @var string
     * @static
     */
    protected static $frame = 'index';
    
    /**
     * TPL a utiliser par le parse() final. Si vide, dernier tpl utilisé.
     *
     * @var string
     */
    private $_tpl = '';
    
    /**
     * Le titre de la frame en cours d'utilisation
     * 
     * @var string
     */
    private $_title = 'Index';
    
    /**
     * Description du site (meta desc)
     *
     * @var string
     */
    private $_desc = 'Les travaux de Talus (Talus\' Works, Talus\' TPL, ...)';
    
    /**
     * Les fichiers JS à utiliser
     * 
     * @var array
     */
    private $_js = array();
    
    /**
     * Le fil d'arianne
     * 
     * @var array
     */
    private $_fil = array();
    
    /**
     * Les flux RSS
     * 
     * @var array
     */
    private $_rss = array();
    
    /**
     * Contient des données à manipuler. Au cas ou :)
     * 
     * @var array
     */
    protected $data = array();

    /**
     * Contient le nom des paramètres
     *
     * @var array
     */
    protected $params = array();
    
    /**
     * Démarre la page demandée : à définir dans toutes les frames utilisées.
     * 
     * @return void
     */
    abstract protected function main();
    
    /**
     * Définit les variables du header et du footer.
     * 
     * @return void
     */
    final private function _callHeadFoot($chrono){
        // -- Selection d'un logo aléatoire.
        $bans = glob('./images/design/logo/*.gif');
        
        // -- Temps.
        $chrono = chrono($chrono);
        $chrono_sql = Obj::$db->getChrono();
        
        if ($chrono < $chrono_sql) {
            $temp = $chrono;
            $chrono = $chrono_sql;
            $chrono_sql = $temp;
        }
        
        $ary_vars = array(
                'TITLE' => $this->_title,
                'SITE_DESC' => $this->_desc,
                
                'EXEC_TIME' => round($chrono, 4),
                'EXEC_SQL' => round($chrono_sql, 4),
                
                'NBR_SQL' => count(Obj::$db->getQueries()),
                'NBR_CONNECTES' => Sys::$nbr_connectes,
                
                'COPY_DATE' => parse_date('now', 'Y', false, false) + 1,
                'CUR_DATE' => parse_date('now', DATE_FULL),
                'IS_DST' => Obj::$date->isDST(),
                
                'JS' => $this->_js,
                'RSS' => $this->_rss,
                
                'FIL' => $this->_fil,
                
                'IS_MODO' => Sys::$u_level <= GRP_MODO,
                'IS_ADMIN' => Sys::$u_level == GRP_ADMIN,
                'IS_LOGGED' => Sys::$uid != GUEST,
                
                'U_ID' => Sys::$uid,
                'USERNAME' => Sys::$u_data['u_login'],
                'NB_MESSAGES' => 0,
                
                'LOGO_ALEATOIRE' => $bans[array_rand($bans)]
            );
        
        Obj::$tpl->set($ary_vars);
    } // end Frame::_callHeadFoot()
    
    /**
     * Construit la pseudo-frame
     * 
     * @param integer $chrono Chrono démarré dans start.php.
     * @return void
     */
    final protected function __construct($chrono){
        // -- On met une référence à cet objet sur l'objet "Frame".
        Obj::$frame = &$this;

        /*
         * S'il existe une méthode "$this->_router()" dans la frame enfant, on
         * l'utilise alors pour nommer les paramètres. Sinon, si il s'agit d'un
         * simple nommage, qui ne nécessite aucuns traitements au cas par cas,
         * alors on regarde le contenu de $this->_params pour nommer les paramètres
         */
        if (method_exists($this, '_router')) {
            $this->_router();
        } elseif (!empty($this->params)) {
            Obj::$router->name($this->params);
        }
        
        // -- Appel du corps, et du header / footer de la page.
        $this->main();
        $this->_callHeadFoot($chrono);
        
        Obj::$tpl->parse($this->_tpl);
        
        // -- Debuggage : Requetes SQL, stats, ... Etc.
        if (Sys::$debug && isset($_GET['debug'])){
            echo '<h5>Requetes Effectuées :</h5><div style="width:100%; overflow:auto;"><ol>';
            
            foreach (Obj::$db->getQueries() as $rq) {
            	echo '<li>' . nl2br(str_replace(' ', '&nbsp;', $rq['query'])) . ', en ' . nombres($rq['time'], 4) . ' secondes</li>';
            }
            
            echo '</ol></div>';
        }
    } // end Frame::__construct()
    
    /**
     * Récupère la page courante, et démarre la bonne frame.
     * 
     * @param integer $chrono Chrono démarré dans start.php.
     * @param string $page Utiliser une page spécifique ?
     * @return Frame_Child
     */
    final public static function start($chrono, $frame = ''){
        if( empty($frame) ){
            $frame = !empty(Obj::$router['frame']) ? Obj::$router['frame'] : 'index';
        }

        // -- Un petit alias...
        if ($frame == 'forgot') {
            $frame = 'activate';
        }
        
        if (!preg_match('`^[a-z0-9_]+$`', $frame) || !file_exists('./frames/' . $frame . '.php')) {
            message('La page demandée n\'existe pas !', 'index.html', 404, MESSAGE_ERROR, array('HTTP/1.0 404 Not Found', 404));
            exit;
        }
        
        self::$frame = $frame;
        
        require './frames/' . self::$frame . '.php';
        
        // -- Lancement de la frame.
        return new Frame_Child($chrono);
    }
    
    /**
     * Ajoute un fichier CSS
     *
     * @param string $href Nom du fichier CSS (sans extension).
     * @return void
     */
    final public function addCSS($href){
        Obj::$tpl->setBlock('style_css', 'IMPORT', $href);
    }
    
    /**
     * Ajoute un flux RSS
     *
     * @param string $title
     * @param string $href
     * @return void
     */
    final public function addRSS($title, $href){
        $this->_rss[] = array(
                'title' => htmlspecialchars($title),
                'href' => $href
            );
    }
    
    /**
     * Ajoute un script JS
     *
     * @param string $file Fichier JS à ajouter
     * @return void
     */
    final public function addJs($file){
        $this->_js[] = $file;
    }
    
    /**
     * Accessor pour $this->data
     *
     * @return array
     */
    final public function getDatas(){
        return $this->data;
    }
    
    /**
     * Settor pour $this->data
     *
     * @param string $key Donnée
     * @param string $val Valeur
     * @return void
     */
    final public function setDatas($key, $val = ''){
        if (is_array($key)){
            $this->data = array_merge($this->data, $key);
        } else {
            $this->data[$key] = $val;
        }
    }
    
    /**
     * Définit le TPL utilisé
     *
     * @param string $tpl TPL principal à utiliser
     * @return void
     */
    final public function setTpl($tpl){
        $this->_tpl = $tpl;
    }
    
    /**
     * Settor pour $this->_title
     *
     * @param string $title Titre de la page
     * @return void
     */
    final public function setTitle($title){
        $this->_title = $title;
    }
    
    /**
     * Settor pour la description (balise meta)
     *
     * @param string $desc Description
     */
    final public function setDesc($desc){
        $this->_desc = $desc;
    }
    
    /**
     * Ajoute un élément au fil d'arianne
     *
     * @param string $item Element à ajouter
     * @param string $url URL de l'élément. Vaut false si y'en a pas.
     */
    final public function addToNav($item, $url = false){
        if (is_array($item)){
            $this->_fil = array_merge($this->_fil, $item);
        } else {
            $this->_fil[$item] = $url;
        }
    }
    
    // }}}
}

// -- Appel de la pseudo frame à afficher, génération du header et du footer.
Frame::start($chrono);

/** EOF /**/
