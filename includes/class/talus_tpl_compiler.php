<?php
/**
 * Compilateur de Talus' TPL.
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
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 * @copyright ©Talus, Talus' Works 2008+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @last 17/07/2009, Talus
 */
 
 // -- Si PHP < 5.3, déclaration de E_USER_DEPRECATED
 if (!defined('E_USER_DEPRECATED')) {
    define('E_USER_DEPRECATED', E_USER_NOTICE);
 }
 
/**
 * Compileur pour les templates
 */
class Talus_TPL_Compiler {
    private static $_instance = null;
    
    /**
     * Constructeur & Cloneur ; Certes, ils ne font rien, mais ils sont là pour
     * éviter de pouvoir instancier plusieurs fois la classe Talus_TPL_Compiler 
     * (il n'y a aucuns sens de l'instancier plusieurs fois...)
     *
     * @see http://fr.wikipedia.org/singleton
     */
    private function __construct(){}
    private function __clone(){}
    
    /**
     * Namespace
     *
     * @var string
     */
    private $_namespace = '';
    
    /**
     * Pattern Singleton ; si l'instance n'a pas été démarrée, on la démarre...
     * Sinon, on renvoit l'objet déjà créé.
     *
     * @return self
     */
    public static function getInstance(){
        if (self::$_instance === null){
            self::$_instance = new self;
        }
        
        return self::$_instance;
    }
    
