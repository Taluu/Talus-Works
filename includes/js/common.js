/**
 * Contient les fonctions basiques pour le Javascript de Talus' Works.
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
 * @begin 01/02/2008, Talus
 * @last 16/09/2009, Talus
 */

var escape_old = escape; //ancienne fonction escape
escape = function (texte){
		return escape_old(texte).replace(/\+/g,'%2B');
	};


/**
 * Emule la fonction trim() de PHP.
 * @param string str
 * @return string
 * @author Kevin van Zonneveld
 * @link http://kevin.vanzonneveld.net
 */
function trim(str){
    return str.replace(/(^[\s\xA0]+|[\s\xA0]+$)/g, '');
}

/**
 * Emule la fonction str_replace() de PHP.
 * @param string search
 * @param string replace
 * @param string subject
 * @return string
 * @author Kevin van Zonneveld
 * @link http://kevin.vanzonneveld.net
 */
function str_replace(search, replace, subject) {
    var result = "";
    var prev_i = 0;
    
    for (i = subject.indexOf(search); i > -1; i = subject.indexOf(search, i)) {
        result += subject.substring(prev_i, i);
        result += replace;
        i += search.length;
        prev_i = i;
    }
    
    return result + subject.substring(prev_i, subject.length);
}

/**
 * Emule la fonction is_numeric() de PHP (d'après {@link http://kevin.vanzonneveld.net Kevin van Zonneveld})
 * @param mixed nb Nombre à tester
 * @return bool
 */
function is_numeric(nb){
    return !isNaN(nb);
}

/**
 * Gère la jumpbox.
 * @param SelectHTMLItem item Select de la Jumpbox
 * @return void
 */
function jumpbox(item){
    var value = item.options[item.selectedIndex].value;	
    document.location = is_numeric(value) ? 'forum-' + value + '-p1.html' : value;
}

/**
 * Simule htmlspecialchars de base de PHP.
 * @param string str Chaine à encoder
 * @return string
 */
function htmlspecialchars(str){
    str = str_replace('&', '&amp;', str);
    str = str_replace('<', '&lt;', str);
    str = str_replace('>', '&gt;', str);
    
    return str;
}

/**
 * Simule l'inverse de htmlspecialchars de base de PHP.
 * @param string str chaine à décoder
 * @return string
 */
function htmlspecialchars_decode(str){
    str = str_replace('&lt;', '<', str);
    str = str_replace('&gt;', '>', str);
    str = str_replace('&amp;', '&', str);
    
    return str;
}

/**
 * Vide un noeud.
 * @param obj node Noeud à nettoyer
 * @return void
 */
function clean(node){
    while( node.childNodes.length > 0 ){
        node.removeChild(node.firstChild);
    }
}
