<?php

use Symfony\Component\HttpKernel\Kernel;

$files = [
    'database.php',
    'framework.php',
    'doctrine.yml',
];

if (version_compare(Kernel::VERSION, '3.0', '>=')) {
    $files[] = 'services.yml';
}

foreach ($files as $file) {
    $loader->import($file);
}
