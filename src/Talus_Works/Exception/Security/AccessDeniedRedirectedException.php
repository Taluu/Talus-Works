<?php
/**
 * This file is part of Talus' Works.
 *
 * For the full copyright and license information, please view the LICENSE file
 * that was distributed with this source code.
 *
 * @copyright Copyleft (c) 2012+, Baptiste Clavié, Talus' Works
 * @link      http://www.talus-works.net Talus' Works
 * @license   http://www.opensource.org/licenses/BSD-3-Clause Modified BSD License
 * @version   $Id$
 */

namespace Talus_Works\Exception\Security;

use \Symfony\Component\Security\Core\Exception\AccessDeniedException as AccessDeniedException;

use \Talus_Works\Application;

/**
 * Exception thrown when a user can't have access to something
 *
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class AccessDeniedRedirectedException extends AccessDeniedException {
    private $_url = null;

    /**
     * @param string     $_message
     * @param string     $_url
     * @param \Exception $_parent
     */
    public function __construct($_message, $_url, \Exception $_parent = null) {
        $this->_url = $_url;

        parent::__construct($_message, $_parent);
    }

    /** @return string */
    public function getUrl() { return $this->_url; }
}
