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
    return getPhpVersion() === '7.1'; // @todo change to 7.2 once the project fully supports it
}

function isNonExperimentalPhp()
{
    return getPhpVersion() !== 'nightly';
}

function isLatestSymfony()
{
    return in_array(getSymfonyVersion(), array('3.4.*', '4.0.*'), true);
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
