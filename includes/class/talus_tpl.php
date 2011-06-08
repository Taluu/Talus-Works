<?php
/**
 * Moteur de gestion de TPLs
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
 * @copyright ©Talus, Talus' Works 2006+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/lgpl.html LGNU Public License 2+
 * @begin 23/12/2006, Talus
 * @last 13/07/2009, Talus
 */

class Talus_TPL {
    /**
     * Chemin des Templates
     * 
     * @var string
     * @see self::getRootDir()
     */
    private $_root = './';
    
    /**
     * Objet Talus_TPL_Cache (Cache des TPLs)
     *
     * @see Talus_TPL_Cache
     * @var Talus_TPL_Cache
     */
    private $_cache = null;
    
    /**
     * Objet Talus_TPL_Compiler (Compilateur des TPLs)
     *
     * @see Talus_TPL_Compiler
     * @var Talus_TPL_Compiler
     */
    private $_compiler = null;
    
    /** 
     * Contenu des blocs.
     * 
     * @see self::getBlock()
     * @see self::$vars
     * @var array
     */
    private $_blocks = array(
            '.' => array(
    	           0 => array()
                )
        ); 
    
    /**
     * Variables globales (définies dans le block root)
     * 
     * @see self::$_blocks
     * @var ref
     */
    public $vars = array();
	
    /**
     * Infos sur le Template à parser.
     * 
     * @var array
     */
    private $_infos = array();
	
    /**
     * Contient le nom du fichier lors de la précompilation
     * 
     * @var string
     */	
    private $_tpl = null; 
	
    /**
     * Constructeur des Templates.
     * 
     * @param string $root Le dossier contenant les templates.
     * @param string $cache Le dossier contenant le cache.
     * @return void
     */
    public function __construct($root = './', $cache = './cache/'){
    	// -- Destruction du cache des fichiers de PHP.
    	clearstatcache();
    	
    	// -- Objets à instancier pour Talus' TPL
    	$this->_cache = Talus_TPL_Cache::getInstance();
    	$this->_compiler = Talus_TPL_Compiler::getInstance();
    	
    	// -- Un petit alias....
    	$this->vars = &$this->_blocks['.'][0];
    	
    	// -- Mise en place du dossier de templates
    	$this->setDir($root, $cache);
    }
	
    /**
     * Permet de choisir le dossier contenant les tpls.
     * 
     * @param string $root Le dossier contenant les templates.
     * @param string $cache Le dossier contenant le cache des tpls.
     * @return void
     *
     * @since 1.5.1
     */
    public function setDir($root = './', $cache = './cache/'){
        // -- On ampute le root du slash final, si il existe.
        $root = rtrim($root, '/');
        
        // -- Le dossier existe-t-il ?
        if (!is_dir($root)) {
            exit("Talus_TPL->setDir() :: Le répertoire <strong>{$root}</strong> n\'existe pas.");
        }
        
        $this->_root = $root;
        $this->_cache->setDir($cache);
    }
	
    /**
     * Ajoute un fichier à l'instance des templates.
     * 
     * @param string $file Fichier à ajouter.
     * @return void
     */
    private function _setFile($file){
        // -- Si le fichier a déjà été ajouté, on ignore
        if (isset($this->_infos[$file])) {
            return;
        }
    
        // -- Le fichier n'existe pas : renvoi d'une erreur fatale.
        if (!is_file($this->getRootDir() . '/' . $file)) {
            exit("Talus_TPL->_setFile() :: Le template <em>{$file}</em> n\'existe pas.");
        }
        
        $this->_infos[$file] = array(
                'last' => filemtime($this->getRootDir() . '/' . $file),
                'included' => array(),
            );
    }
	
    /**
     * Définit une ou plusieurs variable.
     * 
     * @param array|string $vars Variable(s) à ajouter
     * @param mixed $value Valeur de la variable si $vars n'est pas un array
     * @return void
     *
     * @since 1.3.0
     */
    public function set($vars, $value = null){
        if (is_array($vars)) {
            $this->vars = array_merge($this->vars, $vars);
        } else {
            $this->vars[$vars] = $value;
        }
    }
    
    /**
     * Définit une variable par référence.
     * 
     * @param mixed $var Nom de la variable à ajouter
     * @param mixed &$value Valeur de la variable à ajouter.
     * @return void
     *
     * @since 1.5.1
     */
    public function setRef($var, &$value){
        $this->vars[$var] = &$value;
    }
    
