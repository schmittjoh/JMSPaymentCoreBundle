<?php

namespace JMS\Payment\CoreBundle\Tests\Entity;

use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $payment = new Payment($instruction = $this->getInstruction(), 123.45);

        $this->assertEquals(0.0, $payment->getApprovedAmount());
        $this->assertEquals(0.0, $payment->getApprovingAmount());
        $this->assertEquals(0.0, $payment->getCreditedAmount());
        $this->assertEquals(0.0, $payment->getCreditingAmount());
        $this->assertEquals(0.0, $payment->getDepositedAmount());
        $this->assertEquals(0.0, $payment->getDepositingAmount());
        $this->assertEquals(0.0, $payment->getReversingApprovedAmount());
        $this->assertEquals(0.0, $payment->getReversingCreditedAmount());
        $this->assertEquals(0.0, $payment->getReversingDepositedAmount());
        $this->assertEquals(Payment::STATE_NEW, $payment->getState());
        $this->assertEquals(123.45, $payment->getTargetAmount());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $payment->getTransactions());
        $this->assertEquals(0, count($payment->getTransactions()));
        $this->assertFalse($payment->isAttentionRequired());
        $this->assertFalse($payment->isExpired());
        $this->assertNull($payment->getId());
        $this->assertSame($instruction, $payment->getPaymentInstruction());
    }

    public function testAddTransaction()
    {
        $payment = new Payment($this->getInstruction(), 123);
        $transaction = new FinancialTransaction();

        $this->assertEquals(0, count($payment->getTransactions()));
        $payment->addTransaction($transaction);
        $this->assertEquals(1, count($payment->getTransactions()));
        $this->assertSame($transaction, $payment->getTransactions()->get(0));
        $this->assertSame($payment, $transaction->getPayment());
    }

    /**
     * @dataProvider getApproveTransactionTypes
     */
    public function testGetApproveTransaction($approveType)
    {
        $payment = new Payment($this->getInstruction(), 123);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);

        $approveTransaction = new FinancialTransaction();
        $approveTransaction->setTransactionType($approveType);
        $payment->addTransaction($approveTransaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);

        $this->assertSame($approveTransaction, $payment->getApproveTransaction());
    }

    public function getApproveTransactionTypes()
    {
        return array(
            array(FinancialTransaction::TRANSACTION_TYPE_APPROVE),
            array(FinancialTransaction::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT),
        );
    }

    public function testGetDepositTransactions()
    {
        $payment = $this->getPayment();

        $this->assertEquals(10, count($payment->getTransactions()));
        $this->assertEquals(3, count($payment->getDepositTransactions()));

        foreach ($payment->getDepositTransactions() as $transaction) {
            $this->assertSame(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT, $transaction->getTransactionType());
        }
    }

    public function testGetSetExpirationDate()
    {
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertNull($payment->getExpirationDate());
        $payment->setExpirationDate($date = new \DateTime());
        $this->assertSame($date, $payment->getExpirationDate());
    }

    public function testGetPendingTransaction()
    {
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertNull($payment->getPendingTransaction());

        $transaction = new FinancialTransaction();
        $payment->addTransaction($transaction);

        $this->assertNull($payment->getPendingTransaction());

        $pendingTransaction = new FinancialTransaction();
        $pendingTransaction->setState(FinancialTransaction::STATE_PENDING);
        $payment->addTransaction($pendingTransaction);

        $this->assertSame($pendingTransaction, $payment->getPendingTransaction());

        $transaction = new FinancialTransaction();
        $payment->addTransaction($transaction);

        $this->assertSame($pendingTransaction, $payment->getPendingTransaction());
    }

    public function testReverseApprovalTransactions()
    {
        $payment = $this->getPayment();

        $this->assertEquals(10, count($payment->getTransactions()));
        $this->assertEquals(2, count($payment->getReverseApprovalTransactions()));

        foreach ($payment->getReverseApprovalTransactions() as $transaction) {
            $this->assertSame(FinancialTransaction::TRANSACTION_TYPE_REVERSE_APPROVAL, $transaction->getTransactionType());
        }
    }

    public function testReverseDepositTransactions()
    {
        $payment = $this->getPayment();

        $this->assertEquals(10, count($payment->getTransactions()));
        $this->assertEquals(4, count($payment->getReverseDepositTransactions()));

        foreach ($payment->getReverseDepositTransactions() as $transaction) {
            $this->assertSame(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT, $transaction->getTransactionType());
        }
    }

    public function testGetSetState()
    {
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertSame(Payment::STATE_NEW, $payment->getState());
        $payment->setState(Payment::STATE_APPROVED);
        $this->assertSame(Payment::STATE_APPROVED, $payment->getState());
    }

    public function testGetTransactions()
    {
        $payment = $this->getPayment();

        $this->assertEquals(10, count($payment->getTransactions()));

        $transaction = new FinancialTransaction();
        $payment->addTransaction($transaction);

        $this->assertEquals(11, count($payment->getTransactions()));
        $this->assertSame($transaction, $payment->getTransactions()->get(10));
    }

    public function testHasPendingTransaction()
    {
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertFalse($payment->hasPendingTransaction());

        $transaction = new FinancialTransaction();
        $payment->addTransaction($transaction);

        $this->assertFalse($payment->hasPendingTransaction());

        $transaction = new FinancialTransaction();
        $transaction->setState(FinancialTransaction::STATE_PENDING);
        $payment->addTransaction($transaction);

        $this->assertTrue($payment->hasPendingTransaction());
    }

    public function testIsSetAttentionRequired()
    {
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertFalse($payment->isAttentionRequired());
        $payment->setAttentionRequired(true);
        $this->assertTrue($payment->isAttentionRequired());
        $payment->setAttentionRequired(false);
        $this->assertFalse($payment->isAttentionRequired());
    }

    public function testIsExpiredDueToExpirationDate()
    {
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertFalse($payment->isExpired());
        $payment->setExpirationDate(new \DateTime('yesterday'));
        $this->assertTrue($payment->isExpired());

        $payment->setExpirationDate(new \DateTime('tomorrow'));
        $this->assertFalse($payment->isExpired());
    }

    public function testIsSetExpired()
    {
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertFalse($payment->isExpired());

        $payment->setExpired(true);
        $this->assertTrue($payment->isExpired());

        $payment->setExpired(false);
        $this->assertFalse($payment->isExpired());
    }

    /**
     * @dataProvider getSetterGetterTestData
     */
    public function testSimpleSettersGetters($propertyName, $value, $default)
    {
        $setter = 'set'.$propertyName;
        $getter = 'get'.$propertyName;
        $payment = new Payment($this->getInstruction(), 123);

        $this->assertEquals($default, $payment->$getter());
        $payment->$setter($value);
        $this->assertEquals($value, $payment->$getter());
    }

    public function getSetterGetterTestData()
    {
        return array(
            array('ApprovingAmount', 123.45, 0.0),
            array('ApprovingAmount', 583, 0.0),
            array('ApprovedAmount', 123.45, 0.0),
            array('ApprovedAmount', 583, 0.0),
            array('CreditedAmount', 123.45, 0.0),
            array('CreditedAmount', 533, 0.0),
            array('CreditingAmount', 452.64, 0.0),
            array('CreditingAmount', 567, 0.0),
            array('DepositedAmount', 123.45, 0.0),
            array('DepositedAmount', 583, 0.0),
            array('DepositingAmount', 123.45, 0.0),
            array('DepositingAmount', 583, 0.0),
            array('ReversingApprovedAmount', 123.45, 0.0),
            array('ReversingApprovedAmount', 583, 0.0),
            array('ReversingCreditedAmount', 252.63, 0.0),
            array('ReversingCreditedAmount', 5234, 0.0),
            array('ReversingDepositedAmount', 123.45, 0.0),
            array('ReversingDepositedAmount', 583, 0.0),
        );
    }

    /**
     * Creates a payment with sample financial transactions:
     * - 1 APPROVE transaction
     * - 3 DEPOSIT transactions
     * - 2 REVERSE_APPROVAL transactions
     * - 4 REVERSE_DEPOSIT transactions.
     *
     * @return Payment
     */
    protected function getPayment()
    {
        $payment = new Payment($this->getInstruction(), 123.45);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_APPROVE);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_APPROVAL);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_APPROVAL);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);

        return $payment;
    }

    protected function getInstruction()
    {
        return new PaymentInstruction(123, 'EUR', 'foo', new ExtendedData());
    }
}
