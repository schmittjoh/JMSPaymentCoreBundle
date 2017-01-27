<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\PaymentWorkflow;

use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\Payment\CoreBundle\Util\Number;

class PaymentWorkflowMcryptTest extends PaymentWorkflowTest
{
    protected static function createKernel(array $options = array())
    {
        return parent::createKernel(array('config' => 'config_mcrypt.yml'));
    }

    public function setUp()
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            $this->markTestSkipped('mcrypt is deprecated since PHP 7.1');
        }

        parent::setUp();
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
        $this->assertNotEquals('bar', $extendedData['foo'][0]);
    }
}
