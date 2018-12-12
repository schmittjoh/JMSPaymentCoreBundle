#!/usr/bin/env php
<?php

include_once 'common.php';

if (isLatestPhp() && isLatestSymfony()) {
    // Make sure composer.json references all necessary components by having one
    // job run a `composer update`. Since `composer update` will install the
    // latest Symfony, this should be done for the job corresponding to the
    // latest symfony version.
    run('COMPOSER_MEMORY_LIMIT=-1 composer update --prefer-dist');
} else {
    run('COMPOSER_MEMORY_LIMIT=-1 composer require --prefer-dist symfony/symfony:'.getSymfonyVersion());
}

if (shouldBuildDocs()) {
    run('sudo apt-get -qq update');
    run('sudo apt-get install -y graphviz python3 python3-pip');
    run('pip3 install --user virtualenv');
    run('virtualenv venv');
    run('venv/bin/pip install -r requirements.txt');
}
