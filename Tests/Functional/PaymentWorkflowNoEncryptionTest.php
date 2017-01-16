<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

class PaymentWorkflowNoEncryptionTest extends BasePaymentWorkflowTest
{
    protected static function createKernel(array $options = array())
    {
        return parent::createKernel(array('config' => 'config_no_encryption.yml'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testPaymentDetails()
    {
        $order = parent::doTestPaymentDetails();

        $extendedData = $this->getRawExtendedData($order->getPaymentInstruction());
        $this->assertArrayHasKey('foo', $extendedData);
        $this->assertEquals('bar', $extendedData['foo'][0]);
    }
}
