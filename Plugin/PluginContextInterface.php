<?php

namespace Bundle\PaymentBundle\Plugin;

interface PluginContextInterface
{
    function getConfiguration();
    function getLocale();
}