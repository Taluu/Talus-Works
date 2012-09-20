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

namespace Talus_Works\Controller;

use \Silex\ControllerProviderInterface,
    \Silex\Application,
    \Silex\ControllerCollection;

/**
 * Handle the download of scripts
 *
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class DownloadController implements ControllerProviderInterface {
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app) {
        /** @var $download ControllerCollection */
        $download = $app['controllers_factory'];

        $download->get('/{soft}/{tag}', array($this, 'download'));

        return $download;
    }

    public function download(Application $app, $soft, $tag) {
        return 'todo';
    }
}
