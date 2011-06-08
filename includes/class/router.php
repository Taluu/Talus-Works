<?php
/**
 * Gère les paramètres pour Talus' Works.
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
 * @begin 21/06/2009, Talus
 * @last 24/06/2009, Talus
 */

/**
 * Gère les paramètres donnés aux scripts Talus' Works
 */
class Router implements ArrayAccess {
    /**
     * Paramètres nommés passés
     *
     * @var array
     */
    private $_namedParams = array();

    /**
     * Paramètres sans noms
     *
     * @var array
     */
    private $_params = array();
    
    /**
     * Constructeur : renseigne la variable params
     *
     * @return void
     */
    public function __construct() {
        $params = array_filter(explode('/', trim($_SERVER['REQUEST_URI'])));

        /*
         * On travaille en Local : on vire le premier élément, car c'est le
         * dossier du projet, et ca on s'en fout :3
         */
        if (IS_LOCAL) {
            array_shift($params);
        }

        /*
         * Traitement du dernier élément ; On enlève le QueryString de celui-ci
         * si il en a un, et on vire le .html si il y est.
         */
        $last = &$params[count($params) - 1];
        list($tmp,) = explode('?', $last, 2);
        $last = preg_replace('`\.html$`', '', $tmp);


        // -- Renseignement effectif des paramètres
        $this->_namedParams['frame'] = array_shift($params);
        $this->_namedParams['command'] = implode('/', $params);
        $this->_params = $params + array('command' => implode('/', $params));
    }

    /**
     * Permet de nommer un ou plusieurs paramètres.
     * Attention, chaque paramètre nommé ne pourra plus être renommé par la suite !
     *
     * @param string $param,... Noms des paramètres (si le premier argument est
     *                          un array, alors ce sont les éléments de cet array
     *                          qui sont considérés comme étant les noms des
     *                          paramètres)
     * @return void
     */
    public function name() {
        $args = func_get_args();
        $nbArgs = func_num_args();

        /*
         * Si le premier argument est un array, alors son contenu est considéré
         * comme étant les noms des paramètres... Et dans ce cas, on ignore alors
         * les autres arguments, et on utilise cet argument pour nommer les
         * paramètres du script
         */
        if ($nbArgs > 0 && is_array($args[0])) {
            $args = $args[0];
            $nbArgs = count($arg);
        }

        $max = min($nbArgs, count($this->_params) - 1);

        for ($i = 0; $i < $max; ++$i) {
            if (in_array($args[$i], array('frame', 'command', 'extra'))) {
                trigger_error('Router->name() : frame, command et extra sont des noms '
                              . 'réservés, vous ne pouvez donc les réallouer',
                              E_USER_WARNING);
                continue;
            }

            $this->_namedParams[$args[$i]] = array_shift($this->_params);
        }

        $paramsCommand = array_pop($this->_params);
        $this->_params['command'] = implode('/', $this->_params);
    }

    public function get() { return array($this->_params, $this->_namedParams); }

    /**
     * Regarde si il existe un paramètre contenant la valeur $value, et renvoit
     * son indice si oui, false si non.
     *
     * @param mixed $value Valeur à chercher
     * @return mixed
     */
    public function contains($val) {
        $offset = array_search($val, $this->_namedParams, true);

        if ($offset === false) {
            $offset = array_search($val, $this->_params, true);
        }

        return $offset;
    }

    /**
     * Vérifie si le paramètre $offset existe
     *
     * @param mixed $offset Paramètre à tester
     * @return boolean
     */
    public function offsetExists($offset) {
        return isset($this->_namedParams[$offset]) || isset($this->_params[$offset]);
    }

    /**
     * Récupère le paramètre $offset
     *
     * @param mixed $offset Paramètre à récupérer
     * @return mixed
     */
    public function offsetGet($offset) {
        if ($offset == 'extra') {
            return explode('/', $this->_params['command']);
        }

        if (isset($this->_params[$offset])) {
            return $this->_params[$offset];
        }

        if (isset($this->_namedParams[$offset])) {
            return $this->_namedParams[$offset];
        }

        return null;
    }
    
    /**
     * Censé définir le paramètre $offset. Puisque le routeur est censé être
     * en lecture seule, cette méthode est là juste pour respecter l'obligation
     * vis à vis de l'interface ArrayAccess...
     *
     * @param mixed $offset Paramètre à définir
     * @param mixed $value Valeur à donner
     * @return void
     */
    public function offsetSet($offset, $value) {}
    
    /**
     * Censé détruire le paramètre $offset. Puisque le routeur est censé être
     * en lecture seule, cette méthode est là juste pour respecter l'obligation
     * vis à vis de l'interface ArrayAccess...
     *
     * @param mixed $offset Paramètre à détruire
     * @return void
     */
    public function offsetUnset($offset) {}
}
