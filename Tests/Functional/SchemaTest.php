<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

use Doctrine\ORM\Tools\SchemaValidator;
use JMS\Payment\CoreBundle\Util\Legacy;

class SchemaTest extends BaseTestCase
{
    public function testLegacySchemaIsValid()
    {
        if (!Legacy::supportsSecureRandom()) {
            $this->markTestSkipped();

            return;
        }

        $this->doTestSchemaIsValid();
    }

    public function testSchemaIsValid()
    {
        if (Legacy::supportsSecureRandom()) {
            $this->markTestSkipped();

            return;
        }

        $this->doTestSchemaIsValid();
    }

    private function doTestSchemaIsValid()
    {
        $this->createClient();

        $validator = new SchemaValidator(self::$kernel->getContainer()->get('doctrine.orm.entity_manager'));
        $errors = $validator->validateMapping();

        $this->assertEmpty($errors, "Validation errors found: \n\n".var_export($errors, true));
    }
}
