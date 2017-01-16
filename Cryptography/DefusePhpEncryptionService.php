<?php

namespace JMS\Payment\CoreBundle\Cryptography;

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class DefusePhpEncryptionService implements EncryptionServiceInterface
{
    private $key;

    public function __construct($secret)
    {
        $this->key = Key::loadFromAsciiSafeString($secret);
    }

    public function decrypt($encryptedValue)
    {
        return Crypto::decrypt($encryptedValue, $this->key);
    }

    public function encrypt($rawValue)
    {
        return Crypto::encrypt($rawValue, $this->key);
    }
}
