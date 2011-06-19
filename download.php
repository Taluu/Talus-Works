<?php
/**
 * Télécharge un fichier, force le téléchargement de celui ci !
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
 * @copyright ©Talus, Talus' Works 2007, 2009
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 03/02/2008, Talus
 * @last 16/08/2008, Talus
 */

// -- Qqs constantes
define('ROOT', './');
define('PHP_EXT', substr(__FILE__, strrpos(__FILE__, '.')+1));
define('COMMON', true);

// -- Inclusion du démarrage de tout le schmilblik
include('./includes/start.php');

// -- On récupère les infos du script... Si il existe !
$sql = 'SELECT s_path
            FROM scripts
            WHERE s_id = :id;';

#REQ DOWNLOAD_1
$res = Obj::$db->prepare($sql);
$res->bindValue(':id', intval($_GET['id']), SQL::PARAM_INT);
$res->execute();

$data = $res->fetch(SQL::FETCH_ASSOC);
$res = null;

// -- Pas de données retrouvées...
if (!$data) {
	message('Il n\'y a pas de correspondances pour le fichier demandé !', '', 50, MESSAGE_ERROR);
	exit;
}

// -- Le fichier, il existe plus :p
if (!file_exists('../downloads/' . $data['s_path'])) {
	message('Le fichier demandé n\'existe pas.', '', 51, MESSAGE_ERROR);
	exit;
}

// -- On met à jour le compteur de clics
$sql = 'UPDATE scripts 
            SET s_hits = s_hits + 1
            WHERE s_id = :id;';

#REQ DOWNLOAD_2
$res = Obj::$db->prepare($sql);
$res->bindValue(':id', intval($_GET['id']), SQL::PARAM_INT);
$res->execute();
$res = null;

$file = explode('/', $data['s_path']);
$file = $file[count($file) - 1];

// -- Tout baigne, on lance le dl.
header('Pragma: no-cache');
header('Content-Type: application/octetstream; name="http://dl.talus-works.net/' . $data['s_path'] . '"');
header('Content-disposition: inline;filename=' . $file);

echo file_get_contents('../downloads/' . $data['s_path']);

/** EOF /**/
