<?php
return [
    'settings' => [
        'displayErrorDetails' => true, // set to false in production

        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
            'cache_path' => false, //__DIR__ . '/../cache/',
            'template_extension' => 'twig'
        ],

        // Monolog settings
        'logger' => [
            'name' => 'slim-app',
            'path' => __DIR__ . '/../logs/app.log',
        ],

        // Controllers settings
        'controller' => [
            'path' => __DIR__ . '/../app/Modules',
            'dirname' => 'Controller',
            'methodSuffix' => 'Action'
        ],

        'upload_directory' => [
            'path' => __DIR__ . '/../public/uploads',
            'url' => '/uploads'
        ],

        'db' => [
            'host' => 'localhost',
            'name' => 'portal',
            'user' => 'root',
            'pass' => ''
        ]
    ],
];
