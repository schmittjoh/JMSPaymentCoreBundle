<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\Entity;

use InvalidArgumentException;
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

class ExtendedData implements ExtendedDataInterface
{
    private $data;

    private $listeners;

    public function __construct()
    {
        $this->data = [];
    }

    public function remove($name): void
    {
        unset($this->data[$name]);
    }

    public function isEncryptionRequired($name)
    {
        if (!isset($this->data[$name])) {
            throw new InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }

        return $this->data[$name][1];
    }

    public function mayBePersisted($name)
    {
        if (!isset($this->data[$name])) {
            throw new InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }

        return $this->data[$name][2];
    }

    public function set($name, $value, $encrypt = true, $persist = true): void
    {
        if ($encrypt && !$persist) {
            throw new InvalidArgumentException(sprintf('Non persisted field cannot be encrypted "%s".', $name));
        }

        $this->data[$name] = [$value, $encrypt, $persist];
    }

    public function get($name)
    {
        if (!isset($this->data[$name])) {
            throw new InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }

        return $this->data[$name][0];
    }

    public function has($name)
    {
        return isset($this->data[$name]);
    }

    public function all()
    {
        return $this->data;
    }

    public function equals(ExtendedDataInterface $data)
    {
        $data = $data->all();
        ksort($data);

        $cData = $this->data;
        ksort($cData);

        return $data === $cData;
    }
}
