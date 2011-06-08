/**
 * Contient les fonctions pour l'AJAX de Talus' Works.
 *
 * Vous êtes libre d'utiliser et de distribuer ce script comme vous l'entendez, en gardant à l'esprit  
 * que ce script est, à l'origine, fait par des développeurs bénévoles : en conséquence, veillez à 
 * laisser le Copyright, par respect de ceux qui ont consacré du temps à la création du script. 
 *
 * @package Talus' Works
 * @author Baptiste "Talus" Clavié <talusch@gmail.com>
 * @copyright ©Talus, Talus' Works 2007+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 01/02/2008, Talus
 * @last 29/09/2008, Talus
 * @todo Créer une class AJaX ?
 */

var eip_content = new Array();
var eip_working = new Array();
var eip_started = false;

/**
 * Instancie l'objet xhr
 *
 * @return XMLHTTPRequest
 */
function getXhr(){
    var xhr = null; 
    
    try{ // Firefox et autres
        xhr = new XMLHttpRequest();
    }  catch (e){ // Internet Explorer 
        try {
            xhr = new ActiveXObject("Microsoft.XMLHTTP");
        } catch (e) {
            xhr = new ActiveXObject("Msxml2.XMLHTTP");
        }
    }

    return xhr;
}

/**
 * Ajoute une quote sur le formulaire de réponse
 *
 * @param integer pid ID de la réponse
 * @param string txt ID du textarea recepteur
 * @return void
 */
function quote(pid, txt){
    var response; // -- Réponse de l'ajax
    var curText = document.getElementById(txt); // -- Récupération de l'id du text
    var img = document.getElementById('quote_' + pid); // -- Récupération de l'image de citation :)
    var xhr = getXhr(); // -- Récupération de l'objet XHR
    var scroll = curText.scrollTop; // -- La position de l'ascenseur :)
    
    open_waiter();
    
    xhr.onreadystatechange = function(){
            if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
                curText.value += ' ' + xhr.responseText;
                curText.focus();
                curText.scrollTop = scroll;
                
                close_waiter();
                img.src = './images/icones/quote_on.gif';
            }
        };
    
    xhr.open('GET','ajax.php?mode=quote&pid=' + pid,true);
    xhr.send(null);
}

/**
 * Prévisualise un message
 *
 * @param string txt ID du textarea
 * @param string prev ID du prev
 * @return void
 */
function prev(txt, prev){
    var curText = document.getElementById(txt);
    var value = curText.value;
    var response = '';
    var divPrev = document.getElementById(prev);
    var xhr = getXhr();
    
    // -- On agrandit divPrev que si curText est pas vide. Et on lance le tout que si curText est pas vide !
    if (trim(curText.value) != '') {
        open_waiter();
        
        divPrev.innerHTML = '';
        divPrev.style.height = '200px';
        
        value = encodeURIComponent(curText.value);
        
        xhr.onreadystatechange = function(){
                if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
                    divPrev.innerHTML = xhr.responseText;
                    
                    close_waiter();
                    curText.focus();
                }
            };
        
        xhr.open('POST','ajax.php?mode=prev',true);
        xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
        xhr.send('reply=' + value);
    } else {
        divPrev.style.height = '30px';
        divPrev.innerHTML = '';
    }
}

/**
 * Lance l'édition rapide d'un message
 *
 * @param integer pid ID du post
 * @return void
 */
function edit_in_place(pid){
    var container = document.getElementById('content_' + pid);
    var xhr = getXhr();
    var response = '';
    
    // -- Pas plus d'une édition rapide à la fois ! (//?)
    if (eip_working[pid]) {
        return false;
    }
    
    open_waiter();
    
    eip_content[pid] = container.innerHTML;
    
    xhr.onreadystatechange = function(){
            if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0) && xhr.responseText != '') {
                container.innerHTML = xhr.responseText;
                
                eip_working[pid] = true;
                
                close_waiter();
            }
        };
    
    xhr.open('GET', 'ajax.php?mode=edit&pid=' + pid, true);
    xhr.send(null);
    
    return true;
}

/**
 * Annule l'édition dynamique
 *
 * @param integer pid ID du post
 * @return void
 */
