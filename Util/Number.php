<?php

namespace JMS\Payment\CoreBundle\Util;

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

class Number
{
    const EPSILON = 1.0E-8;

    public static function compare($float1, $float2, $comparision = null)
    {
        if (abs($float1 - $float2) < self::EPSILON) {
            if (null === $comparision) {
                return 0;
            }
            if ('==' === $comparision || '>=' === $comparision || '<=' === $comparision) {
                return true;
            }
            if ('>' === $comparision || '<' === $comparision) {
                return false;
            }
            
            throw new \InvalidArgumentException(sprintf('Invalid comparision "%s".', $comparision));
        }

        if (null === $comparision) {
            return $float1 > $float2 ? 1 : -1;
        }
        if ('==' === $comparision) {
            return false;
        }
        if ('>=' === $comparision || '>' === $comparision) {
            return $float1 > $float2;
        }
        if ('<=' === $comparision || '<' === $comparision) {
            return $float1 < $float2;
        }
        
        throw new \InvalidArgumentException(sprintf('Invalid comparision "%s".', $comparision));
    }

    private final function __construct() {}
}