<?php
/**
 * Gestion des Exceptions de Talus' Works
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
 * @copyright ©Talus, Talus' Works 2008+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 10/09/2008, Talus
 * @last 10/09/2008, Talus
 */

/**
 * Interfaces définissant le comportement à avoir lors d'une exception.
 * Vive les néologismes....
 */
interface tracable {
    public function trace();
}

interface isNoisy {
    public function get_error();
}

interface loggable {
    public function make_log();
}

/**
 * Gestion globales des exceptions lancées.
 */
class TW_Exception extends Exception {
    public function __construct($msg = null, $code = 0){
        parent::__construct($msg, $code);
        
        $this->get_error();
        $this->trace();
        //$this->make_log();
    }
    
    final protected function get_error(){
        if ($this instanceof isNoisy){
        	echo $this->getMessage();
        }
        
    }
    
    final protected function trace(){
        if ($this instanceof tracable){
        	echo $this->getTraceAsString();
        }
    }
    
    final protected function make_log(){
        if ($this instanceof loggable){
            
        }
    }
}

/**
 * Exceptions lancées lors d'une erreur formulaire
 */
class TW_Form_Exception extends TW_Exception {
    public function __construct($msg = null, $code = 0){
        parent::__construct($msg, $code);
    }
}

/**
 * Exception lancée au cas d'une erreur PHP.
 */
class TW_PHP_Exception extends TW_Exception implements tracable, isNoisy, loggable {
    protected $context = null;
    
    public function __construct($code, $str, $file, $line, $context){
        $this->context = $context;
        $this->message = $str;
        $this->code = $code;
        $this->file = $file;
        $this->line = $line;
        
        parent::__construct($str, $code);
    }
}

/**
 * Exception lancée si c'est une erreur SQL.
 */
class TW_SQL_Exception extends TW_Exception implements isNoisy, tracable, loggable {
    protected $script_name = '';
    protected $id = 0;
    
    public function __construct($script_name, $idx, $query, $str){
        $this->id = $id;
        $this->script_name = $script_name;
        $this->message = $str;
        
        $debug = '';
        
        if (Sys::$debug === true){
            $debug = ' (SQL : ' . $query . ', Erreur renvoyée : ' . $str . ')';
        }
        
        parent::__construct('SQL_' . $script_name . ' :: Erreur lors de la requête #' . $script_name . '_' . $id . $debug, 800 + $id);
    }
}

/**
 * Exception lancée pour tout autre type d'erreurs
 */
class TW_Misc_Exception extends TW_Exception implements isNoisy {
    public function __construct($msg = null, $code = 0){
        parent::__construct($msg, $code);
    }
}

/**
 * Gestionnaire des erreurs de PHP
 *
 * @param integer $code Code de l'erreur
 * @param string $str Erreur reportée
 * @param string $file Fichier ou l'erreur s'est déclenchée
 * @param integer $line Ligne du fichier ou l'erreur s'est déclenchée
 * @param array $context Variables à l'execution
 */
function tw_phperrors($code, $str, $file, $line, array $context){
    $e = new TW_PHP_Exception($code, $str, $file, $line, $context);
    $e->get_error();
}

/**
 * Attrappe les erreurs non rattrapées
 *
 * @param TW_Exception $e Exception attrapée. Puisque toutes les exceptions sont de type TW_Exception... ^^
 */
function tw_exception(TW_Exception $e){
    $e->get_error();
}

set_error_handler('tw_phperrors');
set_exception_handler('tw_exception');

/** EOF /**/
