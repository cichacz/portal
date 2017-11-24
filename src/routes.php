<?php

use Portal\Core\PortalController;
use Portal\Core\Utils;

$controllerSettings = $settings['controller'];
$directory = $controllerSettings['path'];
$controllerMethodSuffix = $controllerSettings['methodSuffix'];

$modules = array_diff(scandir($directory), array('..', '.'));

$di = new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS);
$it = new RecursiveIteratorIterator($di);

$controllers = array();
foreach($it as $file) {
    if (Utils::endsWith(pathinfo($file, PATHINFO_FILENAME), "Controller")) {
        $controllers[] = realpath($file);
    }
}

PortalController::setup($app->getContainer());

sort($controllers);

foreach($controllers as $controller) {
    $controllerInfo = PortalController::getControllerInfo($controller);

    if(is_array($controllerInfo)) {
        foreach($controllerInfo['routes'] as $method => $routes) {
            foreach($routes as $route) {
                $routeObj = call_user_func_array(array($app, $method), $route);
                $routeObj->setName(PortalController::getRouteName($route[0]));
            }
        }
    }
}