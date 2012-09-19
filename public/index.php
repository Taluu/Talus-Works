<?php

use Taluu\Talus_Works\Application;

require '../vendor/autoload.php';

$app = Application::prepare(Application::DEBUG);

$app->get('/', function (Application $app) {
    $app['monolog']->addDebug('test');

    return 'Hello ! <br />';
});

$app->run();
