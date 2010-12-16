<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Tests\Entity;

use Bundle\JMS\Payment\CorePaymentBundle\Entity\ExtendedData;
use Bundle\JMS\Payment\CorePaymentBundle\Entity\ExtendedDataType;
use Bundle\JMS\Payment\CorePaymentBundle\Cryptography\MCryptEncryptionService;
use Doctrine\DBAL\Types\Type;

class ExtendedDataTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testStaticSetGetEncryptionService()
    {
        $service = new MCryptEncryptionService('foo');
        
        $this->assertNull(ExtendedDataType::getEncryptionService());
        ExtendedDataType::setEncryptionService($service);
        $this->assertSame($service, ExtendedDataType::getEncryptionService());
    }
    
    public function testGetName()
    {
        Type::addType(ExtendedDataType::NAME, 'Bundle\JMS\Payment\CorePaymentBundle\Entity\ExtendedDataType');
        $type = Type::getType(ExtendedDataType::NAME);
        
        $this->assertEquals(ExtendedDataType::NAME, $type->getName());
        $this->assertNotEmpty($type->getName());
    }
    
    public function testConversion()
    {
        ExtendedDataType::setEncryptionService(new MCryptEncryptionService('foo'));
        Type::addType(ExtendedDataType::NAME, 'Bundle\JMS\Payment\CorePaymentBundle\Entity\ExtendedDataType');
        
        $extendedData = new ExtendedData;
        $extendedData->set('foo', 'foo', false);
        $extendedData->set('foo2', 'secret', true);
        $extendedData->set('foo3', 'foo', false);
        
        $type = Type::getType(ExtendedDataType::NAME);
        
        $serialized = $type->convertToDatabaseValue($extendedData, $this->getPlatform());
        $this->assertTrue(false !== $unserialized = unserialize($serialized));
        $this->assertType('array', $unserialized);
        $this->assertEquals('secret', $extendedData->get('foo2'), 'ExtendedData object is not affected by encryption.');
        $this->assertEquals('foo', $extendedData->get('foo'), 'ExtendedData object is not affected by conversion.');
        $this->assertEquals('foo', $unserialized['foo'][0]);
        $this->assertNotEquals('secret', $unserialized['foo2'][0]);
        $this->assertEquals('foo', $unserialized['foo3'][0]);
        
        $extendedData = $type->convertToPHPValue($serialized, $this->getPlatform());
        $this->assertEquals('foo', $extendedData->get('foo'));
        $this->assertEquals('secret', $extendedData->get('foo2'));
        $this->assertEquals('foo', $extendedData->get('foo'));
    }
    
    protected function getPlatform()
    {
        return $this->getMockForAbstractClass('Doctrine\DBAL\Platforms\AbstractPlatform');
    }
}