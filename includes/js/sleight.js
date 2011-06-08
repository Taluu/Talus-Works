/**
 * Permet d'afficher les PNG transparents sous IE6 (supposé :p).
 *
 * Vous êtes libre d'utiliser et de distribuer ce script comme vous l'entendez, en gardant à l'esprit 
 * que ce script est, à l'origine, fait par des développeurs bénévoles : en conséquence, veillez à 
 * laisser le Copyright, par respect de ceux qui ont consacré du temps à la création du script. 
 *	
 * @package Talus' Works
 * @author Aaron Boodman, Drew McLellan <http://www.allinthehead.com>
 * @copyright ©Aaron Boodman, Drew McLellan
 * @link http://www.allinthehead.com, http://www.youngpup.net
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin ??/??/2001, Aaron Boodman
 * @last 16/08/2008, Talus
 */

if (navigator.platform == "Win32" && navigator.appName == "Microsoft Internet Explorer" && window.attachEvent) {
    window.attachEvent("onload", alphaBackgrounds);
    
    document.writeln('<style type="text/css">img { visibility:hidden; } </style>');
    window.attachEvent("onload", fnLoadPngs);
}

/**
 * Filtre les images PNG en background
 * 
 * @return void
 */
function alphaBackgrounds(){
    var rslt = navigator.appVersion.match(/MSIE (\d+\.\d+)/, '');
    var itsAllGood = (rslt != null && Number(rslt[1]) >= 5.5);
    
    for (i=0; i<document.all.length; i++){
        var bg = document.all[i].currentStyle.backgroundImage;
        if (itsAllGood && bg){
            if (bg.match(/\.png/i) != null){
                var mypng = bg.substring(5,bg.length-2);
                document.all[i].style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + mypng + "', sizingMethod='scale')";
                document.all[i].style.backgroundImage = "url('/assets/images/x.gif')";
            }
        }
    }
}

/**
 * Filtre les images PNG en balises <img />
 * 
 * @return void
 */
function fnLoadPngs(){
    var rslt = navigator.appVersion.match(/MSIE (\d+\.\d+)/, '');
    var itsAllGood = (rslt != null && Number(rslt[1]) >= 5.5);
    
    for (var i = document.images.length - 1, img = null; (img = document.images[i]); i--){
        if (itsAllGood && img.src.match(/\.png$/i) != null) {
            var src = img.src;
            var div = document.createElement("DIV");
            
            div.style.filter = "progid:DXImageTransform.Microsoft.AlphaImageLoader(src='" + src + "', sizing='scale')"
            div.style.width = img.width + "px";
            div.style.height = img.height + "px";
            img.replaceNode(div);
        }
        
        img.style.visibility = "visible";
    }
}
