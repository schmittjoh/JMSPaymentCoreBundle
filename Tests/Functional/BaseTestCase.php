<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Filesystem\Filesystem;

class BaseTestCase extends WebTestCase
{
    protected static function createKernel(array $options = array())
    {
        return self::$kernel = new AppKernel(
            isset($options['config']) ? $options['config'] : 'config.yml'
        );
    }

    protected function setUp()
    {
        $fs = new Filesystem();
        $fs->remove(sys_get_temp_dir().'/JMSPaymentCoreBundle/');
    }

    protected function importDatabaseSchema()
    {
        $em = self::$kernel->getContainer()->get('doctrine.orm.entity_manager');

        $metadata = $em->getMetadataFactory()->getAllMetadata();
        if (!empty($metadata)) {
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($em);
            $schemaTool->dropDatabase();
            $schemaTool->createSchema($metadata);
        }
    }
}
