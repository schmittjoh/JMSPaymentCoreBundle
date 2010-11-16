<?php

namespace Bundle\PaymentBundle\Entity;

use Bundle\PaymentBundle\Cryptography\EncryptionServiceInterface;
use Doctrine\DBAL\Types\ObjectType;

class ExtendedDataType extends ObjectType
{
    protected static $encryptionService;
    
    public static function setEncryptionService(EncryptionServiceInterface $service)
    {
        self::$encryptionService = $service;
    }
    
    public static function getEncryptionService()
    {
        return self::$encryptionService;
    }
    
    public function convertToDatabaseValue(ExtendedData $extendedData, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $reflection = new ReflectionPropery($extendedData, 'data');
        $reflection->setAccessible(true);
        $data = $reflection->getValue($extendedData);
        $reflection->setAccessible(false);
        
        foreach ($data as $name => $fixedArray) {
            if (true === $fixedArray[1]) {
                $fixedArray = clone $fixedArray;
                $fixedArray[0] = self::$encryptionService->encrypt($fixedArray[0]);
                $data[$name] = $fixedArray;
            }
        }
        
        return parent::convertToDatabaseValue($data, $platform);
    }
    
    public function convertToPHPValue($value, \Doctrine\DBAL\Platforms\AbstractPlatform $platform)
    {
        $data = parent::convertToPHPValue($value, $platform);
        
        if (!is_array($data)) {
            return new ExtendedData;
        }
        
        foreach ($data as $name => $fixedArray) {
            if (true === $fixedArray[1]) {
                $fixedArray[0] = self::$encryptionService->decrypt($fixedArray[0]);
            }
        }
        
        $extendedData = new ExtendedData();
        $reflection = new ReflectionProperty($extendedData, 'data');
        $reflection->setAccessible(true);
        $reflection->setValue($extendedData, $data);
        $reflection->setAccessible(false);
        
        return $extendedData;
    }
}