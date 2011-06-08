<?php
/**
 * Etend la classe "DateTime" de PHP 5.2
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
 * @begin 15/08/2008, Talus
 * @last 20/07/2009, Talus
 */

class Date extends DateTime {
    /**
     * Timestamp UNIX
     * 
     * @var integer
     */
    private $_unix = 0;
    
    /**
     * Timestamp SQL
     *
     * @var string
     */
    private $_sql = '';
    
    /**
     * Mettre à jour ou non les deux timestamp.
     *
     * @var boolean
     */
    private $_update = true;
    
    /**
     * Est-on en période DST ?
     * 
     * @var boolean
     */
    private $_dst = null;
    
    /**
     * Affiche le timestamp UNIX
     * 
     * @return integer
     */
    public function unix(){
        if ($this->_unix == 0){
            $this->_unix = $this->format('U');
        }
        
        return $this->_unix;
    }
    
    /**
     * Retourne un timestamp SQL
     *
     * @return string
     */
    public function sql(){
        if ($this->_sql == ''){
            $this->_sql = $this->format('Y-m-d H:i:s');
        }
        
        return $this->_sql;
    }
    
    /**
     * Retourne si on est en horaire d'été (DST) ou non
     * (Suit la politique des horaires GMT : http://docs.php.net/manual/fr/function.gmmktime.php#47605)
     * 
     * @return integer
     */
    public function isDST(){
        if ($this->_dst === null){
            $compare = new DateTime('01:00:00', $this->getTimezone());
            $dst = false;
            
            // -- A-t-on dépassé l'heure d'été ?
            $compare->setDate($this->format('Y'), 4, 1);
            $compare->modify('Last Sunday');
            
            $dst = $this >= $compare;
            
            // -- Est-on toujours à l'heure d'été ?
            $compare->setDate($this->format('Y'), 11, 1);
            $compare->modify('Last Sunday');
            
            $this->_dst = $dst && $this < $compare;
        }
        
        return (bool) $this->_dst;
    }
    
    /**
     * Supplante la capacité de modification d'une date via DateTime::modify()
     *
     * @param mixed $timestr Nouveau temps à utiliser
     * @return Date
     */
    public function modify($timestr){
        // -- Si c'est une date non timestamp, on utilise DateTime::modify ; sinon, on magouille un peu...
        if (ctype_digit((string) $timestr) || $timestr[0] == '@') {
            // -- Si c'est un entier, et non préfixé par "@", on le préfixe
            if (ctype_digit((string) $timestr)){
                $timestr = '@' . $timestr;
            }
            
            $date = new Date($timestr, $this->getTimezone());
            
            $this->_update = false;
            
            $this->setDate($date->format('Y'), $date->format('n'), $date->format('j'));
            $this->setTime($date->format('H'), $date->format('i'), $date->format('s'));
            
            $this->_sql = '';
            $this->_unix = 0;
            $this->_update = true;
            
            unset($date);
        } else {
            parent::modify($timestr);
        }
        
        return $this;
    }
    
    /**
     * Supplante DateTime::setTime()
     * 
     * @param integer $h
     * @param integer $m
     * @param integer $s
     * @return Date
     */
    public function setTime($h, $m, $s = 0){
        parent::setTime($h, $m, $s);
        
        if ($this->_update){
            $this->_sql = '';
            $this->_unix = 0;
        }
        
        return $this;
    }
    
    /**
     * Supplante DateTime::setDate()
     * 
     * @param integer $y
     * @param integer $m
     * @param integer $d
     * @return Date
     */
    public function setDate($y, $m, $d){
        parent::setDate($y, $m, $d);
        
        if ($this->_update){
            $this->_sql = '';
            $this->_unix = 0;
        }
        
        return $this;
    }
    
    /**
     * Etends la capacité du constructeur DateTime::__construct().
     * 
     * @param mixed $timestr Temps à utiliser
     * @param DateTimeZone $tz Fuseau horaire à utiliser
     * @return Date
     */
    public function __construct($timestr = 'now', DateTimeZone $tz){
        // -- Si c'est un entier, et non préfixé par "@", on le préfixe
        if (ctype_digit((string) $timestr)){
            $timestr = '@' . $timestr;
        }
        
        parent::__construct($timestr, $tz);
        
        return $this;
    }
}

/** EOF **/