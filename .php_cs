<?php

$finder = Symfony\CS\Finder\DefaultFinder::create()
    ->in(__DIR__)
;

return Symfony\CS\Config\Config::create()
    ->level(Symfony\CS\FixerInterface::SYMFONY_LEVEL)
    ->fixers(array(
        'ordered_use',
        '-unalign_double_arrow',
        '-operators_spaces',
    ))
    ->setUsingCache(true)
    ->finder($finder)
;
