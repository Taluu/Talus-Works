<?php

namespace Talus_Works\Controller;

use \Silex\Application,
    \Silex\ControllerCollection,
    \Silex\ControllerProviderInterface;

use \Symfony\Component\HttpFoundation\Request,
    \Symfony\Component\HttpKernel\HttpKernelInterface;

class ForumController implements ControllerProviderInterface {
    /**
     * Returns routes to connect to the given application.
     *
     * @param Application $app An Application instance
     *
     * @return ControllerCollection A ControllerCollection instance
     */
    public function connect(Application $app) {
        /** @var $forum ControllerCollection */
        $forum = $app['controllers_factory'];

        $forum->match('/forum/{slug}/{page}', array($this, 'getForumData'))
            ->bind('viewforum')
            ->assert('slug', '[a-z0-9-]+')
            ->assert('page', '\d+')
            ->value('page', 1);

        $forum->match('/topic/{slug}/{page}', array($this, 'getPosts'))
            ->bind('viewtopic')
            ->assert('slug', '[a-z0-9-]+')
            ->assert('page', '\d+')
            ->value('page', 1);

        $forum->match('/', array($this, 'getForumData'));

        return $forum;
    }

    public function getForumData(Application $app, $slug = null, $page = 1) {
        ob_start(); var_dump($slug, $page); $content = ob_get_clean();

        return 'todo : ' . $content;
    }

    public function getPosts(Application $app, $slug, $page = 1) {
        return 'todo';
    }
}
