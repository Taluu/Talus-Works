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

use \Symfony\Component\Validator\Mapping\ClassMetadataFactory,
    \Symfony\Component\Validator\Mapping\Loader\AnnotationLoader;

use \Monolog\Logger;

use \Silex\Provider\SecurityServiceProvider,
    \Silex\Provider\DoctrineServiceProvider,
    \Silex\Provider\MonologServiceProvider,
    \Silex\Provider\SessionServiceProvider,
    \Silex\Provider\ValidatorServiceProvider,
    \Silex\Provider\TwigServiceProvider;

use \Dflydev\Silex\Provider\DoctrineOrm\DoctrineOrmServiceProvider;

use \KevinGH\Wisdom\Silex\Provider as WisdomServiceProvider,
    \KevinGH\Wisdom\Loader\Yaml as YamlConfigLoader;

use \Doctrine\Common\Annotations\AnnotationReader;

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
     */
    public function __construct() {
        parent::__construct();

        $this['debug'] = (bool) getenv('IS_DEBUG') ?: false;

        // register silex providers
        $this->register(new SessionServiceProvider);

        $this->register(new ValidatorServiceProvider);
        $this['validator.mapping.class_metadata_factory'] = $this->share(function ($app) {
            return new ClassMetadataFactory(new AnnotationLoader(new AnnotationReader));
        });

        $this->register(new SecurityServiceProvider, array(
            'security.firewalls'      => [],

            'security.providers'      => ['main' => ['entity' => ['class'    => '\\Talus_Works\\Entity\\User',
                                                                  'property' => 'username']
                                                    ]
                                         ],

            'security.role_hierarchy' => ['ROLE_ADMIN'     => 'ROLE_MODERATOR',
                                          'ROLE_MODERATOR' => 'ROLE_USER'],
        ));

        $this->register(new MonologServiceProvider, array(
            'monolog.logfile' => __DIR__ . '/../../app/logs/' . ($this['debug'] ? 'debug' : 'prod') . '.log',
            'monolog.level'   => $this['debug'] ? Logger::DEBUG : Logger::ERROR,
            'monolog.name'    => 'twk'
        ));

        $this->register(new TwigServiceProvider, array(
            'twig.path'    => __DIR__ . '/Resources/views',

            'twig.options' => ['debug' => $this['debug'],
                               'cache' => __DIR__ . '/../../app/cache/tpl']
        ));

        $this->register(new WisdomServiceProvider, array(
            'wisdom.path'    => __DIR__ . '/../../app/config',
            'wisdom.options' => ['cache_path' => __DIR__ . '/../../app/cache/config']
        ));

        $this['wisdom']->addLoader(new YamlConfigLoader);

        $this->register(new DoctrineServiceProvider, array(
            'db.options' => $this['wisdom']->get('config.yml')['database']
        ));

        $this->register(new DoctrineORMServiceProvider, array(
            'orm.proxies_dir' => __DIR__ . '/../../app/cache/doctrine/proxies',

            'orm.em.options'  => ['mappings' => [['type'                         => 'annotation',
                                                  'path'                         => __DIR__ . '/Entity',
                                                  'namespace'                    => 'Talus_Works\Entity',
                                                  'use_simple_annotation_reader' => false]]]
        ));

        // -- load controllers
        $this->mount('/forums', new ForumController);
        $this->mount('/downloads', new DownloadController);

        // -- general links
        $this->match('/login', function (Application $app) { return 'todo : handle logging in'; })
             ->before(function () {
                if ($this['session']->has('userId')) {
                    throw new AccessDeniedRedirectedException('You\'re already logged in !', '/');
                }
             });

        $this->match('/logout', function (Application $app) { return 'todo : handle logging out'; })
             ->before(function () {
                if (!$this['session']->has('userId')) {
                    throw new AccessDeniedRedirectedException('You\'re already logged out !', '/');
                }
             });

        // todo : Use another home, instead of forums ?
        $this->match('/', function (Application $app) { return $app->redirect('/forums'); });

        // errors
        $this->error(function (AccessDeniedRedirectedException $e) {
            $this['session']->setFlash('error', $e->getMessage());

            return $this->redirect($e->getUrl());
        });
    }
}
