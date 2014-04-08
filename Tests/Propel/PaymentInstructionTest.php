<?php

namespace JMS\Payment\CoreBundle\Tests\Propel;

use JMS\Payment\CoreBundle\Propel\Credit;
use JMS\Payment\CoreBundle\Propel\Payment;
use JMS\Payment\CoreBundle\Propel\FinancialTransaction;
use JMS\Payment\CoreBundle\Propel\PaymentInstruction;
use JMS\Payment\CoreBundle\Propel\ExtendedData;

class PaymentInstructionTest extends \PHPUnit_Framework_TestCase
{
    protected function setUp()
    {
        if (!class_exists('Propel')) {
            $this->markTestSkipped('Propel not installed');
        }
    }

    public function testConstructor()
    {
        $data = new ExtendedData();
        $instruction = new PaymentInstruction(123.45, 'USD', 'foo', $data);

        $this->assertEquals(123.45, $instruction->getAmount());
        $this->assertEquals('USD', $instruction->getCurrency());
        $this->assertEquals('foo', $instruction->getPaymentSystemName());
        $this->assertSame($data, $instruction->getExtendedData());
        $this->assertSame(FinancialTransaction::STATE_NEW, $instruction->getState());
        $this->assertInstanceOf('PropelObjectCollection', $instruction->getCredits());
        $this->assertEquals(0, count($instruction->getCredits()));
        $this->assertInstanceOf('PropelObjectCollection', $instruction->getPayments());
        $this->assertEquals(0, count($instruction->getPayments()));
        $this->assertEquals(0.0, $instruction->getApprovingAmount());
        $this->assertEquals(0.0, $instruction->getApprovedAmount());
        $this->assertEquals(0.0, $instruction->getDepositingAmount());
        $this->assertEquals(0.0, $instruction->getDepositedAmount());
        $this->assertEquals(0.0, $instruction->getCreditingAmount());
        $this->assertEquals(0.0, $instruction->getCreditedAmount());
        $this->assertEquals(0.0, $instruction->getReversingApprovedAmount());
        $this->assertEquals(0.0, $instruction->getReversingCreditedAmount());
        $this->assertEquals(0.0, $instruction->getReversingDepositedAmount());
        $this->assertNull($instruction->getId());
        $this->assertNull($instruction->getUpdatedAt());
    }

    public function testAddCredit()
    {
        $instruction = $this->getInstruction();

        $this->assertEquals(0, count($instruction->getCredits()));

        $credit = new Credit($instruction, 123.12);

        $this->assertEquals(1, count($instruction->getCredits()));
        $this->assertSame($credit, $instruction->getCredits()->get(0));
        $this->assertSame($credit->getPaymentInstruction(), $instruction);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddCreditDoesNotAcceptCreditFromAnotherInstruction()
    {
        $instruction1 = $this->getInstruction();
        $instruction2 = $this->getInstruction();

        $credit = new Credit($instruction1, 123);
        $instruction2->addCredit($credit);
    }

    public function testAddPayment()
    {
        $instruction = $this->getInstruction();

        $this->assertEquals(0, count($instruction->getPayments()));

        $payment = new Payment($instruction, 100);

        $this->assertEquals(1, count($instruction->getPayments()));
        $this->assertSame($payment, $instruction->getPayments()->get(0));
        $this->assertSame($payment->getPaymentInstruction(), $instruction);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testAddPaymentDoesNotAcceptPaymentFromAnotherInstruction()
    {
        $instruction1 = $this->getInstruction();
        $instruction2 = $this->getInstruction();

        $payment = new Payment($instruction1, 100);
        $instruction2->addPayment($payment);
    }

    public function testGetPendingTransactionOnPayment()
    {
        $instruction = $this->getInstruction();
        $payment = new Payment($instruction, 100);

        $this->assertNull($instruction->getPendingTransaction());

        $transaction = new FinancialTransaction();
        $payment->addTransaction($transaction);
        $transaction->setState(FinancialTransaction::STATE_PENDING);

        $this->assertSame($transaction, $instruction->getPendingTransaction());
    }

    public function testGetPendingTransactionOnCredit()
    {
        $instruction = $this->getInstruction();
        $credit = new Credit($instruction, 123);

        $this->assertNull($instruction->getPendingTransaction());

        $transaction = new FinancialTransaction();
        $credit->addFinancialTransaction($transaction);
        $transaction->setState(FinancialTransaction::STATE_PENDING);

        $this->assertSame($transaction, $instruction->getPendingTransaction());
    }

    public function testHasPendingTransactionOnPayment()
    {
        $instruction = $this->getInstruction();
        $payment = new Payment($instruction, 100);

        $this->assertFalse($instruction->hasPendingTransaction());

        $transaction = new FinancialTransaction;
        $payment->addTransaction($transaction);
        $transaction->setState(FinancialTransaction::STATE_PENDING);

        $this->assertTrue($instruction->hasPendingTransaction());
    }

    public function testHasPendingTransactionOnCredit()
    {
        $instruction = $this->getInstruction();
        $credit = new Credit($instruction, 123.45);

        $this->assertFalse($instruction->hasPendingTransaction());

        $transaction = new FinancialTransaction;
        $credit->addFinancialTransaction($transaction);
        $transaction->setState(FinancialTransaction::STATE_PENDING);

        $this->assertTrue($instruction->hasPendingTransaction());
    }

    /**
     * @dataProvider getSetterGetterTestData
     */
    public function testSimpleSettersGetters($propertyName, $value, $default)
    {
        $setter = 'set'.$propertyName;
        $getter = 'get'.$propertyName;
        $instruction = $this->getInstruction();

        $this->assertEquals($default, $instruction->$getter());
        $instruction->$setter($value);
        $this->assertEquals($value, $instruction->$getter());
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
            array('CreditedAmount', 123.45, 0.0),
            array('CreditedAmount', 583, 0.0),
            array('CreditingAmount', 123.45, 0.0),
            array('CreditingAmount', 583, 0.0),
            array('ReversingApprovedAmount', 254.32, 0.0),
            array('ReversingApprovedAmount', 423, 0.0),
            array('ReversingCreditedAmount', 5632.14, 0.0),
            array('ReversingCreditedAmount', 2576, 0.0),
            array('ReversingDepositedAmount', 256.24, 0.0),
            array('ReversingDepositedAmount', 5365, 0.0),
            array('State', PaymentInstruction::STATE_INVALID, PaymentInstruction::STATE_NEW),
        );
    }

    protected function getInstruction()
    {
        return new PaymentInstruction(123.45, 'EUR', 'foo', new ExtendedData());
    }
}