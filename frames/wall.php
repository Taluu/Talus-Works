<?php
/**
 * Affichage de la source d'un fichier (TPL, PHP, Autres).
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
 * @begin 25/12/2007, Talus
 * @last 21/06/2009, Talus
 * @todo A revoir entièrement pour le fonctionnement (plus possibilité
 *       de stocker des scripts externes ?)
 */

class Frame_Child extends Frame {
    /**
     * Fichiers interdits de consultation
     * 
     * @var array
     */
    private $forbidden = array('includes/config.php' => 'includes/config.dummy.php');
    
    /**
     * @ignore 
     */
    protected function main(){
        if (isset($_GET['type'], $_GET['file'])){
            $type = $_GET['type'];
            $file = basename($_GET['file']);
            $file = $file == '.htaccess' ? '.htaccess' : trim(substr(realpath($_GET['file']), strlen(DOMAIN_PATH)), './\\\x00..\x20\x80..\xFF');
            //exit($file);
        } else {
            $type = 'html';
            $file = 'tpl/files/home/wall.html';
        }

        $file_highlighted = $file;
        
        // -- Controle !
        if (file_exists($file)) {
            if (isset($this->forbidden[$file])){
                if ($this->forbidden[$file] !== false){
                    $file_highlighted = $this->forbidden[$file];
                } else {
                    message('Vous n\'avez pas les droits pour consulter cette source.', 'wall.html', 403, MESSAGE_ERROR, 'HTTP/1.0 403 Forbidden');
                    exit;
                }
            }
        } else {
            message('Ce fichier n\'existe pas !', 'wall.html', 404, MESSAGE_ERROR, 'HTTP/1.0 404 Not Found');			
            exit;
        }
        
        $data_compiled = '';
        
        // -- Parsage de ce qu'on veut obtenir comme type de source, et pour quel fichier...
        if ($type == 'php') {
            $data_tpl = highlight_file($file_highlighted, true);
            
            $data_tpl = preg_replace('`^(?:&nbsp;&nbsp;&nbsp;&nbsp;)+<br />$`m', '<br />', $data_tpl);
            $data_tpl = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;', '<span class="highlight_tabs">&nbsp;&nbsp;&nbsp;&nbsp;</span>', $data_tpl);
        } else if ($type == 'html') {
            list($data_tpl, $data_compiled) = $this->_tpl($file_highlighted);
        } else {
            $data_tpl = htmlspecialchars(file_get_contents($file_highlighted));
        }
        
        $vars = array(
                'FILE' => $file,
                'SOURCE_CODE' => $data_tpl,
                'SOURCE_CODE_COMPILED' => $data_compiled,
                'TYPE' => $type
            );
        
        Obj::$tpl->set($vars);
        
        // -- Appel des méthodes parentes
        $this->setTpl('home/wall.html');
        $this->addCSS('syntax_highlighter');
        $this->setTitle('Source : ' . $file . ' - Wall');
        $this->addToNav('Wall', DOMAIN_REDIRECT . 'wall.html');
        $this->addToNav('Source du fichier <strong>' . $file . '</strong>', false);
    }
    
    /**
     * Récupère la source d'un TPL précis et de son équivalent compilé.
     * 
     * @param string $tpl TPL à récupérer
     * @return array
     */
    private function _tpl($tpl) {
        $data_compiled = '';
        
        if (strpos($tpl, 'tpl/files/') !== false) {
            $cache = str_replace('/', '.', substr($tpl, strlen('tpl/files/')));
            $data_compiled = highlight_file('./tpl/cache/' . $cache . '.php', true);
            $data_compiled = str_replace('<br />', '<br />' . "\n", $data_compiled);
            $data_compiled = preg_replace('`(?:&nbsp;&nbsp;&nbsp;&nbsp;)+<br />`', '<br />', $data_compiled);
            $data_compiled = str_replace('&nbsp;&nbsp;&nbsp;&nbsp;', '<span class="highlight_tabs">&nbsp;&nbsp;&nbsp;&nbsp;</span>', $data_compiled);
        }
        
        $data_tpl = file_get_contents($tpl);
        $data_tpl = highlight_tpl($data_tpl, true, true);
        
        return array($data_tpl, $data_compiled);
    }
}

/** EOF /**/
