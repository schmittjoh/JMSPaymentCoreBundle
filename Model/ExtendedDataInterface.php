<?php

namespace JMS\Payment\CoreBundle\Model;

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

interface ExtendedDataInterface
{
    public function isEncryptionRequired($name);
    public function mayBePersisted($name);
    public function remove($name);
    public function set($name, $value, $encrypt = true, $persist = true);
    public function get($name);
    public function has($name);
    public function all();
    public function equals(ExtendedDataInterface $data);
}
