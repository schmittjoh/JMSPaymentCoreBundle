<?php

use Symfony\Component\HttpKernel\Kernel;

$assets = array('assets' => false);

if (version_compare(Kernel::VERSION, '2.7', '<')) {
    // The 'assets' configuration is only available for Symfony >= 2.7
    $assets = array();
}

$container->loadFromExtension('framework', array_merge($assets, array(
    'secret' => 'test',
    'test' => true,
    'session' => array(
        'storage_id' => 'session.storage.mock_file',
    ),
    'templating' => array(
        'engines' => array('twig', 'php'),
    ),
    'router' => array(
        'resource' => '%kernel.root_dir%/TestBundle/Resources/config/routing.yml',
    ),
    'form' => true,
    'validation' => array(
        'enabled' => true,
        'enable_annotations' => true,
    ),
)));
