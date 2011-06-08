<?php
/**
 * Affiche l'arborescence du site (et pointe vers le {@link wall.php wall})
 * -- Repris du "webtool_ftp" de l'admin FSB, adapté pour Talus' Works.
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
 * @begin 19/08/2008, Talus
 * @last 17/07/2009, Talus
 * @ignore
 */

final class Frame_Child extends Frame {
    /**
     * Fichiers auxquels on ne doit pas pouvoir acceder
     *
     * @var array
     */
    private $_forbidden = array(
            //'includes/config.php' => 'includes/config.dummy.php', 
            'includes/config.dummy.php' => false
        );
    
    /**
     * Extensions autorisées
     *
     * @var unknown_type
     */
    //private $allowed_exts = array('php', 'html');
    
    /**
     * Le type de fichiers qu'on ne peut mettre sur le wall
     *
     * @var array
     */
    private $_not_wallable = array('img', 'archive');
    
    /**
     * Dossier courant
     *
     * @var string
     */
    private $_dir = '';
    
    /**
     * Contenu du dossier
     *
     * @var array
     */
    private $_content = array();
    
    /**
     * Méthode de tri
     *
     * @var string
     */
    private $_sortby = 'type';
    
    /**
     * Sens de la liste
     *
     * @var string
     */
    private $_order = 'asc';
    
    /**
     * Méthodes de tri possibles
     *
     * @var array
     */
    private $_sortby_list = array(
            'name' => 'U_NAME', 
            'type' => 'U_TYPE', 
            'filesize' => 'U_FILESIZE', 
            'lastmodif' => 'U_LASTMODIF'
        );
    
    /**
     * Sens possibles
     *
     * @var array
     */
    private $_order_list = array('desc' => 'asc', 'asc' => 'desc');
    
    /**
     * @ignore
     */
    protected function main(){
        $this->_dir = trim(isset($_GET['dir']) ? substr(realpath('.' . $_GET['dir']), strlen(DOMAIN_PATH)) : '', './\\');
        
        if (!is_dir(ROOT . $this->_dir)){
            $this->_dir = '';
        }
        
        $this->_sortby = isset($_GET['sortby']) && isset($this->_sortby_list[$_GET['sortby']]) ? $_GET['sortby'] : $this->_sortby;
        $this->_order = isset($_GET['order']) && isset($this->_order_list[$_GET['order']]) ? $_GET['order'] : $this->_order;
        
        $sortby_links = array('CURRENT' => '~/' . $this->_dir);
        
        foreach ($this->_sortby_list as $key => $var) {
        	$sortby_links[$var] = 'explorer.html?' . ($this->_dir != '' ? 'dir=' . $this->_dir . '/&amp;' : '') . 'order=' . ($this->_sortby == $key ? $this->_order_list[$this->_order] : 'asc') . '&amp;sortby=' . $key;
        }
        
        Obj::$tpl->set($sortby_links);
        
        $this->_listItems();
        
        // -- Définition des elements de la page (titre, description ...)
        $this->addCSS('explorer');
        $this->setTitle('Répertoire ~/' . $this->_dir . ' - Explorez les sources de Talus\' Works');
        $this->setDesc('Explorez l\'arborescence de Talus\' Works - Répertoire ~/' . $this->_dir);
        $this->setTpl('home/explorer.html');
        
        $this->addToNav('Arborescence du site', 'explorer.html');
        $this->addToNav('Repertoire ~/' . $this->_dir, 'explorer.html?' . ($this->_dir != '' ? 'dir=/' . $this->_dir . '&amp;' : '') . 'order=' . $this->_order . '&amp;sortby=' . $this->_sortby);
        $this->addToNav('Exploration du répertoire', false);
    }
    
