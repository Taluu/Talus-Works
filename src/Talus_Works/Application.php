<?php
/**
 * Todo : make a real header
 */

namespace Talus_Works;

use \Silex\Application as BaseApplication;

use \Symfony\Component\Yaml\Yaml;

use \Monolog\Logger;

use \Talus_Works\Controller\DownloadController,
    \Talus_Works\Controller\ForumController;


class Application extends BaseApplication {
    /**
     * Prepare the application, sets the right things on their way
     *
     * @return Application
     */
    public static function prepare() {
        $app = new self;

        $app['config'] = []; //todo : use provider ?
        $app['debug'] = (bool) getenv('IS_DEBUG') ?: false;

        // register silex providers
        $app->register(new \Silex\Provider\ValidatorServiceProvider);
        $app->register(new \Silex\Provider\SessionServiceProvider);

        $app->register(new \Silex\Provider\SecurityServiceProvider, array(
            'security.firewalls' => array()
        ));

        $app->register(new \Silex\Provider\MonologServiceProvider, array(
            'monolog.logfile' => __DIR__ . '/Resources/logs/' . ($app['debug'] ? 'debug' : 'prod') . '.log',
            'monolog.level'   => $app['debug'] ? Logger::DEBUG : Logger::ERROR,
            'monolog.name'    => 'twk'
        ));

        $app->register(new \Silex\Provider\FormServiceProvider, array(
            'form.secret' => sha1(__DIR__)
        ));

        $app->register(new \Silex\Provider\TwigServiceProvider, array(
            'twig.path' => __DIR__ . '/Resources/views'
        ));

        $app->register(new \Silex\Provider\DoctrineServiceProvider, array(
            'db.options' => Yaml::parse(__DIR__ . '/Resources/config/sql.yml') ['database']
        ));

        // -- load controllers
        $app->mount('/forums', new ForumController);
        $app->mount('/downloads', new DownloadController);

        return $app;
    }
}
