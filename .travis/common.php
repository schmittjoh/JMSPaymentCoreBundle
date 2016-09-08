<?php

function withCodeCoverage()
{
    return isLatestPhp() && in_array(getSymfonyVersion(), array('2.3.*', '3.1.*'));
}

function isHhvm()
{
    return getPhpVersion() === 'hhvm';
}

function isLatestPhp()
{
    return getPhpVersion() === '7.0';
}

function isLatestSymfony()
{
    return getSymfonyVersion() === '3.1.*';
}

function getSymfonyVersion()
{
    return getenv('SYMFONY_VERSION');
}

function getPhpVersion()
{
    return exec('phpenv version-name');
}

function run($command)
{
    echo "$ $command\n";

    passthru($command, $ret);

    if ($ret !== 0) {
        exit($ret);
    }
}
