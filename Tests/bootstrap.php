<?php

use Doctrine\Common\Annotations\AnnotationRegistry;

// Composer
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    throw new \RuntimeException('Could not find vendor/autoload.php, make sure you ran composer.');
}

$loader = require_once __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader(array($loader, 'loadClass'));

if (class_exists('Propel')) {
    set_include_path(__DIR__ . '/../vendor/phing/phing/classes' . PATH_SEPARATOR . get_include_path());
    $class = new \ReflectionClass('TypehintableBehavior');
    $builder = new \PropelQuickBuilder();
    $builder->getConfig()->setBuildProperty('behavior.typehintable.class', $class->getFileName());
    $builder->setSchema(file_get_contents(__DIR__.'/../Resources/config/propel/schema.xml'));
    $builder->setClassTargets(array('tablemap', 'peer', 'object', 'query'));
    $builder->build();
}

return $loader;
