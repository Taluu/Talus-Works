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

namespace Talus_Works;

use \Silex\Application as BaseApplication;

use \Symfony\Component\Yaml\Yaml,
    \Symfony\Component\Security\Core\Exception\AccessDeniedException;

use \Monolog\Logger;

use \Silex\Provider\SecurityServiceProvider,
    \Silex\Provider\DoctrineServiceProvider,
    \Silex\Provider\MonologServiceProvider,
    \Silex\Provider\SessionServiceProvider,
    \Silex\Provider\TwigServiceProvider;

use \Nutwerk\Provider\DoctrineORMServiceProvider;

use \Doctrine\Common\Cache\ArrayCache;

use \Talus_Works\Controller\ForumController,
    \Talus_Works\Controller\DownloadController,

    \Talus_Works\Exception\Security\AccessDeniedRedirectedException;

/**
 * Main Application. Extended to prepare stuff.
 *
 * @author Baptiste "Talus" Clavié <clavie.b@gmail.com>
 */
class Application extends BaseApplication {
    /**
     * Prepare the application, sets the right things on their way
     *
     * @throws AccessDeniedRedirectedException
     * @return Application
     */
    public static function prepare() {
        $app = new self;

        $app['config'] = []; //todo : use provider ?
        $app['debug'] = (bool) getenv('IS_DEBUG') ?: false;

        // register silex providers
        $app->register(new SessionServiceProvider);

        $app->register(new SecurityServiceProvider, array(
            'security.firewalls'      => [],

            'security.providers'      => ['main' => ['entity' => ['class'    => '\\Talus_Works\\Entity\\User',
                                                                  'property' => 'username']
                                                    ]
                                         ],

            'security.role_hierarchy' => ['ROLE_ADMIN'     => 'ROLE_MODERATOR',
                                          'ROLE_MODERATOR' => 'ROLE_USER'],
        ));

        $app->register(new MonologServiceProvider, array(
            'monolog.logfile' => __DIR__ . '/Resources/logs/' . ($app['debug'] ? 'debug' : 'prod') . '.log',
            'monolog.level'   => $app['debug'] ? Logger::DEBUG : Logger::ERROR,
            'monolog.name'    => 'twk'
        ));

        $app->register(new TwigServiceProvider, array(
            'twig.path'    => __DIR__ . '/Resources/views',

            'twig.options' => ['debug' => $app['debug'],
                               'cache' => __DIR__ . '/../../cache/tpl']
        ));

        $app->register(new DoctrineServiceProvider, array(
            'db.options' => Yaml::parse(__DIR__ . '/Resources/config/sql.yml')['database']
        ));

        $app->register(new DoctrineORMServiceProvider, array(
            'db.orm.auto_generate_proxies' => $app['debug'],
            'db.orm.proxies_dir'           => __DIR__ . '/../../cache/doctrine/Proxy',
            'db.orm.cache'                 => new ArrayCache,

            'db.orm.entities'              => [['type'      => 'annotation',
                                                'path'      => __DIR__ . '/Entity',
                                                'namespace' => '\Talus_Works\Entity']]
        ));

        // -- load controllers
        $app->mount('/forums', new ForumController);
        $app->mount('/downloads', new DownloadController);

        // -- general links
        $app->match('/login', function (Application $app) { return 'todo : handle logging in'; })
            ->before(function () use ($app) {
                if ($app['session']->has('userId')) {
                    throw new AccessDeniedRedirectedException('You\'re already logged in !', '/');
                }
            });

        $app->match('/logout', function (Application $app) { return 'todo : handle logging out'; })
            ->before(function () use ($app) {
                if (!$app['session']->has('userId')) {
                    throw new AccessDeniedRedirectedException('You\'re already logged out !', '/');
                }
            });

        // todo : Use another home, instead of forums ?
        $app->match('/', function (Application $app) { return $app->redirect('/forums'); });

        // errors
        $app->error(function (AccessDeniedRedirectedException $e) use ($app) {
            $app['session']->setFlash('error', $e->getMessage());

            return $app->redirect($e->getUrl());
        });

        return $app;
    }
}