    /**
     * Permet d'ajouter une itération d'un bloc et de ses variables
     * 
     * @param string $block Nom du bloc à ajouter.
     * @param array|string $vars Variable(s) à assigner à ce bloc
     * @param string $value Valeur de la variable si $vars n'est pas un array
     * @return void
     *
     * @since 1.5.1
     */
    public function setBlock($block, $vars, $value = null){
        if (!is_array($vars)) {
            $vars = array($vars => $value);
        }
        
        /* 
         * Récupération de tous les blocs, du nombre de blocs, et mise en place
         * d'une référence sur la variable globale des blocs.
         *
         * Le but d'une telle manipulation est de parcourir chaque élément
         * "$current", afin d'accéder au bloc désiré, et permettre ainsi
         * l'initialisation des variables pour la dernière instance du bloc
         * appelé.
         */
        $blocks = explode('.', $block);
        $curBlock = array_pop($blocks); // Bloc à instancier
        $current = &$this->_blocks;
        
        foreach ($blocks as &$cur) {
            if (!isset($current[$cur])) {
                trigger_error("<strong>Talus_TPL->setBlock() ::</strong> Le bloc
                               <em>{$cur}</em> ({$block}) n\'est pas défini.",
                               E_USER_WARNING);
                return;
            }
            
            $current = &$current[$cur];
            $current = &$current[count($current) -  1];
        }
        
        if (!isset($current[$curBlock])) {
            $current[$curBlock] = array();
            $nbRows = 0;
        } else {
            $nbRows = count($current[$curBlock]);
        }
        
        /*
         * Variables spécifiques aux blocs (inutilisables autre part) :
         * 
         * FIRST : Est-ce la première itération (true/false) ?
         * LAST : Est-ce la dernière itération (true/false) ?
         * CURRENT : Itération actuelle du bloc.
         * SIZE_OF : Taille totale du bloc (Nombre de répétitions totale)
         *
         * On peut être à la première itération ; mais ce qui est sur, c'est
         * qu'on est forcément à la dernière itération.
         * 
         * Si le nombre d'itération est supérieur à 0, alors ce n'est pas la
         * première itération, et celle d'avant n'était pas la dernière. 
         *
         * Quant au nombre d'itérations (SIZE_OF), il suffit de lier la variable
         * de l'instance actuelle aux autres, et ensuite d'incrémenter cette
         * même variable
         */
        $vars['FIRST'] = true;
        $vars['LAST'] = true;
        $vars['CURRENT'] = $nbRows + 1;
        $vars['SIZE_OF'] = 0;
        
        if ($nbRows > 0) { 
            $vars['FIRST'] = false;
            $current[$curBlock][$nbRows - 1]['LAST'] = false;
            
            $vars['SIZE_OF'] = &$current[$curBlock][0]['SIZE_OF'];
        }
        
        ++$vars['SIZE_OF'];
        $current[$curBlock][] = $vars;
    }
	
    /** 
     *  Parse l'ensemble du TPL.
     * 
     *  @param  mixed $tpl TPL concerné (vide si non spécifié)
     *  @return void
     */
    public function parse($tpl = ''){
        // -- Erreur critique si vide
        if (empty($tpl)) {
            exit('Talus_TPL->parse() :: Aucuns modèle renseigné !');
        }
        
        // -- Déclaration du fichier (ne fera rien si déjà fait)
        $this->_setFile($tpl);
        
        $this->_tpl = $tpl;
        $this->_cache->setFile($this->_tpl, 0);
        
        // -- Si le cache n'existe pas, ou n'est pas valide, on le met à jour.
        if (!$this->_cache->isValid($this->_infos[$this->_tpl]['last'])) {
            $this->_cache->put($this->_compiler->compile(file_get_contents($this->getRootDir() . '/' . $this->_tpl)));
        }
        
        $this->_cache->exec($this);
    }
	
    /**
     * Parse le TPL, mais renvoi directement le résultat de celui-ci 
     * (entièrement parsé, et donc déjà executé par PHP).
     * 
     * @param string $tpl Nom du TPL à parser.
     * @param integer $ttl Temps de vie (en secondes) du cache de niveau 2
     * @return string
     *
     * @todo Cache de niveau 2 ??
     */
    public function pparse($tpl = '', $ttl = 0){
        ob_start();
        $this->parse($tpl);
        return ob_get_clean();
    }
    
    /**
     * Parse une chaine de caractères, et retourne son contenu
     *
     * @param string $str chaine de caractère à parser
     * @return string
     *
     * @since 1.5.0
     */
    public function sParser($str){
        return $this->_compiler->compile($str);
    }
	
    /**
     * Inclue un TPL : Le parse si nécessaire
     * 
     * @see Talus_TPL_Compiler::compile()
     * 
     * @param string $file Fichier à inclure.
     * @param bool $once N'inclure qu'une fois ?
     * @param integer $lifetime Temps de vie en secondes du cache
     * @return void
     *
     * @todo Cache de niveau 2 ?
     */
    public function includeTpl($file, $once = false, $lifetime = 0){
        $current_tpl = $this->_tpl;
        
        /*
         * Si un fichier ne doit être présent qu'une seule fois, on regarde si
         * il a déjà été inclus au moins une fois.
         * 
         * Si oui, on ne l'inclue pas ; 
         * Si non, on l'ajoute à la pile des fichiers inclus.
         */
        if ($once){
            if (in_array($file, $this->_infos[$current_tpl]['included'])) {
                return;
            } else {   
                $this->_infos[$current_tpl]['included'][] = $file;
            }
        }
        
        $data = $this->pparse($file, (int)$lifetime);
        
        /* 
         * Remise en place du tpl en cours de compilation, et affichage du
         * contenu du tpl inclus
         */
        $this->_tpl = $current_tpl;
        echo $data;
    }
    
    /**
     * Getter pour $this->_root
     *
     * @return string
     */
    public function getRootDir(){
        return $this->_root;
    }
    
    /**
     * Getter pour le dossier de cache.
     *
     * @return string
     */
    public function getCacheDir(){
        return $this->_cache->getDir();
    }
    
    /**
     * Getter pour $this->_blocks
     * 
     * @param string $block Bloc à récupérer
     * @return &array
     */
    public function &getBlock($block = '.'){
        $block = isset($this->_blocks[$block]) ? $this->_blocks[$block] : null;
        return $block;
    }
    
    /**
     * Setter pour $this->_compiler->_namespace
     *
     * @param string $namespace Namespace à utiliser
     * @return void
     */
    public function setNamespace($namespace = 'tpl') {
        
    }
}

/**
 * EOF
 */
