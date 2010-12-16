<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Cryptography;

/**
 * Interface for encryption services
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface EncryptionServiceInterface
{
    /**
     * This method decrypts the passed value.
     * 
     * @param string $encryptedValue
     */
    function decrypt($encryptedValue);
    
    /**
     * This method encrypts the passed value. 
     * 
     * Binary data may be base64-encoded.
     * 
     * @param string $rawValue
     */
    function encrypt($rawValue);
}