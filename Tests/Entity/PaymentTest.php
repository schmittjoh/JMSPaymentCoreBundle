<?php

namespace Bundle\PaymentBundle\Tests\Entity;

use Bundle\PaymentBundle\Entity\PaymentInstruction;

use Bundle\PaymentBundle\Entity\FinancialTransaction;

use Bundle\PaymentBundle\Entity\Payment;

class PaymentTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructor()
    {
        $payment = new Payment();
        
        $this->assertEquals(0.0, $payment->getApprovedAmount());
        $this->assertEquals(0.0, $payment->getApprovingAmount());
        $this->assertEquals(0.0, $payment->getDepositedAmount());
        $this->assertEquals(0.0, $payment->getDepositingAmount());
        $this->assertEquals(0.0, $payment->getReversingApprovedAmount());
        $this->assertEquals(0.0, $payment->getReversingDepositedAmount());
        $this->assertEquals(Payment::STATE_NEW, $payment->getState());
        $this->assertEquals(0.0, $payment->getTargetAmount());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $payment->getTransactions());
        $this->assertEquals(0, count($payment->getTransactions()));
        $this->assertFalse($payment->isAttentionRequired());
        $this->assertFalse($payment->isExpired());
        $this->assertNull($payment->getId());
    }
    
    public function testAddTransaction()
    {
        $payment = new Payment;
        $transaction = new FinancialTransaction;
        
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
        $payment = new Payment;
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);
        
        $approveTransaction = new FinancialTransaction;
        $approveTransaction->setTransactionType($approveType);
        $payment->addTransaction($approveTransaction);
        
        $transaction = new FinancialTransaction;
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
        $payment = new Payment;
        
        $this->assertNull($payment->getExpirationDate());
        $payment->setExpirationDate($date = new \DateTime());
        $this->assertSame($date, $payment->getExpirationDate());
    }
    
    public function testGetSetPaymentInstruction()
    {
        $payment = new Payment;
        
        $this->assertNull($payment->getPaymentInstruction());
        $payment->setPaymentInstruction($instruction = new PaymentInstruction(123, 'EUR', 'foo'));
        $this->assertSame($instruction, $payment->getPaymentInstruction());
    }
    
    public function testGetPendingTransaction()
    {
        $payment = new Payment;
        
        $this->assertNull($payment->getPendingTransaction());
        
        $transaction = new FinancialTransaction;
        $payment->addTransaction($transaction);
        
        $this->assertNull($payment->getPendingTransaction());
        
        $pendingTransaction = new FinancialTransaction;
        $pendingTransaction->setState(FinancialTransaction::STATE_PENDING);
        $payment->addTransaction($pendingTransaction);
        
        $this->assertSame($pendingTransaction, $payment->getPendingTransaction());
        
        $transaction = new FinancialTransaction;
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
        $payment = new Payment;
        
        $this->assertSame(Payment::STATE_NEW, $payment->getState());
        $payment->setState(Payment::STATE_APPROVED);
        $this->assertSame(Payment::STATE_APPROVED, $payment->getState());
    }
    
    public function testGetTransactions()
    {
        $payment = $this->getPayment();
        
        $this->assertEquals(10, count($payment->getTransactions()));
        
        $transaction = new FinancialTransaction;
        $payment->addTransaction($transaction);
        
        $this->assertEquals(11, count($payment->getTransactions()));
        $this->assertSame($transaction, $payment->getTransactions()->get(10));
    }
    
    public function testHasPendingTransaction()
    {
        $payment = new Payment;
        
        $this->assertFalse($payment->hasPendingTransaction());
        
        $transaction = new FinancialTransaction;
        $payment->addTransaction($transaction);
        
        $this->assertFalse($payment->hasPendingTransaction());
        
        $transaction = new FinancialTransaction;
        $transaction->setState(FinancialTransaction::STATE_PENDING);
        $payment->addTransaction($transaction);
        
        $this->assertTrue($payment->hasPendingTransaction());
    }
    
    public function testIsSetAttentionRequired()
    {
        $payment = new Payment;
        
        $this->assertFalse($payment->isAttentionRequired());
        $payment->setAttentionRequired(true);
        $this->assertTrue($payment->isAttentionRequired());
        $payment->setAttentionRequired(false);
        $this->assertFalse($payment->isAttentionRequired());
    }
    
    public function testIsExpiredDueToExpirationDate()
    {
        $payment = new Payment;
        
        $this->assertFalse($payment->isExpired());
        $payment->setExpirationDate(new \DateTime('yesterday'));
        $this->assertTrue($payment->isExpired());
        
        $payment->setExpirationDate(new \DateTime('tomorrow'));
        $this->assertFalse($payment->isExpired());
    }
    
    public function testIsSetExpired()
    {
        $payment = new Payment;
        
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
        $payment = new Payment;
        
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
            array('DepositedAmount', 123.45, 0.0),
            array('DepositedAmount', 583, 0.0),
            array('DepositingAmount', 123.45, 0.0),
            array('DepositingAmount', 583, 0.0),
            array('ReversingApprovedAmount', 123.45, 0.0),
            array('ReversingApprovedAmount', 583, 0.0),
            array('ReversingDepositedAmount', 123.45, 0.0),
            array('ReversingDepositedAmount', 583, 0.0),
        );
    }
    
    /**
     * Creates a payment with sample financial transactions:
     * - 1 APPROVE transaction
     * - 3 DEPOSIT transactions
     * - 2 REVERSE_APPROVAL transactions
     * - 4 REVERSE_DEPOSIT transactions
     * 
     * @return Payment
     */
    protected function getPayment()
    {
        $payment = new Payment;
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_APPROVE);
        $payment->addTransaction($transaction);
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_DEPOSIT);
        $payment->addTransaction($transaction);
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_APPROVAL);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_APPROVAL);
        $payment->addTransaction($transaction);
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);

        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);
        
        $transaction = new FinancialTransaction;
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_REVERSE_DEPOSIT);
        $payment->addTransaction($transaction);
        
        return $payment;
    }
}