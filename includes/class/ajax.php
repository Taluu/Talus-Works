<?php
/**
 * Gestion des evenements AJaX
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
 * @last 16/06/2008, Talus
 * 
 */

if (!defined('COMMON') || COMMON == false) {
    header('Location: ../../index.php');
    exit;
}

final class AJaX {
    /**
     * Contient les évenements
     * 
     * @var array
     * @access private
     * @static
     */
    private static $_events = array();
    
    /**
     * Constantes pour l'AJAX : On renvoit du XML ou du TXT ?
     * 
     * @var     integer
     * @acces   public
     */
    const TXT = 0; // -- text/plain
    const XML = 1; // -- text/xml
    
    /**
     * Ajoute un évenement.
     * 
     * @param integer $type Renvoyer du texte ou du xml (via les constantes) ?
     * @param string $name Nom de l'Evenemment, et de sa fonction.
     * @param mixed $args,... Arguments à passer à l'évenement.
     * @return void
     * @static
     */
    public static function add($type, $name){
        if (function_exists('AJaX_' . $name)) {	
            // -- On charge tous les arguments, et on ajoute l'evenement
            $args = func_num_args();
            $argv = array();
            
            for ($i = 2; $i < $args; $i++){
                $argv[] = func_get_arg($i);
            }
            
            self::$_events[$name] = array(
                    'args' => $argv,
                    'type' => $type
                );
        }
    }
    
    /**
     * Détruit un évenement AJAX
     * 
     * @param string $name Evenement à détruire
     * @return void
     * @static
     */
    public static function destroy($name){
        if (isset(self::$_events[$name])){
            unset(self::$_events[$name]);
        }
    }
    
    /**
     * Déclenche un évennement
     * 
     * @param string $name Nom de l'évenement à déclencher
     * @return void
     * @static
     */
    public static function trigger($name){
        if (isset(self::$_events[$name])){
            $return = call_user_func_array('AJaX_' . $name, self::$_events[$name]['args']);
            
            // -- On ne traite les données que si elles sont valides...
            if( $return !== null ){
                switch(self::$_events[$name]['type']) {
                    default:
                    case self::TXT:
                        header('Content-Type: text/plain');
                    break;
                    
                    case self::XML:
                        header('Content-Type: text/xml');
                    break;
                }
                
                echo $return;
            }
        }
    }
}

/** EOF /**/