    /**
     * Transforme une chaine en syntaxe TPL vers une syntaxe PHP.
     * 
     * @param string $compile TPL à compiler
     * @return string
     */
    public function compile($compile){
        $compile = str_replace('<?' ,'<?php echo \'<?\'; ?>', $compile);
        $compile = preg_replace('`/\*.*?\*/`s', '', $compile);
        
        // -- Utilisation de filtres (parsage récursif)
        $matches = array();
        while (preg_match('`\{(?:(KEY|VALUE|GLOB),)?(\$?[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff.]*(?:\[(?!]\|)(?:.*?)])?)\|((?:[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\|?)+)}`', $compile, $matches)) {
            $compile = str_replace($matches[0], $this->_filters($matches[2], $matches[3], $matches[1]), $compile);
        }
        
        // -- Appels de fonctions 
        $compile = preg_replace_callback('`<' . $this->_namespace . 'call ' . $this->_namespace . 'name="([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"((?: ' . $this->_namespace . 'arg="[^"]*?")*) />`', array($this, '_callFunction'), $compile);
        
        // -- Les blocs
        $compile = preg_replace_callback('`<' . $this->_namespace . 'block ' . $this->_namespace . 'name="([a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*)"(?: ' . $this->_namespace . 'parent="([a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*)")?>`', array($this, '_compileBlock'), $compile);
        $compile = preg_replace_callback('`<' . $this->_namespace . 'block ' . $this->_namespace . 'name="([a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*)\.([a-z0-9_\x7f-\xff]+)">`', array($this, '_compileBlock__Old'), $compile);
        
        // -- Regex non complexes qui n'ont pas besoin d'un traitement récursif
        $not_recursives = array(
                // -- Inclusions
                '`<' . $this->_namespace . 'include ' . $this->_namespace . 'tpl="(\{\$(?:[a-z0-9_]+\.)?[A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*(?:\[(?!]})(?:.*?)])?})" />`' => '<?php $tpl->includeTpl($1, false, 0); ?>',
                '`<' . $this->_namespace . 'include ' . $this->_namespace . 'tpl="(.+?\.html)" />`' => '<?php $tpl->includeTpl(\'$1\', false, 0); ?>',
                '`<' . $this->_namespace . 'include ' . $this->_namespace . 'tpl="(\{\$(?:[a-z0-9_]+\.)?[A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*(?:\[(?!]})(?:.*?)])?})" ' . $this->_namespace . 'once="(true|false)" />`' =>  '<?php $tpl->includeTpl($1, $2, 0); ?>', 
                '`<' . $this->_namespace . 'include ' . $this->_namespace . 'tpl="(.+?\.html)" ' . $this->_namespace . 'once="(true|false)" />`' => '<?php $tpl->includeTpl(\'$1\', $2, 0); ?>',
                
                // -- Déclaration de variables
                '`<' . $this->_namespace . 'set ' . $this->_namespace . 'var="([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[(?!]">)(?:.*?)])?">(?!"</set>)(.+?)</set>`' => '<?php $tpl->vars[\'$1\']$2 = \'$3\'; ?>',
                
                // -- Foreachs
                '`<' . $this->_namespace . 'foreach ' . $this->_namespace . 'ary="\{\$([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}">`i' => '<?php foreach ({$$1} as $__tpl_foreach_key[\'$1\'] => &$__tpl_foreach_value[\'$1\']) : ?>',
                '`<' . $this->_namespace . 'foreach ' . $this->_namespace . 'ary="\{((?:(?:VALUE,)?\$[A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(?:\[(?!]})(?:.*?)])?)}" ' . $this->_namespace . 'as="\{\$([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}">`i' => '<?php foreach ({$1} as $__tpl_foreach_key[\'$2\'] => &$__tpl_foreach_value[\'$2\']) : ?>', 
                
                // -- Constantes
                '`\{__([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)__}`i' => '<?php echo $1; ?>',
                '`\{__\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)__}`i' => '$1', 
                
                // -- Conditions
                '`<' . $this->_namespace . 'if ' . $this->_namespace . 'cond(?:ition)?="(?!">)(.+?)">`' => '<?php if ($1) : ?>',
                '`<' . $this->_namespace . 'elseif ' . $this->_namespace . 'cond(?:ition)?="(?!" />)(.+?)" />`' => '<?php elseif ($1) : ?>'
            );
        
        $compile = preg_replace(array_keys($not_recursives), array_values($not_recursives), $compile);
        
        // -- Regex non complexes qui ont besoin d'un traitement récursif
        $recursives = array(
                // -- Variables Foreachs
                '`\{KEY,([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}`i' => '<?php echo $__tpl_foreach_key[\'$1\']; ?>',
                '`\{KEY,\$([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)}`i' => '$__tpl_foreach_key[\'$1\']',
                '`\{VALUE,([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[(?!]})(?:.*?)])?}`' => '<?php echo $__tpl_foreach_value[\'$1\']$2; ?>',
                '`\{VALUE,\$([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[(?!]})(?:.*?)])?}`' => '$__tpl_foreach_value[\'$1\']$2', 
                
                // -- Variables simples
                '`\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[(?!]})(?:.*?)])?}`' => '<?php echo $tpl->vars[\'$1\']$2; ?>',
                '`\{\$([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[(?!]})(?:.*?)])?}`' => '$tpl->vars[\'$1\']$2',
                
                // -- Variables Blocs
                '`\{([a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*)\.([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[(?!]})(?:.*?)])?}`' => '<?php echo $__tplBlock[\'$1\'][\'$2\']$3; ?>',
                '`\{\$([a-z_\x7f-\xff][a-z0-9_\x7f-\xff]*)\.([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)(\[(?!]})(?:.*?)])?}`' => '$__tplBlock[\'$1\'][\'$2\']$3'
            );
        
        foreach ($recursives as $regex => $replace) {
            while(preg_match($regex, $compile)) {
                $compile = preg_replace($regex, $replace, $compile);
            }
        }
            
        // -- Les définitions de fonctions
        $compile = preg_replace_callback('`<' . $this->_namespace . 'function ' . $this->_namespace . 'name="([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)"((?: ' . $this->_namespace . 'arg="[A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*")*)>(.+?)</function>`s', array($this, '_defineFunction'), $compile);
        
        // -- Les str_replace (moins de ressources que les preg_replace) !
        $compile = str_replace(array(
                "</{$this->_namespace}block>", "<{$this->_namespace}blockelse />", "</{$this->_namespace}foreach>",
                "<{$this->_namespace}else />", "</{$this->_namespace}if>",
                '{\\'
            ), array(
                '<?php } endif; ?>', '<?php } else : if (true) { ?>', '<?php endforeach; ?>',
                '<?php else : ?>', '<?php endif; ?>',
                '{'
            ), $compile);
        
        // -- Nettoyage du code, et retour du code "compilé"
        return str_replace('?><?php', '', $compile);
    }
    	
