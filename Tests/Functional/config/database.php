<?php

$container->loadFromExtension('doctrine', array(
    'dbal' => array(
        'driver' => 'pdo_sqlite',
        'path' => tempnam(sys_get_temp_dir(), 'database'),
    ),
));
