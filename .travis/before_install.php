#!/usr/bin/env php
<?php

include_once 'common.php';

# Prevent Travis throwing an out of memory error on older PHP
if (getPhpVersion() < '5.6') {
    run('echo "memory_limit=-1" >> ~/.phpenv/versions/5.3/etc/conf.d/travis.ini');
}

// Disable XDebug
run('phpenv config-rm xdebug.ini');
