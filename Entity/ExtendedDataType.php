<?php

namespace Bundle\PaymentBundle\Entity;

use Bundle\PaymentBundle\Cryptography\EncryptionServiceInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\ObjectType;

class ExtendedDataType extends ObjectType
{
    const NAME = 'extended_payment_data';
    
    protected static $encryptionService;
    
    public static function setEncryptionService(EncryptionServiceInterface $service)
    {
        self::$encryptionService = $service;
    }
    
    public static function getEncryptionService()
    {
        return self::$encryptionService;
    }
    
    public function convertToDatabaseValue($extendedData, AbstractPlatform $platform)
    {
        if (null === $extendedData) {
            return null;
        }
        
        $reflection = new \ReflectionProperty($extendedData, 'data');
        $reflection->setAccessible(true);
        $data = $reflection->getValue($extendedData);
        $reflection->setAccessible(false);
        
        foreach ($data as $name => $value) {
            if (true === $value[1]) {
                $data[$name][0] = self::$encryptionService->encrypt(serialize($value[0]));
            }
        }
        
        return parent::convertToDatabaseValue($data, $platform);
    }
    
    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $data = parent::convertToPHPValue($value, $platform);
        
        if (null === $data) {
            return null;
        }
        else if (is_array($data)) {
            foreach ($data as $name => $value) {
                if (true === $value[1]) {
                    $data[$name][0] = unserialize(self::$encryptionService->decrypt($value[0]));
                }
            }
            
            $extendedData = new ExtendedData;
            $reflection = new \ReflectionProperty($extendedData, 'data');
            $reflection->setAccessible(true);
            $reflection->setValue($extendedData, $data);
            $reflection->setAccessible(false);
            
            return $extendedData;
        }
        else {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }
    
    public function getName()
    {
        return self::NAME;
    }
}