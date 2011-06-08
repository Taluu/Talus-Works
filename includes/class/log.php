<?php
/**
 * Gère les logs.
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
 * @begin 11/09/2008, Talus
 * @last 11/09/2008, Talus
 */

 class Log {
    private static $table = 'logs';
    private static $file = '/logs/errlog.log';
    
    private static $str = '';
    private static $date = '';
    
    const DATE_LOG = 'd/m/Y - H:i';
    
    public static function set($table = 'logs', $file = '/logs/errlog.log', $sub = ''){
        self::$table = $table;
        self::$file = $file;
        self::$subject = $sub;
    }
     
    public static function add($str){
        self::$date = parse_date(Obj::$date, self::DATE_LOG);
        self::$str = $str;
        
        // -- ?????
        try {
            $exception = 'Impossible d\'écrire dans le journal des erreurs !';
            
            if (!self::table()){
                throw new TW_Misc_Exception($exception, 90);
            }
            
            if (!self::file()){
                throw new TW_Misc_Exception($exception, 90);
            }
        } catch (TW_Misc_Exception $e) {
            message('Une erreur interne est survenue.', 'index.html', 90, MESSAGE_ERROR);
        }
    }
    
    private static function table(){
        return true;
    }
    
    private static function file(){
        return true;
    }
 }

/** EOF /**/
