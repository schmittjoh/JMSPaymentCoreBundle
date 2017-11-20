#!/usr/bin/env php
<?php

include_once 'common.php';

// Disable XDebug for non-experimental PHP environments
if (isNonExperimentalPhp()) {
    run('phpenv config-rm xdebug.ini');
}

// Set composer minimum-stability configuration filter to beta versions
if (usesBetaDependencies()) {
    run('perl -pi -e \'s/^}$/,"minimum-stability":"beta"}/\' composer.json');
}