    /**
     * Récupère la liste des fichiers
     * 
     * @return void
     * @access private
     */
    private function _listItems(){
        $dir_handle = opendir(ROOT . $this->_dir);
        
        while (($current = readdir($dir_handle)) !== false){
            if ($current == '.' || $current == '..' || $current == 'index.html'){
                continue;
            }
            
            // -- Par défaut, il n'existe pas de fichier 'dummy'.
            $dummy = false;
            
            if (isset($this->_forbidden[$this->_dir . '/' . $current])){
                if ($this->_forbidden[$this->_dir . '/' . $current] !== false) {
                    $dummy = true;
                } else {
                    continue;
                }
            }
            
            $is_dir = is_dir(ROOT . $this->_dir . '/' . $current);
            
            $this->_content[] = array(
                    'name' => $current,
                    'type' => $is_dir ? 'dir' : $this->_type(substr($current, strrpos($current, '.') + 1)),
                    'filesize' => $is_dir ? (pow(2, 32) - 1) : filesize(ROOT . $this->_dir . '/' . $current),
                    'lastmodif' => filemtime(ROOT . $this->_dir . '/' . $current),
                    'is_dir' => $is_dir,
                    'url' => trim($dummy ? $this->_forbidden[$this->_dir . '/' . $current] : $this->_dir . '/' . $current, './\\')
                );
        }
        
        closedir($dir_handle);
        
        usort($this->_content, array($this, '_sort'));
       
        if ($this->_dir != ''){ 
            foreach (array('~/' => '', '../' => dirname($this->_dir)) as $name => $dir) {
                Obj::$tpl->setBlock('dirlist', array(
                        'NAME' => $name,
                        'TYPE' => 'Dossier de Fichiers',
                        'SIZE' => '---',
                        'LASTMODIF' => '---',
                        'IMGTYPE' => './images/icones/arbo/dir.gif',
                        'URL' => 'explorer.html?' . (($name == '~/' || $dir == '.') ? '' : 'dir=/' . $dir . '&amp;') . 'order=' . $this->_order . '&amp;sortby=' . $this->_sortby
                    ));
            }
        }
        
        foreach ($this->_content as $current) {
            if ($current['is_dir']){
                $url = 'explorer.html?dir=' . ($current['url'][0] == '/' ? '' : '/') . $current['url'] . '&amp;order=' . $this->_order . '&amp;sortby=' . $this->_sortby;
            } elseif (!in_array($current['type'], $this->_not_wallable)){
                $url = 'wall.html?type=' . $current['type'] . '&amp;file=' . $current['url'];
            } else {
                $url = $current['url'];
            }
            
            Obj::$tpl->setBlock('dirlist', array(
                    'NAME' => $current['name'],
                    'TYPE' => $current['is_dir'] ? 'Dossiers de fichiers' : 'Fichier ' . strtoupper($current['type']),
                    'SIZE' => $current['is_dir'] ? '---' : get_size($current['filesize']),
                    'LASTMODIF' => parse_date($current['lastmodif'] - 150),
                    'IMGTYPE' => './images/icones/arbo/' . $current['type'] . '.gif',
                    'URL' => $url
               ));
        }
    }
    
    /**
     * Callback pour usort ; permet de trier les dossiers
     *
     * @param array $a Premier element à comparer
     * @param array $b Deuxieme element à comparer
     * @return integer
     * @access private
     */
    private function _sort($a, $b){
        $order = strcmp($a[$this->_sortby], $b[$this->_sortby]);
        
        // -- Si la comparaison alphabétique a échoué, on compare manuellement.
        if ($order != 0){
            $order = $a[$this->_sortby] > $b[$this->_sortby] ? 1 : -1;
            
            // -- Si on souhaite un ordre descendant, on inverse le résultat.
            if ($this->_order == 'desc'){
                $order *= -1;
            }
        } else {
            $order = strcmp($a['name'], $b['name']);
        }
        
        return $order;
    }
    
    /**
     * Détermine le type d'un fichier à partir de son extension
     *
     * @param Extension du fichier $ext
     * @return string
     */
    private function _type($ext){
        switch ($ext) {
            case 'php':
            case 'phps':
            case 'php3':
            case 'php4':
            case 'php5':
            case 'php6':
                $type = 'php';
                break;
                
            case 'html':
            case 'xhtml':
            case 'xml':
            case 'htm':
                $type = 'html';
                break;
            
            case 'jpeg':
            case 'gif':
            case 'jpg':
            case 'png':
                $type = 'img';
                break;
                
            case 'tar':
            case 'zip':
            case 'gz':
                $type = 'archive';
                break;
            
            default:
            case 'txt':
                $type = 'txt';
                break;
        }
        
        return $type;
    }
}

/** EOF /**/
