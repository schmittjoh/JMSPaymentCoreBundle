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

        $c = self::$kernel->getContainer();
        $ppc = $c->get('payment.plugin_controller');
        $em = $c->get('doctrine.orm.entity_manager');
        $instruction = new PaymentInstruction(123.45, 'EUR', 'paypal_express_checkout');
        $ppc->createPaymentInstruction($instruction);
        $credit = $ppc->createIndependentCredit($instruction->getId(), 123);

        $em->clear();

        $rInstruction = $ppc->getPaymentInstruction($instruction->getId());
        $this->assertNotSame($instruction, $rInstruction);
        $this->assertEquals(1, $rInstruction->getCredits()->count());
        $this->assertEquals($credit->getId(), $rInstruction->getCredits()->first()->getId());
    }
}
