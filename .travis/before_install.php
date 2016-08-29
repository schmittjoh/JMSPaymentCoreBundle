#!/usr/bin/env php
<?php

include_once 'common.php';

if (!isHhvm()) {
    // Disable XDebug
    run('phpenv config-rm xdebug.ini');
}
