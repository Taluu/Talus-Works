<?php
/**
 * Gère les requetes SQL : Etend la classe PDO.
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
 * @begin 10/09/2008, Talus
 * @last 05/01/2009, Talus
 */

if (!defined('COMMON') || COMMON == false) {
    header('Location: index.php');
    exit;
}

final class SQL extends PDO {
    /**
     * Historique des requêtes
     * 
     * @var array
     */
    private $_queries = array();
    
    /**
     * Chrono de toutes les requêtes
     * 
     * @var integer
     */
    private $_chrono = 0;
    
    /**
     * Requête en cours d'execution (pour self::prepare, self::query)
     * 
     * @var string
     * @see SQL::query
     * @see SQL::prepare
     */
    private $_curQuery = '';
    
    /**
     * Erreur SQL
     * 
     * @const string
     */
    const ERR = 'Erreur SQL ! Vous pensez que c\'est un bug ? Contactez l\'admin...';
    
    /**
     * Se connecte à la base de donnee
     * 
     * @param string $dsn Driver à utiliser
     * @param string $username Utilisateur
     * @param string $passwd Mot de passe
     * @param array $options[optional] Options à faire passer
     * @return void
     */
    public function __construct($dsn, $username, $passwd) {
        try {
            parent::__construct($dsn, $username, $passwd);
            
            // -- Quelques options...
            parent::setAttribute(PDO::ATTR_STATEMENT_CLASS, array('SQL_Statement', array($this)));
            parent::setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            parent::setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
            parent::setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
            parent::setAttribute(PDO::ATTR_AUTOCOMMIT, true); // en attendant la mise en place des transactions...
            
            // -- On travaille en UTF8, et en UTC :]
            //parent::exec('SET NAMES UTF8;');
            parent::exec('SET time_zone = \'' . TIME_ZONE . '\';');
        } catch (PDOException $e){
            $str = 'SQL::__construct() : ' . self::ERR;
            
            if (Sys::$debug){
                $str .= '<br />' . $e->getMessage() . '<br />' . $e->getTraceAsString();
            }
            
            exit($str);
        }
    }
    
    /**
     * Effectue une requête SQL
     * 
     * @param string $sql Requête à effectuer
     * @return SQL_Statement
     */
    public function query($sql){ 
        try {
            $chr = chrono(0);
            $this->_curQuery = $sql;
            $stmt = parent::query($sql);
            $this->addQuery($sql, chrono($chr));
        } catch (PDOException $e) {
            $str = 'SQL::query() : ' . self::ERR;
            
            if (Sys::$debug){
                $str .= '<br />' . $e->getMessage() . '<br />' . $e->getTraceAsString();
            }
            
            exit($str);
        }
        
        return $stmt;
    }
    
    /**
     * Prépare une requête SQL
     * 
     * @param $statment Requete à effectuer
     * @param $options Options à faire passer
     * @return SQL_Statement
     */
    public function prepare($statment, $options = array()){
        try {
            $chr = chrono(0);
            $this->_curQuery = $statment;
            $stmt = parent::prepare($statment, $options);
            
             // On augemente que le chrono, car elle n'est pas executée...
            $this->_chrono += chrono($chr);
        } catch (PDOException $e){
            $str = 'SQL::prepare() : ' . self::ERR;
            
            if (Sys::$debug){
                $str .= '<br />' . $e->getMessage() . '<br />' . $e->getTraceAsString();
            }
            
            exit($str);
        }
        
        return $stmt;
    }
    
    /**
     * Effectue une requête SQL qui ne renvoit rien.
     * 
     * @param string $sql Requête SQL à effectuer
     * @return void
     */
    public function exec($sql){
        try {
            $chr = chrono(0);
            parent::exec($sql);
            $this->addQuery($sql, chrono($chr));
        } catch (PDOException $e){
            $str = 'SQL::exec() : ' . self::ERR;
            
            if (Sys::$debug){
                $str .= '<br />' . $e->getMessage() . '<br />' . $e->getTraceAsString();
            }
            
            exit($str);
        }
    }
    
    /**
     * Ajoute une requête à l'historique
     * 
     * @param string $sql Requête à ajouter
     * @param integer $chrono Temps mis par la requête
     * @return void
     */
    public function addQuery($sql, $chrono = 0){
        $this->_queries[] = array(
                'query' => $sql, 
                'chrono' => $chrono
            );
            
        $this->_chrono += $chrono;
    }
    
    /**
     * Retourne l'historique des requêtes
     * 
     * @return array
     */
    public function getQueries(){
        return $this->_queries;
    }
    
    /**
     * Retourne le chrono total
     * 
     * @return integer
     */
    public function getChrono(){
        return $this->_chrono;
    }
    
    /**
     * Récupère la dernière requête effectuée.
     * 
     * @return string
     */
    public function getLastQuery(){
        return $this->_curQuery;
    }
}

/**
 * Surcouche de PDOStatement
 * 
 * On étend ici le comportement de PDOStatement pour pouvoir gérer l'historique
 * des requêtes. 
 */
class SQL_Statement extends PDOStatement {
    /**
     * Objet SQL
     * 
     * @var SQL
     */
    private $_pdo = null;
    
    /**
     * Constructeur
     * 
     * @param SQL $pdo Objet SQL à transmettre
     * @return void
     */
    protected function __construct(SQL $pdo) {
        $this->_pdo = $pdo;
    }
    
    /**
     * Execute une requête précédemment préparée
     * 
     * @param array $input_parameters Array à faire passer.
     * @return bool
     */
    public function execute($input_parameters = null){
        try {
            $chr = chrono(0);
            $bool = parent::execute($input_parameters);
            $this->_pdo->addQuery($this->_pdo->getLastQuery(), chrono($chr));
        } catch (PDOException $e){
            $str = 'SQL_Statement::execute() : ' . SQL::ERR;
            
            if (Sys::$debug){
                $str .= '<br />' . $e->getMessage() . '<br />' . $e->getTraceAsString();
            }
            
            exit($str);
        }
        
        return $bool;
    }
}

/** EOF /**/
