<?php
/**
 * Todo : make a real header
 */

namespace Talus_Works;

use \Silex\Application as BaseApplication;

use \Symfony\Component\Yaml\Yaml;

use \Monolog\Logger;


class Application extends BaseApplication {
    const DEBUG = 0, PROD = 1;

    /**
     * Prepare the application, sets the right things on their way
     *
     * @param integer $_env Environment used (either debug or prod)
     *
     * @return Application
     */
    public static function prepare($_env = self::PROD) {
        $app = new self;

        // debug ?
        if ($_env === self::DEBUG) {
            $app['debug'] = true;
        }

        // todo : load global config ?
        $sqlAccess = Yaml::parse(file_get_contents(__DIR__ . '/Resources/config/sql.yml'))['database'];

        // register silex providers
        $app->register(new \Silex\Provider\ValidatorServiceProvider);
        //$app->register(new \Silex\Provider\SecurityServiceProvider);
        $app->register(new \Silex\Provider\SessionServiceProvider);

        $app->register(new \Silex\Provider\MonologServiceProvider, array(
            'monolog.logfile' => __DIR__ . '/Resources/logs/' . ($_env === self::DEBUG ? 'debug' : 'prod') . '.log',
            'monolog.level'   => $_env === self::DEBUG ? Logger::DEBUG : Logger::Error
        ));

        $app->register(new \Silex\Provider\FormServiceProvider, array(
            'form.secret' => sha1(__DIR__)
        ));

        $app->register(new \Silex\Provider\TwigServiceProvider, array(
            'twig.path' => __DIR__ . '/Resources/views'
        ));

        $app->register(new \Silex\Provider\DoctrineServiceProvider, array(
            'db.options' => $sqlAccess
        ));

        // -- todo : load the right controller...

        return $app;
    }
}
