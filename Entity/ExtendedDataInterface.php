<?php

namespace Bundle\PaymentBundle\Entity;

interface ExtendedDataInterface
{
    function isEncryptionRequired($name);
    function remove($name);
    function set($name, $value, $encrypt = false);
    function get($name);
}