function cancel_eip(pid){
    var container = document.getElementById('content_' + pid);
    
    open_waiter();
    
    if( typeof eip_content[pid] !== 'undefined' ){
    container.innerHTML = eip_content[pid];
    eip_content[pid] = undefined;
    eip_working[pid] = false;
    }
    
    close_waiter();
}

/**
 * Affiche ou non le titre "Double Cliquez pour éditer".
 *
 * @param integer pid ID du post
 * @return void
 */
function eip_title(pid){
    document.getElementById('content_' + pid).title = eip_working[pid] ? '' : 'Double Cliquez pour éditer';
}

/**
 * Soumet le formulaire d'édition rapide
 *
 * @param pid ID du post
 * @return bool
 */
function submit_eip(pid){
    var container = document.getElementById('content_' + pid);
    var date_edit = document.getElementById('edit_p' + pid);
    var msg_edit = document.getElementById('msg_edit_p' + pid);
    
    var form = document.forms['ajax_eip_' + pid];
    var edit = form.edit;
    var title = form.t_title;
    var description = form.t_description;
    
    var xhr = getXhr();
    
    var response = null;
    var content = null;
    
    var node = document.getElementById('infos_topic');
    
    var titre = null;
    var desc = null;
    
    var item = 0;
    var new_content = '';
    
    open_waiter();
    
    // -- Nettoyage du div "infos_topic".
    clean(node);
    
    // -- Affectation de nouvelles valeurs pour les noeuds titres et description.
    titre = document.createElement('h1');
    desc = document.createElement('div');
    
    // -- Attribution d'une class spéciale au noeud de description.
    desc.className = 'forum_description';
    
    edit = encodeURIComponent(edit.value);
    title = encodeURIComponent(title.value);
    description = encodeURIComponent(description.value);
    
    xhr.onreadystatechange = function(){
            if (xhr.readyState == 4 && (xhr.status == 200 || xhr.status == 0)) {
                response = xhr.responseXML;
                
                //alert(xhr.responseText);
                
                if (response) {
                    //alert('k' + "\n" + xhr.responseText);
                    // -- Affectation des titres
                    content = response.getElementsByTagName('titre')[0].firstChild.data;
                    document.title = content + ' - Les Forums - Talus\' Works';
                    titre.appendChild(document.createTextNode(content));
                    node.appendChild(titre);
                    
                    // -- Affectation de la description... Si non vide !
                    if (response.getElementsByTagName('description')[0].firstChild) {
                        desc.appendChild(document.createTextNode(response.getElementsByTagName('description')[0].firstChild.data));
                        node.appendChild(desc);
                    }
                    
                    container.innerHTML = response.getElementsByTagName('message')[0].firstChild.data;
    
                    // -- Si la date d'edition n'existe pas (ou n'a pas encoré été crée), on la crée !
                    // -- -- (ou du moins, on essaye xD)
                    if (date_edit == null){
                        edit_details = document.createElement('div');
                        edit_details.className = 'edited';
                        edit_details.setAttribute('id', 'edit_p' + pid);
                        
                        container.parentNode.appendChild(edit_details);
                        date_edit = document.getElementById('edit_p' + pid);
                    }
                    
                    //if (date_edit != null){
                    date_edit.innerHTML = 'Dernière édition il y a 0 secondes, par ' + response.getElementsByTagName('author')[0].firstChild.data;
                    //}
                    
                    eip_content[pid] = undefined;
                    eip_working[pid] = false;
                }
                
                close_waiter();
                
                // -- L'edition a reussie ==> tant mieux, si elle a raté, y'a quand même le traitement des réponses...
                return !response;
            }
        };
    
    xhr.open('POST','ajax.php?mode=submit_eip&pid=' + pid,true);
    xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    xhr.send('edit=' + edit + '&title=' + title + '&description=' + description);
}

/**
 * Ouvre le waiter de l'AJAX.
 *
 * @return void
 */
function open_waiter(){
    document.getElementById('ajax_waiter').style.display = 'block';
}

/**
 * Ferme le waiter de l'AJAX
 *
 * @return void
 */
function close_waiter(){
    document.getElementById('ajax_waiter').style.display = 'none';	
}