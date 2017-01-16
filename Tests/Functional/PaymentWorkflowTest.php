<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

class PaymentWorkflowTest extends BasePaymentWorkflowTest
{
    /**
     * @runInSeparateProcess
     */
    public function testPaymentDetails()
    {
        $order = parent::doTestPaymentDetails();

        $extendedData = $this->getRawExtendedData($order->getPaymentInstruction());
        $this->assertArrayHasKey('foo', $extendedData);
        $this->assertNotEquals('bar', $extendedData['foo'][0]);
    }
}
