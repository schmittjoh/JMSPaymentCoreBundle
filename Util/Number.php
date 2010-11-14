<?php

namespace Bundle\PaymentBundle\Util;

abstract class Number
{
    const EPSILON = 1.0E-8;
    
    public static function compare($float1, $float2)
    {
        if (abs($float1 - $float2) < self::EPSILON) {
            return 0;
        }

        return $float1 > $float2 ? 1 : -1;
    }
}