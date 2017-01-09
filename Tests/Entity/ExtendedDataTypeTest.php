<?php

namespace JMS\Payment\CoreBundle\Tests\Entity;

use Doctrine\DBAL\Types\Type;
use JMS\Payment\CoreBundle\Cryptography\DefusePhpEncryptionService;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\ExtendedDataType;

class ExtendedDataTypeTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (Type::hasType(ExtendedDataType::NAME)) {
            Type::overrideType(ExtendedDataType::NAME, 'JMS\Payment\CoreBundle\Entity\ExtendedDataType');
        } else {
            Type::addType(ExtendedDataType::NAME, 'JMS\Payment\CoreBundle\Entity\ExtendedDataType');
        }
    }

    public function testStaticSetGetEncryptionService()
    {
        $service = new DefusePhpEncryptionService('def00000812bba10524777c97f1155877f0c91a4fac2c9f3d71a39d0df7214eab90d492faa58d2db667c5003c3c2228e3f19ad493ae86c74079a600a1ed51cd65e21f28e');

        $this->assertNull(ExtendedDataType::getEncryptionService());
        ExtendedDataType::setEncryptionService($service);
        $this->assertSame($service, ExtendedDataType::getEncryptionService());
    }

    public function testGetName()
    {
        $type = Type::getType(ExtendedDataType::NAME);

        $this->assertEquals(ExtendedDataType::NAME, $type->getName());
        $this->assertNotEmpty($type->getName());
    }

    public function testConversion()
    {
        ExtendedDataType::setEncryptionService(new DefusePhpEncryptionService('def00000812bba10524777c97f1155877f0c91a4fac2c9f3d71a39d0df7214eab90d492faa58d2db667c5003c3c2228e3f19ad493ae86c74079a600a1ed51cd65e21f28e'));

        $extendedData = new ExtendedData();
        $extendedData->set('foo', 'foo', false);
        $extendedData->set('foo2', 'secret', true);
        $extendedData->set('dont_persist', 'nono', false, false);
        $extendedData->set('foo3', 'foo', false);

        $type = Type::getType(ExtendedDataType::NAME);

        $serialized = $type->convertToDatabaseValue($extendedData, $this->getPlatform());
        $this->assertTrue(false !== $unserialized = unserialize($serialized));
        $this->assertInternalType('array', $unserialized);
        $this->assertEquals('secret', $extendedData->get('foo2'), 'ExtendedData object is not affected by encryption.');
        $this->assertEquals('foo', $extendedData->get('foo'), 'ExtendedData object is not affected by conversion.');
        $this->assertEquals('foo', $unserialized['foo'][0]);
        $this->assertNotEquals('secret', $unserialized['foo2'][0]);
        $this->assertEquals('foo', $unserialized['foo3'][0]);
        $this->assertTrue(!isset($unserialized['dont_persist']));

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
