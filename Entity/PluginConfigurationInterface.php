<?php

namespace Bundle\PaymentBundle\Entity;

interface PluginConfigurationInterface
{
    function getName();
    function getOptions();
    function isIndependentCreditSupported();
}