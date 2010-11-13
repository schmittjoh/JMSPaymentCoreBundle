<?php

namespace Bundle\PaymentBundle\Plugin;

interface PluginConfigurationInterface
{
    function getName();
    function getOptions();
    function isIndependentCreditSupported();
}