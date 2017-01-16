<?php

namespace JMS\Payment\CoreBundle\Tests\DependencyInjection\Configuration;

use JMS\Payment\CoreBundle\DependencyInjection\Configuration;
use Matthias\SymfonyConfigTest\PhpUnit\AbstractConfigurationTestCase;

class ConfigurationTest extends AbstractConfigurationTestCase
{
    public function testNoSecret()
    {
        $this->assertConfigurationIsValid(array());
        $this->assertConfigurationIsInvalid(array('secret' => ''));

        $this->assertConfigurationEquals(
            array(),
            array('encryption' => array(
                'enabled' => false,
                'provider' => 'defuse_php_encryption',
            ))
        );
    }

    public function testSecret()
    {
        $this->assertConfigurationIsValid(array('secret' => 'foo'));

        $this->assertConfigurationEquals(
            array('secret' => 'foo'),
            array(
                'secret' => 'foo',
                'encryption' => array(
                    'enabled' => true,
                    'secret' => 'foo',
                    'provider' => 'mcrypt',
                ),
            )
        );
    }

    public function testEncryptionDisabled()
    {
        $this->assertConfigurationIsValid(array());
        $this->assertConfigurationIsValid(array('encryption' => false));

        $this->assertConfigurationEquals(
            array(),
            array('encryption' => array(
                'enabled' => false,
                'provider' => 'defuse_php_encryption',
            ))
        );

        $this->assertConfigurationEquals(
            array('encryption' => false),
            array('encryption' => array(
                'enabled' => false,
                'provider' => 'defuse_php_encryption',
            ))
        );

        $this->assertConfigurationEquals(
            array('encryption' => array(
                'enabled' => false,
            )),
            array('encryption' => array(
                'enabled' => false,
                'provider' => 'defuse_php_encryption',
            ))
        );
    }

    public function testEncryptionEnabled()
    {
        $this->assertConfigurationIsInvalid(array('encryption' => true));

        $this->assertConfigurationIsInvalid(array('encryption' => array(
            'enabled' => true,
        )));

        $this->assertConfigurationIsValid(array('encryption' => array(
            'enabled' => true,
            'secret' => 'foo',
        )));

        $this->assertConfigurationIsValid(array('encryption' => array(
            'secret' => 'foo',
        )));

        $this->assertConfigurationEquals(
            array('encryption' => array(
                'secret' => 'foo',
            )),
            array('encryption' => array(
                'enabled' => true,
                'secret' => 'foo',
                'provider' => 'defuse_php_encryption',
            ))
        );

        $this->assertConfigurationEquals(
            array('encryption' => array(
                'enabled' => true,
                'secret' => 'foo',
            )),
            array('encryption' => array(
                'enabled' => true,
                'secret' => 'foo',
                'provider' => 'defuse_php_encryption',
            ))
        );
    }

    protected function getConfiguration()
    {
        return new Configuration('jms_payment_core');
    }

    protected function assertConfigurationIsInvalid(array $config, $expected = null, $useRegExp = false)
    {
        parent::assertConfigurationIsInvalid(array($config), $expected, $useRegExp);
    }

    protected function assertConfigurationIsValid(array $config, $breadcrumbPath = null)
    {
        parent::assertConfigurationIsValid(array($config), $breadcrumbPath);
    }

    protected function assertConfigurationEquals($config, $expected, $breadcrumbPath = null)
    {
        $this->assertProcessedConfigurationEquals($config, $expected, $breadcrumbPath);
    }

    protected function assertProcessedConfigurationEquals(array $config, array $expected, $breadcrumbPath = null)
    {
        parent::assertProcessedConfigurationEquals(array($config), $expected, $breadcrumbPath);
    }
}
