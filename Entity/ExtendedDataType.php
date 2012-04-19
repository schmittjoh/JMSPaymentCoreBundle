<?php

namespace JMS\Payment\CoreBundle\Entity;

use JMS\Payment\CoreBundle\Cryptography\EncryptionServiceInterface;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\ObjectType;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class ExtendedDataType extends ObjectType
{
    const NAME = 'extended_payment_data';

    private static $encryptionService;

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
            if (false === $value[2]) {
                unset($data[$name]);
                continue;
            }
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
        } else if (is_array($data)) {
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
        } else {
            throw ConversionException::conversionFailed($value, $this->getName());
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}