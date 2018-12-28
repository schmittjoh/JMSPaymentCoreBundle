<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

class BasicEntityInteractionsTest extends BaseTestCase
{
    /**
     * @runInSeparateProcess
     */
    public function testIndependentCredit()
    {
        $this->createClient();
        $this->importDatabaseSchema();

        $ppc = $this->getContainer()->get('payment.plugin_controller');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        $instruction = new PaymentInstruction(123.45, 'EUR', 'test_plugin');
        $ppc->createPaymentInstruction($instruction);
        $credit = $ppc->createIndependentCredit($instruction->getId(), 123);

        $em->clear();

        $rInstruction = $ppc->getPaymentInstruction($instruction->getId());
        $this->assertNotSame($instruction, $rInstruction);
        $this->assertEquals(1, $rInstruction->getCredits()->count());
        $this->assertEquals($credit->getId(), $rInstruction->getCredits()->first()->getId());
    }
}
