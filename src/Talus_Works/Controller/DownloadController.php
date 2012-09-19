<?php

namespace Talus_Works\Controller;

use \Silex\ControllerProviderInterface,
    \Silex\Application,
    \Silex\ControllerCollection;

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
