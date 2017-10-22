<?php
// DIC configuration

use Portal\Core\Model\PortalAuth;
use Portal\Core\Utils;

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};
$container['view'] = function ($container) {
    $settings = $container->get('settings')['renderer'];

    $view = new \Slim\Views\Twig($settings['template_path'], [
        'cache' => $settings['cache_path']
    ]);

//    $view->getEnvironment()->addGlobal('portal_uri', base64_encode($container['request']->getUri()));

    $view->addExtension(new \Slim\Views\TwigExtension(
        $container['router'],
        $container['request']->getUri()
    ));

    /**
     * Filters
     */
    $filters = array();
    $filters[] = new Twig_SimpleFilter('portalpartial', function ($context, $template) {
        if(!empty($context['template_path'])) {
            $parts = array(
                $context['template_path'],
                '..',
                DIRECTORY_SEPARATOR,
                '_partial',
                DIRECTORY_SEPARATOR,
                $template,
                '.',
                $context['template_extension']
            );
            return implode('', $parts);
        }

        return $template;
    }, array('needs_context' => true));

    $filters[] = new Twig_SimpleFilter('nameToKey', function ($name) {
        return Utils::nameToKey($name);
    });

    $filters[] = new Twig_SimpleFilter('extractSiteName', function ($name) {
        return Utils::extractSiteName($name);
    });

    foreach($filters as $filter) {
        $view->getEnvironment()->addFilter($filter);
    }

    /**
     * Functions
     */
    $functions = array();
    $functions[] = new Twig_SimpleFunction('is_logged_in', function () {
        return PortalAuth::isLoggedIn();
    });

    $functions[] = new Twig_SimpleFunction('user', function () {
        return PortalAuth::currentUser();
    });

    foreach($functions as $function) {
        $view->getEnvironment()->addFunction($function);
    }

    return $view;
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], Monolog\Logger::DEBUG));
    return $logger;
};
