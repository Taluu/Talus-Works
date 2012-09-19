<?php

use Talus_Works\Application;

require '../vendor/autoload.php';

$app = Application::prepare(Application::DEBUG);

$app->get('/', function (Application $app) {
    return 'Hello ! <br />';
});

$app->run();