    /**
     * Compile un bloc
     * 
     * @param  array $match  Array contenant les captures des blocs (cf Ligne 83)
     * @see compile()
     * @return string
     */
    private function _compileBlock(array $match){
        /*
         * Il y a un bloc parent ; Il faut donc utiliser une des références
         * créées par le foreach du bloc parent.
         * 
         * Sinon, Il faut juste récupérer le bloc à la racine.
         */
        if (!empty($match[2])) {
            $bloc = "\$__tplBlock['{$match[2]}']['{$match[1]}']";
            $cond = "isset({$bloc})";
        } else {
            $cond = $bloc = "\$tpl->getBlock('{$match[1]}')";
        }
        
        // -- Variable référençant le bloc actuel
        $ref = '$__tpl_' . sha1(uniqid(mt_rand(), true));
        
        /* 
         * Afin de pouvoir faire un foreach par référence, celui-ci demandant
         * forcément une variable (et pas une fonction) pour pouvoir faire une
         * itération par référence, on est ainsi obligé de créer un bloc
         * temporaire pour récupérer la référence retournée par $tpl->getBlock...
         */
        return "<?php if ({$cond}) : {$ref} = &{$bloc}; foreach ({$ref} as &\$__tplBlock['{$match[1]}']){ ?>";
    }
    	
    /**
     * Compile un bloc (pour le format déprécié <block name="parent.enfant">)
     * 
     * @param  array $match  Array contenant les captures des blocs (cf Ligne 82)
     * @see compile()
     * @return string
     *
     * @deprecated
     */
    private function _compileBlock__Old(array $match){
        // -- Notice pour deprecated
        trigger_error('Talus_TPL->compiler->_compileBlock() : La syntaxe
                       <code><block name="parent.enfant"></code> est dépréciée ;
                       Veuillez mettre à jour votre script TPL pour
                       <code><block name="enfant" parent="parent"></code>',
                       E_USER_DEPRECATED);
        
        // -- Appel de la méthode actuelle, et inversion de match[1] et match[2]
        $this->_compileBlock(array($match[0], $match[2], $match[1]));
    }
    
    /**
     * Parse les déclarations de fonctions
     * 
     * @param array $matches Capture (cf ligne 116)
     * @see compile()
     * @return string
     */
    private function _defineFunction(array $matches){
        $php = "<?php function __tpl_{$matches[1]}(Talus_TPL \$tpl, ";
        
        // -- Demande d'arguments...
        if (!empty($matches[2])) {
            $matches[2] = ltrim(mb_substr($matches[2], 5 + mb_strlen($this->_namespace), -1));
            $args = explode('" ' . $this->_namespace . 'arg=" ', $matches[2]);
            foreach ($args as &$arg) {
                $php .= "\${$arg}, ";
            }
        }
        
        $php = rtrim($php, ', ') . '){ ?>';
        
        $script = preg_replace('`\$tpl->vars\[\'([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\']`', '$$1', $matches[3]);
        $script = preg_replace('`\$GLOB,([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)`', '$tpl->vars[\'$1\']', $script );
        
        return $php . $script . ' <?php } ?>';
    }
    
