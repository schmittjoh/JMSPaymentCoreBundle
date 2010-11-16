<?php

namespace Bundle\PaymentBundle\Cryptography;

interface EncryptionServiceInterface
{
    function decrypt($encryptedValue);
    function encrypt($rawValue);
}