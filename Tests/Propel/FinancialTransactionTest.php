<?php

namespace JMS\Payment\CoreBundle\Tests\Propel;

use JMS\Payment\CoreBundle\Propel\FinancialTransaction;
use JMS\Payment\CoreBundle\Propel\Credit;
use JMS\Payment\CoreBundle\Propel\ExtendedData;
use JMS\Payment\CoreBundle\Propel\Payment;

class FinancialTransactionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Propel')) {
            $this->markTestSkipped('Propel not installed');
        }
    }

    public function testConstructor()
    {
        $transaction = new FinancialTransaction();

        $this->assertEquals(FinancialTransaction::STATE_NEW, $transaction->getState());
        $this->assertNull($transaction->getId());
        $this->assertEquals(0.0, $transaction->getProcessedAmount());
        $this->assertEquals(0.0, $transaction->getRequestedAmount());
    }

    public function testSetGetProcessedAmount()
    {
        $transaction = new FinancialTransaction;

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
