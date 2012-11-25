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

use \Talus_Works\Application;

use \Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require '../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

$app = Application::prepare();
$app->run();
