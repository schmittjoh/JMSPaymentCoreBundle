<?php

namespace Bundle\PaymentBundle\Tests\Cryptography;

use Bundle\PaymentBundle\Cryptography\MCryptEncryptionService;

class MCryptEncryptionServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testEncryptDecrypt($data)
    {
        $service1 = new MCryptEncryptionService('foo');
        $service2 = new MCryptEncryptionService('foo2');
        $service3 = new MCryptEncryptionService('foo');
        
        $this->assertNotEqual($data, $service1->encrypt($data));
        $this->assertNotEqual($data, $service2->encrypt($data));
        $this->assertNotEqual($data, $service1->decrypt($service2->encrypt($data)));
        $this->assertNotEqual($data, $service2->decrypt($service1->encrypt($data)));
        $this->assertNotEqual($service1->encrypt($data), $service2->encrypt($data));
        
        $this->assertEqual($data, $service1->decrypt($service1->encrypt($data)));
        $this->assertEqual($data, $service2->decrypt($service2->encrypt($data)));
        $this->assertEqual($data, $service3->decrypt($service1->encrypt($data)));
    }
    
    public function getTestData()
    {
        return array(
            array('this is some test data, very sensitive stuff'),
            array('12345674234'),
            array('123'),
            array('4565-3346-2124-5653'),
            array('HDarfg$§fasHaha&$%§'),
        );
    }
}