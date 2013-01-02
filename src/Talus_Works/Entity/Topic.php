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

namespace Talus_Works\Entity;

use \Doctrine\ORM\Mapping as ORM;

/**
 * Topic Entity
 *
 * @ORM\Entity(repositoryClass="Talus_Works\Entity\Repository\Topic")
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Topic {
    /**
     * @ORM\Id
     * @ORM\Column(name = "id", type = "integer")
     * @ORM\GeneratedValue(strategy = "AUTO")
     */
    private $id;
}
