<?php

namespace JMS\Payment\CoreBundle\Tests\Entity;

use JMS\Payment\CoreBundle\Entity\FinancialTransaction;

class FinancialTransactionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $transaction = new FinancialTransaction();

        $this->assertEquals(FinancialTransaction::STATE_NEW, $transaction->getState());
        $this->assertTrue(time() - $transaction->getCreatedAt()->getTimestamp() < 5);
        $this->assertNull($transaction->getId());
        $this->assertEquals(0.0, $transaction->getProcessedAmount());
        $this->assertEquals(0.0, $transaction->getRequestedAmount());
    }

    public function testSetGetCredit()
    {
        $transaction = new FinancialTransaction();
        $credit = $this->getMockBuilder('JMS\Payment\CoreBundle\Model\CreditInterface')->getMock();

        $this->assertNull($transaction->getCredit());
        $transaction->setCredit($credit);
        $this->assertSame($credit, $transaction->getCredit());
    }

    public function testSetGetExtendedData()
    {
        $transaction = new FinancialTransaction();
        $extendedData = $this->getMockBuilder('JMS\Payment\CoreBundle\Model\ExtendedDataInterface')->getMock();

        $this->assertNull($transaction->getExtendedData());
        $transaction->setExtendedData($extendedData);
        $this->assertSame($extendedData, $transaction->getExtendedData());
    }

    public function testSetGetPayment()
    {
        $transaction = new FinancialTransaction();
        $payment = $this->getMockBuilder('JMS\Payment\CoreBundle\Model\PaymentInterface')->getMock();

        $this->assertNull($transaction->getPayment());
        $transaction->setPayment($payment);
        $this->assertSame($payment, $transaction->getPayment());
    }

    public function testSetGetProcessedAmount()
    {
        $transaction = new FinancialTransaction();

        $this->assertEquals(0.0, $transaction->getProcessedAmount());
        $transaction->setProcessedAmount(1.2345);
        $this->assertEquals(1.2345, $transaction->getProcessedAmount());
    }

    public function testSetGetReasonCode()
    {
        $transaction = new FinancialTransaction();

        $this->assertNull($transaction->getReasonCode());
        $transaction->setReasonCode('foo');
        $this->assertEquals('foo', $transaction->getReasonCode());
    }

    public function testSetGetReferenceNumber()
    {
        $transaction = new FinancialTransaction();

        $this->assertNull($transaction->getReferenceNumber());
        $transaction->setReferenceNumber('foo');
        $this->assertEquals('foo', $transaction->getReferenceNumber());
    }

    public function testSetGetRequestedAmount()
    {
        $transaction = new FinancialTransaction();

        $this->assertEquals(0.0, $transaction->getRequestedAmount());
        $transaction->setRequestedAmount(1.2345);
        $this->assertEquals(1.2345, $transaction->getRequestedAmount());
    }

    public function testSetGetResponseCode()
    {
        $transaction = new FinancialTransaction();

        $this->assertNull($transaction->getResponseCode());
        $transaction->setResponseCode('foo');
        $this->assertEquals('foo', $transaction->getResponseCode());
    }

    public function testSetGetState()
    {
        $transaction = new FinancialTransaction();

        $this->assertEquals(FinancialTransaction::STATE_NEW, $transaction->getState());
        $transaction->setState(FinancialTransaction::STATE_PENDING);
        $this->assertEquals(FinancialTransaction::STATE_PENDING, $transaction->getState());
    }
}
