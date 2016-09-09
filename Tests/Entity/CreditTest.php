<?php

namespace JMS\Payment\CoreBundle\Tests\Entity;

use JMS\Payment\CoreBundle\Entity\Credit;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;

class CreditTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $credit = new Credit($instruction = $this->getInstruction(), 100.23);

        $this->assertSame($instruction, $credit->getPaymentInstruction());
        $this->assertEquals(0.0, $credit->getCreditedAmount());
        $this->assertEquals(0.0, $credit->getCreditingAmount());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $credit->getTransactions());
        $this->assertEquals(0, count($credit->getTransactions()));
        $this->assertEquals(0.0, $credit->getReversingAmount());
        $this->assertSame(CreditInterface::STATE_NEW, $credit->getState());
        $this->assertEquals(100.23, $credit->getTargetAmount());
        $this->assertFalse($credit->isAttentionRequired());
        $this->assertNull($credit->getId());
    }

    public function testAddTransaction()
    {
        $credit = new Credit($instruction = $this->getInstruction(), 100);

        $this->assertEquals(0, count($credit->getTransactions()));

        $transaction = new FinancialTransaction();
        $credit->addTransaction($transaction);

        $this->assertSame($credit, $transaction->getCredit());
        $this->assertEquals(1, count($credit->getTransactions()));
    }

    public function testGetCreditTransaction()
    {
        $credit = new Credit($this->getInstruction(), 100);

        $transaction = new FinancialTransaction();
        $credit->addTransaction($transaction);

        $creditTransaction = new FinancialTransaction();
        $creditTransaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_CREDIT);
        $credit->addTransaction($creditTransaction);

        $transaction = new FinancialTransaction();
        $credit->addTransaction($transaction);

        $this->assertSame($creditTransaction, $credit->getCreditTransaction());
    }

    public function testGetPendingTransactionAndHasPendingTransaction()
    {
        $credit = new Credit($this->getInstruction(), 100);

        $this->assertFalse($credit->hasPendingTransaction());

        $transaction = new FinancialTransaction();
        $credit->addTransaction($transaction);

        $this->assertFalse($credit->hasPendingTransaction());

        $pendingTransaction = new FinancialTransaction();
        $pendingTransaction->setState(FinancialTransactionInterface::STATE_PENDING);
        $credit->addTransaction($pendingTransaction);

        $this->assertTrue($credit->hasPendingTransaction());

        $transaction = new FinancialTransaction();
        $credit->addTransaction($transaction);

        $this->assertTrue($credit->hasPendingTransaction());
        $this->assertSame($pendingTransaction, $credit->getPendingTransaction());
    }

    public function testGetReverseCreditTransactions()
    {
        $credit = new Credit($this->getInstruction(), 100);

        $this->assertEquals(0, count($credit->getReverseCreditTransactions()));

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_CREDIT);
        $credit->addTransaction($transaction);

        $this->assertEquals(1, count($credit->getReverseCreditTransactions()));

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_CREDIT);
        $credit->addTransaction($transaction);

        $this->assertEquals(2, count($credit->getReverseCreditTransactions()));

        $transaction = new FinancialTransaction();
        $credit->addTransaction($transaction);

        $this->assertEquals(2, count($credit->getReverseCreditTransactions()));

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_CREDIT);
        $credit->addTransaction($transaction);

        $this->assertEquals(3, count($credit->getReverseCreditTransactions()));

        $transaction = new FinancialTransaction();
        $credit->addTransaction($transaction);

        $this->assertEquals(3, count($credit->getReverseCreditTransactions()));

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_CREDIT);
        $credit->addTransaction($transaction);

        $this->assertEquals(4, count($credit->getReverseCreditTransactions()));
    }

    public function testIsSetAttentionRequired()
    {
        $credit = new Credit($this->getInstruction(), 100);

        $this->assertFalse($credit->isAttentionRequired());
        $credit->setAttentionRequired(true);
        $this->assertTrue($credit->isAttentionRequired());
        $credit->setAttentionRequired(false);
        $this->assertFalse($credit->isAttentionRequired());
    }

    /**
     * @dataProvider getSimpleTestData
     */
    public function testSimpleSettersGetters($property, $value, $default)
    {
        $getter = 'get'.$property;
        $setter = 'set'.$property;

        $credit = new Credit($this->getInstruction(), 100);

        $this->assertEquals($default, $credit->$getter());
        $credit->$setter($value);
        $this->assertEquals($value, $credit->$getter());
    }

    public function getSimpleTestData()
    {
        return array(
            array('CreditingAmount', 123.45, 0.0),
            array('CreditedAmount', 643.12, 0.0),
            array('ReversingAmount', 453.14, 0.0),
            array('State', CreditInterface::STATE_CANCELED, CreditInterface::STATE_NEW),
        );
    }

    public function testSetGetPayment()
    {
        $credit = new Credit($instruction = $this->getInstruction(), 11);

        $this->assertNull($credit->getPayment());
        $credit->setPayment($payment = new Payment($instruction, 100));
        $this->assertSame($payment, $credit->getPayment());
    }

    protected function getInstruction()
    {
        return new PaymentInstruction(123.45, 'EUR', 'foo', new ExtendedData());
    }
}
