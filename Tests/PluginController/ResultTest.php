<?php

namespace JMS\Payment\CoreBundle\Tests\PluginController;

use JMS\Payment\CoreBundle\Entity\Credit;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Plugin\Exception\Exception;
use JMS\Payment\CoreBundle\PluginController\Result;

class ResultTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorWithInvalidArguments()
    {
        new Result();
    }

    public function testConstructPaymentInstructionResult()
    {
        $instruction = new PaymentInstruction(123, 'EUR', 'foo', new ExtendedData());
        $result = new Result($instruction, Result::STATUS_FAILED, 'foo');

        $this->assertSame($instruction, $result->getPaymentInstruction());
        $this->assertNull($result->getFinancialTransaction());
        $this->assertNull($result->getPayment());
        $this->assertNull($result->getCredit());
        $this->assertSame(Result::STATUS_FAILED, $result->getStatus());
        $this->assertEquals('foo', $result->getReasonCode());
    }

    public function testConstructFinancialTransactionResultWithCredit()
    {
        $transaction = $this->getTransaction(true);
        $result = new Result($transaction, Result::STATUS_SUCCESS, 'fooreason');

        $this->assertSame($transaction, $result->getFinancialTransaction());
        $this->assertSame($transaction->getCredit(), $result->getCredit());
        $this->assertNull($transaction->getPayment());
        $this->assertSame($transaction->getCredit()->getPaymentInstruction(), $result->getPaymentInstruction());
        $this->assertSame(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertEquals('fooreason', $result->getReasonCode());
    }

    public function testConstructFinancialTransactionResultWithPayment()
    {
        $transaction = $this->getTransaction();
        $result = new Result($transaction, Result::STATUS_SUCCESS, 'fooreason');

        $this->assertSame($transaction, $result->getFinancialTransaction());
        $this->assertSame($transaction->getPayment(), $result->getPayment());
        $this->assertNull($transaction->getCredit());
        $this->assertSame($transaction->getPayment()->getPaymentInstruction(), $result->getPaymentInstruction());
        $this->assertSame(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertEquals('fooreason', $result->getReasonCode());
    }

    public function testSetGetPluginException()
    {
        $result = $this->buildResult();
        $exception = new Exception('foo');

        $this->assertNull($result->getPluginException());
        $result->setPluginException($exception);
        $this->assertSame($exception, $result->getPluginException());
    }

    public function testIsSetRecoverable()
    {
        $result = $this->buildResult();

        $this->assertFalse($result->isRecoverable());
        $result->setRecoverable();
        $this->assertTrue($result->isRecoverable());
        $result->setRecoverable(false);
        $this->assertFalse($result->isRecoverable());
        $result->setRecoverable(true);
        $this->assertTrue($result->isRecoverable());
    }

    public function testIsAttentionRequiredWithPayment()
    {
        $result = $this->buildResult();

        $this->assertFalse($result->isAttentionRequired());
        $result->getPayment()->setAttentionRequired(true);
        $this->assertTrue($result->isAttentionRequired());
    }

    public function testIsAttentionRequiredWithCredit()
    {
        $transaction = $this->getTransaction(true);
        $result = new Result($transaction, Result::STATUS_FAILED, 'foo');

        $this->assertFalse($result->isAttentionRequired());
        $result->getCredit()->setAttentionRequired(true);
        $this->assertTrue($result->isAttentionRequired());
    }

    /**
     * @expectedException \LogicException
     */
    public function testIsPaymentAttentionRequiredThrowsExceptionWhenResultHasNoPayment()
    {
        $result = new Result(new PaymentInstruction(123.45, 'EUR', 'foo', new ExtendedData()), Result::STATUS_FAILED, 'foo');

        $result->isAttentionRequired();
    }

    protected function buildResult()
    {
        return new Result($this->getTransaction(), Result::STATUS_SUCCESS, 'foo');
    }

    protected function getTransaction($withCredit = false)
    {
        $instruction = new PaymentInstruction(123, 'EUR', 'foo', new ExtendedData());
        $transaction = new FinancialTransaction();

        if (!$withCredit) {
            $payment = new Payment($instruction, 100);
            $payment->addTransaction($transaction);
        } else {
            $credit = new Credit($instruction, 123.45);
            $credit->addTransaction($transaction);
        }

        return $transaction;
    }
}
