<?php
/**
 * Regroupe les fonctions de gestion de la RI
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
 * @copyright ©Talus, Talus' Works 2007+
 * @link http://www.talus-works.net Talus' Works
 * @license http://www.gnu.org/licenses/gpl.html GNU Public License 2+
 * @begin 24/12/2007, Talus
 * @last 24/07/2009, Talus
 */

if( !( defined('COMMON') && COMMON == true ) ){
    header('Location: ../../index.php');
    exit;
}

/**
 * Regroupe les fonctions de gestion de la RI.
 * 
 * @author Talus <talusch@gmail.com>
 * @package Talus' Works
 * @link http://www.siteduzero.com/tuto-3-20017-1-la-representation-intervallaire.html Tutoriel sur la Représentation Intervallaire d'arbre en SQL
 * @link http://sqlpro.developpez.com/cours/arborescence/ Cours sur la gestion d'arborescence en SQL (par Frederic Brouard)
 * @todo Déplacement haut / bas de noeuds, au sein d'un même parent
 */
class RI {
    /**
     * Contient la table en cours d'utilisation
     * 
     * @var string
     * @static
     */
    private static $_table = 'forums';

    /**
     * Cache instantané
     *
     * @var array
     */
    private static $_cache = array('forums' => array());
    
    /**
     * Constantes de la classe
     */
    const CURRENT_NOT_INCLUDED = true;
    const CURRENT_INCLUDED = false;
    
    /**
     * Configure la table à utiliser.
     * 
     * @param string Table à utiliser
     * @return void
     * @static
     */
    public static function set($table = 'forums'){
        self::$_table = $table;

        if (!isset(self::$_cache[self::$_table])) {
            self::$_cache[self::$_table] = array();
        }
    }
    
    /**
     * Ajoute une feuille dans l'arbre.
     * 
     * @param array $data Données de la feuille à insérer
     * @param integer parent ID du parent de la feuille à insérer.
     * @return bool
     */
    public static function add($data, $parent = 0){
        if (!is_array($data)) {
            return false;
        }

        $tree = self::getTree();
        
        /*
         * Si ce n'est pas une nouvelle catégorie, on selectionne la borne droite
         * et le level du forum parent : sinon, on sélectionne la borne la plus à
         * droite, plus un, pour insérer une nouvelle racine.
         */
        if ($parent != 0 && isset($tree[$parent])) {
            $parent_data = array(
                    'f_right' => $tree[$parent]['f_right'],
                    'f_level' => $tree[$parent]['f_level']
                );

        } else {
            $sql = 'SELECT (COALESCE(MAX(f_right), 0) + 1) as f_right, -1 as f_level
                        FROM ' . self::$_table . ';';

            // -- Récupération des données
            $res = Obj::$db->query($sql);
            $parent_data = $res->fetch(SQL::FETCH_ASSOC);
            $res = null;
        }
        
        // -- Décalage des bornes droite & gauche
        $sql = 'UPDATE ' . self::$_table . '
                    SET f_right = f_right + 2
                    WHERE f_right >= ' . $parent_data['f_right'] . ';';
        
        #REQ RI_2
        Obj::$db->exec($sql);
        
        $sql = 'UPDATE ' . self::$_table . '
                    SET f_left = f_left + 2
                    WHERE f_left >= ' . $parent_data['f_right'] . ';';
        
        #REQ RI_3
        Obj::$db->exec($sql);
        
        // -- On insère les données de la nouvelle feuille.
        $data = array_merge($data, array(
                'f_left' => $parent_data['f_right'],
                'f_right' => $parent_data['f_right'] + 1,
                'f_level' => $parent_data['f_level'] + 1,
                'f_parent' => $parent
            ));
        
        $fields = implode(', ', array_keys($data));

        $values = array();
        foreach (array_keys($data) as $field) {
            $values[] = ":{$field}";
        }

        $values = implode(', ', $values);
        
        $sql = 'INSERT INTO ' . self::$_table . ' (' . $fields . ')
                    VALUES (' . $values . ');';
        
        #REQ RI_4
        $res = Obj::$db->prepare($sql);

        foreach ($data as $field => $value) {
            $res->bindValue(":{$field}", $value);
        }

        $res->execute();

        // -- Destruction du cache
        unset(self::$_cache[self::$_table]['tree']);

        return true;
    }
    
    /** 
     * Supprime un élément de l'arbre.
     * 
     * @param integer $id ID de l'élément.
     * @return bool
     */
    public static function delete($id){
        $id = (int) $id;
        $nodes = self::getTree();
        
        if (!isset($nodes[$id])){
            return false;
        }
        
        // -- Suppression de l'arbre
        $sql = 'DELETE FROM ' . self::$_table . '
                    WHERE f_left >= :left
                        AND f_right <= :right;';
        
        #REQ RI_6
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':left', $nodes[$id]['f_left'], SQL::PARAM_INT);
        $res->bindValue(':right', $nodes[$id]['f_right'], SQL::PARAM_INT);
        $res->execute();
        $res = null;

