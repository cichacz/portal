<?php

use Portal\Core\PortalDb;

if (PHP_SAPI != 'cli') {
    exit('Error');
}

require __DIR__ . '/../vendor/autoload.php';

// Instantiate the app
$settings = require __DIR__ . '/../src/settings.php';
$app = new \Slim\App($settings);

$container = $app->getContainer();
$settings = $container->get('settings');

//Set up db
PortalDb::getInstance($settings['db']);
