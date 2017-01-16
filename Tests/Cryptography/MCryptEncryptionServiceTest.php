<?php

namespace JMS\Payment\CoreBundle\Tests\Cryptography;

use JMS\Payment\CoreBundle\Cryptography\MCryptEncryptionService;

class MCryptEncryptionServiceTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            $this->markTestSkipped('mcrypt is deprecated since PHP 7.1');
        }

        if (false !== strpos(PHP_OS, 'WIN')) {
            $this->markTestSkipped('Windows is not suited for generating random data.');
        }
    }

    public function testConstructor()
    {
        $service = new MCryptEncryptionService('foo', 'rijndael-256', 'ctr');

        $this->assertEquals('rijndael-256', $service->getCipher());
        $this->assertEquals('ctr', $service->getMode());
        $this->assertTrue('foo' != $service->getKey());
        $this->assertTrue(preg_match('/[^\x00-\x7F]/S', $service->getKey()) > 0, 'Key must not be ASCII');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedMessage The mode "foomode" is not supported.
     */
    public function testConstructorWithInvalidMode()
    {
        $service = new MCryptEncryptionService('foo', 'rijndael-256', 'foomode');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedMessage The cipher "foocipher" is not supported.
     */
    public function testConstructorWithInvalidCipher()
    {
        $service = new MCryptEncryptionService('foo', 'foocipher');
    }

    /**
     * @dataProvider getTestData
     */
    public function testEncryptDecrypt($data)
    {
        $service1 = new MCryptEncryptionService('foo');
        $service2 = new MCryptEncryptionService('foo2');
        $service3 = new MCryptEncryptionService('foo');

        $this->assertNotEquals($data, $service1->encrypt($data));
        $this->assertNotEquals($data, $service2->encrypt($data));
        $this->assertNotEquals($data, $service1->decrypt($service2->encrypt($data)));
        $this->assertNotEquals($data, $service2->decrypt($service1->encrypt($data)));
        $this->assertNotEquals($service1->encrypt($data), $service2->encrypt($data));

        $this->assertEquals($data, $service1->decrypt($service1->encrypt($data)));
        $this->assertEquals($data, $service2->decrypt($service2->encrypt($data)));
        $this->assertEquals($data, $service3->decrypt($service1->encrypt($data)));
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
