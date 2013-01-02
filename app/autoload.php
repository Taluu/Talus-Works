<?php

use \Doctrine\Common\Annotations\AnnotationRegistry;

$loader = require '../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));
