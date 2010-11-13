<?php

namespace Bundle\PaymentBundle\Entity;

interface PluginContextInterface
{
    function getConfiguration();
    function getLocale();
}