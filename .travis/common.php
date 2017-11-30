<?php

function shouldBuildDocs()
{
    return isLatestPhp() && isLatestSymfony();
}

function usesBetaDependencies()
{
    return getenv('DEPENDENCIES') === 'beta';
}

function isLatestPhp()
{
    return getPhpVersion() === '7.2';
}

function isNonExperimentalPhp()
{
    return getPhpVersion() !== 'nightly';
}

function isLatestSymfony()
{
    return getSymfonyVersion() === '3.3.*';
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
