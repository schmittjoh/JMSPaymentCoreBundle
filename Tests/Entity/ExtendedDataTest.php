<?php

namespace Bundle\PaymentBundle\Tests\Entity;

use Bundle\PaymentBundle\Entity\ExtendedData;

class ExtendedDataTest extends \PHPUnit_Framework_TestCase
{
    public function testRemoveIgnoresIfKeyDoesNotExist()
    {
        $data = new ExtendedData;
        
        $this->assertFalse($data->has('foo'));
        $data->remove('foo');
        $this->assertFalse($data->has('foo'));
    }
    
    public function testRemove()
    {
        $data = new ExtendedData;
        
        $this->assertFalse($data->has('foo'));
        $data->set('foo', 'foo', false);
        $this->assertTrue($data->has('foo'));
        $data->remove('foo');
        $this->assertFalse($data->has('foo'));
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testIsEncryptionRequiredThrowsExceptionOnNonExistentKey()
    {
        $extendedData = new ExtendedData;
        $extendedData->isEncryptionRequired('foo');
    }
    
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetThrowsExceptionOnNonExistentKey()
    {
        $extendedData = new ExtendedData;
        $extendedData->get('foo');
    }
    
    /**
     * @dataProvider getTestData
     */
    public function testWithSomeData($name, $value, $encrypt)
    {
        $extendedData = new ExtendedData;
        $extendedData->set($name, $value, $encrypt);
        
        $this->assertEquals($value, $extendedData->get($name));
        
        if ($encrypt) {
            $this->assertTrue($extendedData->isEncryptionRequired($name));
        }
        else {
            $this->assertFalse($extendedData->isEncryptionRequired($name));
        }
    }
    
    public function getTestData()
    {
        return array(
            array('account_holder', 'fooholder', false),
            array('account_number', '1234567890', true),
        );
    }
}