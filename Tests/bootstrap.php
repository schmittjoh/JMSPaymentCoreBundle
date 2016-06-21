<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

// Composer
if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    $loader = require __DIR__.'/../vendor/autoload.php';

    AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

    return $loader;
}

throw new \RuntimeException('Could not find vendor/autoload.php, make sure you ran composer.');
