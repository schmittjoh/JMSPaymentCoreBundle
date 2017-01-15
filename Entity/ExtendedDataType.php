<?php

namespace JMS\Payment\CoreBundle\Entity;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\ConversionException;
use Doctrine\DBAL\Types\ObjectType;
use JMS\Payment\CoreBundle\Cryptography\EncryptionServiceInterface;
use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;

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
        if ($extendedData === null) {
            return null;
        }

        if (!$extendedData instanceof ExtendedDataInterface) {
            throw new \InvalidArgumentException(
                '$extendedData must implement JMS\Payment\CoreBundle\Model\ExtendedDataInterface'
            );
        }

        $data = array();

        foreach (array_keys($extendedData->all()) as $name) {
            if (!$extendedData->mayBePersisted($name)) {
                continue;
            }

            $value = $extendedData->get($name);
            $isEncryptionRequired = $extendedData->isEncryptionRequired($name);

            if ($isEncryptionRequired && self::$encryptionService) {
                $value = self::$encryptionService->encrypt(serialize($value));
            }

            $data[$name] = array(
                $value,
                $isEncryptionRequired,
                $mayBePersisted = true,
            );
        }

        return parent::convertToDatabaseValue($data, $platform);
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        $data = parent::convertToPHPValue($value, $platform);

        if ($data === null) {
            return null;
        }

        if (!is_array($data)) {
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        $extendedData = new ExtendedData();

        foreach ($data as $name => $value) {
            $isEncryptionRequired = (bool) $value[1];
            $value = $value[0];

            if ($isEncryptionRequired && self::$encryptionService) {
                $value = unserialize(self::$encryptionService->decrypt($value));
            }

            $extendedData->set($name, $value, $isEncryptionRequired);
        }

        return $extendedData;
    }

    public function getName()
    {
        return self::NAME;
    }

    public function requiresSQLCommentHint(AbstractPlatform $platform)
    {
        return true;
    }
}
