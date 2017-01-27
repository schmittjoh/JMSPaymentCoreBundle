<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\PaymentWorkflow;

use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\Payment\CoreBundle\Util\Number;

class PaymentWorkflowNoEncryptionTest extends BasePaymentWorkflowTest
{
    /**
     * Disable encryption globally.
     */
    protected static function createKernel(array $options = array())
    {
        return parent::createKernel(array('config' => 'config_no_encryption.yml'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testPayment()
    {
        $amount = 123.45;
        $order = new Order($amount);

        $response = parent::doRequest($order, 'payment');
        $order = $this->refreshOrder($order);

        $this->assertSame(201, $response->getStatusCode(), substr($response, 0, 2000));
        $this->assertTrue(Number::compare($amount, $order->getPaymentInstruction()->getAmount(), '=='));
        $this->assertEquals('bar', $order->getPaymentInstruction()->getExtendedData()->get('foo'));

        $extendedData = $this->getRawExtendedData($order);
        $this->assertArrayHasKey('foo', $extendedData);
        $this->assertEquals('bar', $extendedData['foo'][0]);
    }
}
