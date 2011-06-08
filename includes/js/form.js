/**
 * Contient les fonctions  Javascript pour les formulaires de Talus' Works.
 *
 * Vous êtes libre d'utiliser et de distribuer ce script comme vous l'entendez, en gardant à l'esprit 
 * que ce script est, à l'origine, fait par des développeurs bénévoles : en conséquence, veillez à 
 * laisser le Copyright, par respect de ceux qui ont consacré du temps à la création du script. 
 *
 * @package	Talus' Works
 * @author Baptiste "Talus" Clavié <talusch@gmail.com>
 * @copyright ©Talus, Talus' Works 2007, 2008
 * @link http://www.talus-works.net Talus' Works
 * @license	http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 01/02/2008, Talus
 * @last 16/08/2008, Talus
 */

var minHeight = 150;
var maxHeight = 1000;

var jump_size = 50;

var curHeight = 150;

/**
 * Agrandis un textarea
 * @param string text id du textarea
 * @param integer coeff coefficient à multimplier (-1 ou 1)
 * @return bool
 * @access public
 */
function setAll(text, coeff){
    var curText = document.getElementById(text);
    
    curHeight += Number(coeff) * jump_size;
    
    if (curHeight < minHeight) {
        curHeight = minHeight;
    } else if(curHeight > maxHeight) {
        curHeight = maxHeight;
    }
    
    curText.style.height = curHeight + 'px';
    
    curText.focus();
    return false;
}