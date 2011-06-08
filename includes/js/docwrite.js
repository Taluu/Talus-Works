/**
 * Permet d'émuler document.write pour les navigateurs récents et comprenant 
 * application/xhtml+xml (disons... tous sauf IE ? :p).
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
 * @begin 16/06/2009, Talus
 * @last 16/06/2009, Talus
 */

/**
 * Emule document.write, pour FFx, Safari, Opera (non existant en xHTML !)
 * @param string str Chaine à ajouter
 * @author John Resig
 * @see http://ejohn.org/blog/xhtml-documentwrite-and-adsense/
 */
document.write = function(str){
    var moz = !window.opera && !/Apple/.test(navigator.vendor);
       
    // Watch for writing out closing tags, we just
    // ignore these (as we auto-generate our own)
    if ( str.match(/^<\//) ) return;

    // Make sure & are formatted properly, but Opera
    // messes this up and just ignores it
    if ( !window.opera )
        str = str.replace(/&(?![#a-z0-9]+;)/g, "&amp;");

    // Watch for when no closing tag is provided
    // (Only does one element, quite weak)
    str = str.replace(/<([a-z]+)(.*[^\/])>$/, "<$1$2></$1>");
       
    // Mozilla assumes that everything in XHTML innerHTML
    // is actually XHTML - Opera and Safari assume that it's XML
    if ( !moz )
        str = str.replace(/(<[a-z]+)/g, "$1 xmlns='http://www.w3.org/1999/xhtml'");
       
    // The HTML needs to be within a XHTML element
    var div = document.createElementNS("http://www.w3.org/1999/xhtml","div");
    div.innerHTML = str;
       
    // Find the last element in the document
    var pos;
       
    // Opera and Safari treat getElementsByTagName("*") accurately
    // always including the last element on the page
    if ( !moz ) {
        pos = document.getElementsByTagName("*");
        pos = pos[pos.length - 1];
               
        // Mozilla does not, we have to traverse manually
    } else {
        pos = document;
        while ( pos.lastChild && pos.lastChild.nodeType == 1 )
            pos = pos.lastChild;
    }
       
    // Add all the nodes in that position
    var nodes = div.childNodes;
    while ( nodes.length )
        pos.parentNode.appendChild( nodes[0] );
};