        $diff = $nodes[$id]['f_right'] - $nodes[$id]['f_left'] + 1;
        
        // -- Rebouchage des trous
        $sql = 'UPDATE ' . self::$_table . '
                    SET f_left = f_left - :diff
                    WHERE f_left >= :left;';
        
        #REQ RI_7
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':left', $nodes[$id]['f_left'], SQL::PARAM_INT);
        $res->bindValue(':diff', $diff, SQL::PARAM_INT);
        $res->execute();
        $res = null;
        
        $sql = 'UPDATE ' . self::$_table . '
                    SET f_right = f_right - :diff
                    WHERE f_right >= :right;';
        
        #REQ RI_8
        $res = Obj::$db->prepare($sql);
        $res->bindValue(':right', $nodes[$id]['f_right'], SQL::PARAM_INT);
        $res->bindValue(':diff', $diff, SQL::PARAM_INT);
        $res->execute();
        $res = null;

        unset(self::$_cache[self::$_table]['tree']);
        
        return true;
    }

    /**
     * Met un noeud à jour
     *
     * @param integer $id ID du noeud
     * @param array $datas Données à mettre à jour.
     * @return bool
     */
    public static function update($id, array $datas) {
        $id = (int) $id;
        $upd = array();

        $nodes = self::getTree();

        if (!$datas  || !$id || !isset($nodes[$id])) {
            return false;
        }
        
        $current = $nodes[$id];

        // -- Parcour des elements à mettre à jour, puis mise à jour
        foreach ($datas as $field => $value) {
            if ($field == 'f_parent') continue;
            $upd[] = "{$field} = :{$field}";
        }

        $sql = 'UPDATE ' . self::$_table . '
                    SET ' . implode(', ', $upd) . '
                    WHERE f_id = :fid';

        $res = Obj::$db->prepare($sql);
        $res->bindValue(':fid', $id, SQL::PARAM_INT);

        foreach ($datas as $field => $value){
            if ($field == 'f_parent') continue;
            $res->bindValue(":{$field}", $value);
        }

        $res->execute();
        $res = null;

        // -- Mise à jour du parent, si besoin est
        if (isset($datas['f_parent']) && $current['f_parent'] != $datas['f_parent']) {
            /*
             * Le but est d'extraire l'arbre, de le supprimer puis de le
             * réinsérer à la bonne place...
             */
            $curTree = array_reverse(self::getChildren($id));
            $parent = array();

            foreach ($curTree as $key => $node) {
                $parent[$key] = $node['f_parent'];
                unset($curTree[$key]['f_parent'], $curTree[$key]['f_left'],
                      $curTree[$key]['f_right'], $curTree[$key]['f_level']);
            }

            $parent[0] = $datas['f_parent'];

            self::delete($id);

            foreach ($curTree as $key => &$node) {
                self::add($node, $parent[$key]);
            }
        }

        unset(self::$_cache[self::$_table]['tree']);

        return true;
    }
    
    /**
     * Récupère l'arbre complet.
     * 
     * @param bool $upd Regénérer le cache ?
     * @return array
     */
    public static function getTree($upd = false){
        // -- Pas de cache ? On le regénère...
        if (!isset(self::$_cache[self::$_table]['tree']) || $upd){

            $sql = 'SELECT *
                        FROM ' . self::$_table . '
                        ORDER BY f_left ASC;';
            
            #REQ RI_23
            $res = Obj::$db->query($sql);
            $tree = $res->fetchAll(SQL::FETCH_ASSOC);
            $res = null;
            
            self::$_cache[self::$_table]['tree'] = array();
            
            foreach ($tree as &$node) {
                self::$_cache[self::$_table]['tree'][$node['f_id']] = $node;
            }
        }
        
        return self::$_cache[self::$_table]['tree'];
    }
    
    /**
     * Récupère la filière descendante d'un noeud.
     * 
     * @param integer $id ID de la feuille
     * @return array
     * @static
     */
    public static function getChildren($id){
        $id = (int)$id;
        
        // -- On récupère l'arbre complet de l'arborescence
        $tree = self::getTree();
        
        // -- Si l'id est inexistant, on renvoit un array vide
        if (!isset($tree[$id])) {
            return array();
        }
        
        $childs = array();
        
        foreach ($tree as &$node) {
            if ($node['f_left'] < $tree[$id]['f_left']) continue;
            if ($node['f_right'] > $tree[$id]['f_right']) break;
            
            $childs[] = $node;
        }
        
        return array_reverse($childs);
    }
}

/** EOF /**/
