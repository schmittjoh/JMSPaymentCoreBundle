<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

class PaymentWorkflowMcryptTest extends PaymentWorkflowTest
{
    protected static function createKernel(array $options = array())
    {
        return parent::createKernel(array('config' => 'config_mcrypt.yml'));
    }

    /**
     * @runInSeparateProcess
     */
    public function testPaymentDetails()
    {
        if (version_compare(phpversion(), '7.1', '>=')) {
            $this->markTestSkipped('mcrypt is deprecated since PHP 7.1');
        }

        $order = parent::doTestPaymentDetails();

        $extendedData = $this->getRawExtendedData($order->getPaymentInstruction());
        $this->assertArrayHasKey('foo', $extendedData);
        $this->assertNotEquals('bar', $extendedData['foo'][0]);
    }
}
