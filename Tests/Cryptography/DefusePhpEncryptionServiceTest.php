<?php

namespace JMS\Payment\CoreBundle\Tests\Cryptography;

use JMS\Payment\CoreBundle\Cryptography\DefusePhpEncryptionService;

class DefusePhpEncryptionServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getTestData
     */
    public function testEncryptDecrypt($data)
    {
        $service1 = new DefusePhpEncryptionService('def00000290a4b250a1b24c41f3076b5e3955e1a51d8535a5dbcf209d17f1eb8d772349cbd12af5dc8f4b05d43ca900489c0fb5aa5c4c5190ccffb5663ae4831e3022fc6');
        $service2 = new DefusePhpEncryptionService('def00000000bdbf82c58d9fbd77d15fa5314bdafebe7586e03b0679ef09f622577afe58485b2a4b3c2e74a16a7375ad348f29e9254a57237691fdf2d71b1d78cd3958497');
        $service3 = new DefusePhpEncryptionService('def00000290a4b250a1b24c41f3076b5e3955e1a51d8535a5dbcf209d17f1eb8d772349cbd12af5dc8f4b05d43ca900489c0fb5aa5c4c5190ccffb5663ae4831e3022fc6');

        $this->assertNotEquals($data, $service1->encrypt($data));
        $this->assertNotEquals($data, $service2->encrypt($data));

        $this->assertEquals($data, $service1->decrypt($service1->encrypt($data)));
        $this->assertEquals($data, $service2->decrypt($service2->encrypt($data)));
        $this->assertEquals($data, $service3->decrypt($service1->encrypt($data)));
    }

    /**
     * @dataProvider getTestData
     * @expectedException \Defuse\Crypto\Exception\WrongKeyOrModifiedCiphertextException
     */
    public function testEncryptDecryptFailure($data)
    {
        $service1 = new DefusePhpEncryptionService('def00000290a4b250a1b24c41f3076b5e3955e1a51d8535a5dbcf209d17f1eb8d772349cbd12af5dc8f4b05d43ca900489c0fb5aa5c4c5190ccffb5663ae4831e3022fc6');
        $service2 = new DefusePhpEncryptionService('def00000000bdbf82c58d9fbd77d15fa5314bdafebe7586e03b0679ef09f622577afe58485b2a4b3c2e74a16a7375ad348f29e9254a57237691fdf2d71b1d78cd3958497');

        $this->assertNotEquals($data, $service1->decrypt($service2->encrypt($data)));
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
