<?php

namespace Bundle\PaymentBundle\Tests\Util;

use Bundle\PaymentBundle\Util\Number;

class NumberTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getEqualFloats
     */
    public function testCompareForEquality($float1, $float2)
    {
        $this->assertSame(0, Number::compare($float1, $float2));
    }
    
    public function getEqualFloats()
    {
        return array(
            array(0, 0),
            array(1.12, 1.12),
            array(1.123456789, 1.123456788),
        );
    }
    
    /**
     * @dataProvider getSmallerFloats
     */
    public function testCompareSmaller($float1, $float2)
    {
        $this->assertSame(-1, Number::compare($float1, $float2));
    }
    
    public function getSmallerFloats()
    {
        return array(
            array(0, 1),
            array(0.12, 0.123),
            array(0.1234, 0.1235)
        );
    }
    
    /**
     * @dataProvider getGreaterFloats
     */
    public function testCompareGreater($float1, $float2)
    {
        $this->assertSame(1, Number::compare($float1, $float2));
    }
    
    public function getGreaterFloats()
    {
        return array(
            array(1, 0),
            array(0.123, 0.12),
            array(0.1235, 0.1234),
        );
    }
}