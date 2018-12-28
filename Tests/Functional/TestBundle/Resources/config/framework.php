<?php

use Symfony\Component\HttpKernel\Kernel;

$config = [
    'form' => true,
    'router' => [
        'resource' => '%kernel.root_dir%/TestBundle/Resources/config/routing.yml',
    ],
    'secret' => 'test',
    'session' => [
        'storage_id' => 'session.storage.mock_file',
    ],
    'templating' => [
        'engines' => ['twig', 'php'],
    ],
    'test' => true,
    'validation' => [
        'enabled' => true,
        'enable_annotations' => true,
    ],
];

if (version_compare(Kernel::VERSION, '2.7', '>=')) {
    // The 'assets' configuration is only available for Symfony >= 2.7
    $config['assets'] = false;
}

if (version_compare(Kernel::VERSION, '4.0', '<')) {
    $config['router'] = [
        'resource' => '%kernel.root_dir%/TestBundle/Resources/config/routing_legacy.yml',
    ];
}

$container->loadFromExtension('framework', $config);