    /**
     * Parse les appels de fonctions
     *
     * @param array $matches Capture (cf ligne 70)
     * @see compile()
     * @return string
     */
    private function _callFunction(array $matches){
        $php = "<?php __tpl_{$matches[1]}(\$tpl, ";
        
        if (!empty($matches[2])) {
            $args = explode('" ' . $this->_namespace . 'arg="', mb_substr(ltrim($matches[2]), 5 + mb_strlen($this->_namespace), -1));
            
            foreach ($args as &$arg ){
                $php .= $this->_escape($arg) . ', ';
            }			
        }
        
        return rtrim($php, ', ') . '); ?>';
    }
    
    /**
     * Filtre une var avec un ou plusieurs des filtres de Talus_TPL_Filters.
     *
     * @param string $var Variable capturée
     * @param $filters Liste des filtres (séparés|par|un|pipe)
     * @param string $type Capture du TYPE pour {TYPE,VAR}
     * @see Talus_TPL_Filters
     * @see compile()
     * @return string
     */
    private function _filters($var = '', $filters = '', $type = null){
        $i = 0; // Nombre de parenthèses ( ouvertes
        $return = ''; // Retour
        $toPrint = false; // Faut-il afficher ou retourner le résultat ?
        $var = "{{$var}}"; // Variable
        $filters = array_reverse(array_filter(explode('|', $filters))); // Filtres
        
        /*
         * Si on souhaite afficher la variable (absence du $ significatif), il
         * faut alors bidouiller la variable pour qu'elle ait un $... En étant
         * affichée, et non retournée.
         *
         * Si c'est le cas, on a juste besoin de rajouter le $ devant le nom
         * de la variable...
         */
        if ($var[1] != '$') {
            $var = '{$' . mb_substr($var, 1);
            $toPrint = true;
        }
        
        /*
         * Si on a affaire à une variable du type {TYPE,VAR}, on doit alors
         * remplacer la première accolade ouvrante "{" (caractéristique des
         * variables TPL) par "{TYPE,"
         */
        if (!empty($type)) {
            $var = "{{$type}," . mb_substr($var, 1);
        }
        
        foreach ($filters as &$filter) {
            // -- Filtre non déclaré ?
            if (!method_exists('Talus_TPL_Filters', $filter)){
                trigger_error("Talus_TPL->compiler->_filters() :: Le filtre
                              \"$filter\" n'existe pas, et sera donc ignoré.\n\n",
                              E_USER_NOTICE);
                continue;
            }
            
            // -- Ajout du filtre, incrémentation du nombre de (
            $return .= "Talus_TPL_Filters::{$filter}(";
            ++$i;
        }
        
        // -- Association de la variable, fermeture des différentes ( ouvertes
        $return .= $var . str_repeat(')', $i);
        
        /*
         * Si la variable ne commence pas par un $, il faut alors afficher son
         * contenu.
         */
        if ($toPrint === true){
            $return = "<?php echo {$return}; ?>";
        }
        
        return $return;
    }
    
    /**
     * Vérifie si il faut échapper (et le fait si c'est le cas), ou non.
     *
     * @param mixed $arg Argument à vérifier
     * @return mixed Argument
     */
    private function _escape($arg) {
        /* 
         * Il faut vérifier que l'argument en question n'est ni une
         * variable ni ni un chiffre... il faut donc l'échapper !
         */
        if (($arg[0] != '{' || $arg[mb_strlen($arg) - 1] != '}') && !ctype_digit($arg)) {
            $arg = '\'' . str_replace('\'', '\\\'', $arg) . '\'';
        }
        
        return $arg;
    }
    
    /**
     * Getter pour $this->_namespace
     *
     * @return string
     */
    public function getNamespace() {
        return $this->_namespace;
    }
    
    /**
     * Setter pour $this->_namespace
     *
     * @param string $namespace Nom du nspace
     * @return void
     */
    public function setNamespace($namespace = 'tpl') {
        $this->_namespace = empty($namespace) ? '' : $namespace . ':';
    }
}

/** EOF /**/
