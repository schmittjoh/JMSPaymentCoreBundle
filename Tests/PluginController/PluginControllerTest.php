<?php

namespace JMS\Payment\CoreBundle\Tests\PluginController;

use JMS\Payment\CoreBundle\Entity\Credit;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException;
use JMS\Payment\CoreBundle\Plugin\Exception\TimeoutException as PluginTimeoutException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\PluginController\Event\PaymentInstructionStateChangeEvent;
use JMS\Payment\CoreBundle\PluginController\Event\PaymentStateChangeEvent;
use JMS\Payment\CoreBundle\PluginController\PluginController;
use JMS\Payment\CoreBundle\PluginController\Result;

class PluginControllerTest extends \PHPUnit_Framework_TestCase
{
    private $dispatcher;

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentException
     * @dataProvider getInvalidPaymentStatesForDependentCredit
     */
    public function testCreditOnlyAcceptsValidPaymentStatesOnDependentCredit($invalidState)
    {
        $controller = $this->getController();

        $credit = $this->getCredit(false);
        $instruction = $credit->getPaymentInstruction();
        $instruction->setState(PaymentInstruction::STATE_VALID);
        $payment = $credit->getPayment();
        $instruction->setDepositedAmount(100);
        $payment->setDepositedAmount(100);
        $payment->setState($invalidState);

        $this->callCredit($controller, array($credit, 10));
    }

    public function getInvalidPaymentStatesForDependentCredit()
    {
        return array(
            array(PaymentInterface::STATE_APPROVING),
            array(PaymentInterface::STATE_CANCELED),
            array(PaymentInterface::STATE_FAILED),
            array(PaymentInterface::STATE_NEW),
        );
    }

    /**
     * @dataProvider getTestAmountsForDependentCreditOnRetry
     * @expectedException \InvalidArgumentException
     */
    public function testCreditOnlyAcceptsValidAmountsForDependentCreditOnRetry($amount)
    {
        $controller = $this->getController();

        $instruction = new PaymentInstruction(111, 'EUR', 'foo', new ExtendedData());
        $instruction->setState(PaymentInstruction::STATE_VALID);
        $credit = new Credit($instruction, 100);
        $credit->setState(CreditInterface::STATE_CREDITING);
        $payment = new Payment($instruction, 10);
        $payment->setState(Payment::STATE_APPROVED);
        $credit->setPayment($payment);
        $credit->setCreditingAmount(7.12);

        $instruction->setDepositedAmount(10);
        $payment->setDepositedAmount(5.0);
        $payment->setCreditingAmount(0.01);
        $payment->setCreditedAmount(0.01);
        $payment->setReversingDepositedAmount(0.01);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_CREDIT);
        $transaction->setState(FinancialTransaction::STATE_PENDING);
        $credit->addTransaction($transaction);

        $this->callCredit($controller, array($credit, $amount));
    }

    public function getTestAmountsForDependentCreditOnRetry()
    {
        return array(
            array(0.0),
            array(0.01),
            array(7.12),
            array(100.0),
        );
    }

    /**
     * @dataProvider getTestAmountsForDependentCredit
     * @expectedException \InvalidArgumentException
     */
    public function testCreditOnlyAcceptsValidAmountsForDependentCredit($amount)
    {
        $controller = $this->getController();

        $instruction = new PaymentInstruction(111, 'EUR', 'foo', new ExtendedData());
        $instruction->setState(PaymentInstruction::STATE_VALID);
        $credit = new Credit($instruction, 100);
        $payment = new Payment($instruction, 10);
        $payment->setState(Payment::STATE_APPROVED);
        $credit->setPayment($payment);

        $instruction->setDepositedAmount(10);
        $payment->setDepositedAmount(5.0);
        $payment->setCreditingAmount(0.01);
        $payment->setCreditedAmount(0.01);
        $payment->setReversingDepositedAmount(0.01);

        $this->callCredit($controller, array($credit, $amount));
    }

    public function getTestAmountsForDependentCredit()
    {
        return array(
            array(4.98),
            array(4.99),
            array(5.00),
            array(12345),
        );
    }

    /**
     * @dataProvider getTestAmountsForIndependentCreditRetryTransaction
     * @expectedException \InvalidArgumentException
     */
    public function testCreditOnlyAcceptsValidAmountsForIndependentCreditsOnRetry($amount)
    {
        $controller = $this->getController();

        $credit = $this->getCredit();
        $instruction = $credit->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        $instruction->setCreditingAmount(123.44);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransaction::TRANSACTION_TYPE_CREDIT);
        $transaction->setState(FinancialTransaction::STATE_PENDING);
        $credit->addTransaction($transaction);

        $this->callCredit($controller, array($credit, $amount));
    }

    public function getTestAmountsForIndependentCreditRetryTransaction()
    {
        return array(
            array(12.345),
            array(123.43),
            array(123.44),
            array(123.45),
            array(123.46),
            array(123456),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @dataProvider getTestAmountsForIndependentCredit
     */
    public function testCreditOnlyAcceptsValidAmountsForIndependentCredits($amount)
    {
        $controller = $this->getController();

        $instruction = new PaymentInstruction(150.0, 'EUR', 'foo', new ExtendedData());
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        $credit = new Credit($instruction, 50);

        $instruction->setDepositedAmount(10.0);
        $instruction->setReversingDepositedAmount(0.01);
        $instruction->setCreditedAmount(0.01);
        $instruction->setCreditingAmount(0.01);

        $this->callCredit($controller, array($credit, $amount));
    }

    public function getTestAmountsForIndependentCredit()
    {
        return array(
            array(10.0),
            array(9.99),
            array(9.98),
            array(50.01),
            array(40.0),
            array(1032),
        );
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidCreditException
     * @dataProvider getInvalidCreditStatesForCredit
     */
    public function testCreditDoesNotAcceptInvalidCreditState($invalidState)
    {
        $controller = $this->getController();
        $credit = $this->getCredit();
        $credit->setState($invalidState);
        $credit->getPaymentInstruction()->setState(PaymentInstruction::STATE_VALID);

        $this->callCredit($controller, array($credit, 100));
    }

    public function getInvalidCreditStatesForCredit()
    {
        return array(
            array(CreditInterface::STATE_CANCELED),
            array(CreditInterface::STATE_CREDITED),
            array(CreditInterface::STATE_FAILED),
        );
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentInstructionException
     * @dataProvider getInvalidPaymentInstructionStatesForCredit
     */
    public function testCreditDoesNotAcceptInvalidPaymentInstructionState($invalidState)
    {
        $controller = $this->getController();
        $credit = $this->getCredit();
        $credit->getPaymentInstruction()->setState($invalidState);

        $this->callCredit($controller, array($credit, 100));
    }

    public function getInvalidPaymentInstructionStatesForCredit()
    {
        return array(
            array(PaymentInstructionInterface::STATE_CLOSED),
            array(PaymentInstructionInterface::STATE_INVALID),
            array(PaymentInstructionInterface::STATE_NEW),
        );
    }

    public function testCreateDependentCredit()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $payment->setState(PaymentInterface::STATE_APPROVED);
        $payment->getPaymentInstruction()->setState(PaymentInstructionInterface::STATE_VALID);

        $controller
            ->expects($this->once())
            ->method('buildCredit')
            ->with($this->equalTo($payment->getPaymentInstruction()), $this->equalTo(100))
            ->will($this->returnValue($credit = new Credit($payment->getPaymentInstruction(), 10)))
        ;

        $returnedCredit = $this->createDependentCredit($controller, array($payment, 100));

        $this->assertSame($credit, $returnedCredit);
        $this->assertSame($payment, $credit->getPayment());
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentException
     * @dataProvider getInvalidPaymentStatesForCreateDependentCredit
     */
    public function testCreateDependentCreditDoesOnlyAcceptValidPayments($invalidState)
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $payment->setState($invalidState);
        $payment->getPaymentInstruction()->setState(PaymentInstructionInterface::STATE_VALID);

        $this->createDependentCredit($controller, array($payment, 100));
    }

    public function getInvalidPaymentStatesForCreateDependentCredit()
    {
        return array(
            array(PaymentInterface::STATE_APPROVING),
            array(PaymentInterface::STATE_CANCELED),
            array(PaymentInterface::STATE_FAILED),
            array(PaymentInterface::STATE_NEW),
        );
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentInstructionException
     * @dataProvider getInvalidInstructionStatesForCreateDependentCredit
     */
    public function testCreateDependentCreditDoesOnlyAcceptValidPaymentInstruction($invalidState)
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $payment->setState(PaymentInterface::STATE_APPROVED);
        $payment->getPaymentInstruction()->setState($invalidState);

        $this->createDependentCredit($controller, array($payment, 100));
    }

    public function getInvalidInstructionStatesForCreateDependentCredit()
    {
        return array(
            array(PaymentInstructionInterface::STATE_CLOSED),
            array(PaymentInstructionInterface::STATE_INVALID),
            array(PaymentInstructionInterface::STATE_NEW),
        );
    }

    public function testApproveAndDepositPluginReturnsSuccessfulResponseInRetryTransaction()
    {
        $controller = $this->getController();

        $payment = $this->getPayment();
        $payment->setState(PaymentInterface::STATE_APPROVING);
        $payment->setApprovingAmount(100);
        $payment->setDepositingAmount(100);

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        $instruction->setApprovedAmount(30);
        $instruction->setDepositedAmount(20);
        $instruction->setApprovingAmount(110);
        $instruction->setDepositingAmount(110);

        $transaction = new FinancialTransaction();
        $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT);
        $transaction->setState(FinancialTransactionInterface::STATE_PENDING);
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setProcessedAmount(50);
        $payment->addTransaction($transaction);

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approveAndDeposit')
        ;
        $controller->addPlugin($plugin);

        $result = $this->callApproveAndDeposit($controller, array($payment, 100));

        $this->assertInstanceOf('JMS\Payment\CoreBundle\PluginController\Result', $result);
        $this->assertSame($transaction, $result->getFinancialTransaction());
        $this->assertSame(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame(PluginInterface::REASON_CODE_SUCCESS, $result->getReasonCode());
        $this->assertSame(PaymentInterface::STATE_DEPOSITED, $payment->getState());
        $this->assertEquals(0, $payment->getApprovingAmount());
        $this->assertEquals(0, $payment->getDepositingAmount());
        $this->assertEquals(50, $payment->getApprovedAmount());
        $this->assertEquals(50, $payment->getDepositedAmount());
        $this->assertEquals(10, $instruction->getApprovingAmount());
        $this->assertEquals(10, $instruction->getDepositingAmount());
        $this->assertEquals(80, $instruction->getApprovedAmount());
        $this->assertEquals(70, $instruction->getDepositedAmount());
    }

    public function testApproveAndDepositPluginReturnsSuccessfulResponse()
    {
        $controller = $this->getController(array(), false);
        $payment = $this->getPayment();

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        $instruction->setApprovedAmount(30);
        $instruction->setApprovingAmount(10);
        $instruction->setDepositingAmount(20);
        $instruction->setDepositedAmount(40);

        $transaction = new FinancialTransaction();
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setProcessedAmount(50);
        $controller
            ->expects($this->once())
            ->method('buildFinancialTransaction')
            ->will($this->returnValue($transaction))
        ;

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approveAndDeposit')
        ;
        $controller->addPlugin($plugin);

        $result = $this->callApproveAndDeposit($controller, array($payment, 100));

        $this->assertInstanceOf('JMS\Payment\CoreBundle\PluginController\Result', $result);
        $this->assertSame($transaction, $result->getFinancialTransaction());
        $this->assertSame(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame(PluginInterface::REASON_CODE_SUCCESS, $result->getReasonCode());
        $this->assertSame(PaymentInterface::STATE_DEPOSITED, $payment->getState());
        $this->assertEquals(0, $payment->getApprovingAmount());
        $this->assertEquals(0, $payment->getDepositingAmount());
        $this->assertEquals(50, $payment->getApprovedAmount());
        $this->assertEquals(50, $payment->getDepositedAmount());
        $this->assertEquals(10, $instruction->getApprovingAmount());
        $this->assertEquals(20, $instruction->getDepositingAmount());
        $this->assertEquals(80, $instruction->getApprovedAmount());
        $this->assertEquals(90, $instruction->getDepositedAmount());
    }

    public function testApproveAndDepositPluginReturnsUnsuccessfulResponse()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();

        $instruction = $payment->getPaymentInstruction();
        $instruction->setApprovingAmount(20);
        $instruction->setDepositingAmount(10);
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approveAndDeposit')
        ;
        $controller->addPlugin($plugin);

        $result = $this->callApproveAndDeposit($controller, array($payment, 100));
        $transaction = $result->getFinancialTransaction();

        $this->assertInstanceOf('JMS\Payment\CoreBundle\PluginController\Result', $result);
        $this->assertSame(Result::STATUS_FAILED, $result->getStatus());
        $this->assertSame($transaction->getReasonCode(), $result->getReasonCode());
        $this->assertSame(FinancialTransactionInterface::STATE_FAILED, $transaction->getState());
        $this->assertSame(PaymentInterface::STATE_FAILED, $payment->getState());
        $this->assertEquals(0, $payment->getApprovingAmount());
        $this->assertEquals(0, $payment->getDepositingAmount());
        $this->assertEquals(20, $instruction->getApprovingAmount());
        $this->assertEquals(10, $instruction->getDepositingAmount());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedMessage foo
     */
    public function testApproveAndDepositPluginThrowsException()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $payment->getPaymentInstruction()->setState(PaymentInstructionInterface::STATE_VALID);

        $plugin = $this->getPlugin();
        $exception = new \RuntimeException('foo');
        $plugin
            ->expects($this->once())
            ->method('approveAndDeposit')
            ->will($this->throwException($exception))
        ;
        $controller->addPlugin($plugin);

        $this->callApproveAndDeposit($controller, array($payment, 100));
    }

    public function testApproveAndDepositPluginThrowsTimeoutException()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();

        $instruction = $payment->getPaymentInstruction();
        $instruction->setApprovingAmount(10);
        $instruction->setDepositingAmount(20);
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $plugin = $this->getPlugin();
        $exception = new PluginTimeoutException('timeout');
        $plugin
            ->expects($this->once())
            ->method('approveAndDeposit')
            ->will($this->throwException($exception))
        ;
        $controller->addPlugin($plugin);

        $result = $this->callApproveAndDeposit($controller, array($payment, 100));
        $transaction = $result->getFinancialTransaction();

        $this->assertInstanceOf('JMS\Payment\CoreBundle\PluginController\Result', $result);
        $this->assertSame(Result::STATUS_PENDING, $result->getStatus());
        $this->assertSame(PluginInterface::REASON_CODE_TIMEOUT, $result->getReasonCode());
        $this->assertSame(PaymentInterface::STATE_APPROVING, $payment->getState());
        $this->assertSame(FinancialTransactionInterface::STATE_PENDING, $transaction->getState());
        $this->assertSame(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT, $transaction->getTransactionType());
        $this->assertEquals(100, $payment->getApprovingAmount());
        $this->assertEquals(100, $payment->getDepositingAmount());
        $this->assertEquals(110, $instruction->getApprovingAmount());
        $this->assertEquals(120, $instruction->getDepositingAmount());
        $this->assertTrue($result->isRecoverable());
    }

    /**
     * @dataProvider getInvalidAmountApproveAndDepositOnRetry
     * @expectedException \InvalidArgumentException
     */
    public function testApproveAndDepositThrowsExceptionWhenRequestedAmountIsNotEqualToApprovingOrDepositingAmountOnRetry($amount)
    {
        $controller = $this->getController();
        $payment = $this->getPayment(array(1 => 123.45));
        $payment->setState(PaymentInterface::STATE_APPROVING);
        $payment->getPaymentInstruction()->setState(PaymentInstructionInterface::STATE_VALID);
        $payment->setApprovingAmount(12.34);
        $payment->setDepositingAmount(12.33);

        $this->callApproveAndDeposit($controller, array($payment, $amount));
    }

    public function getInvalidAmountApproveAndDepositOnRetry()
    {
        return array(
            array(12.33),
            array(12.34),
            array(12.32),
            array(12.35),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testApproveAndDepositThrowsExceptionWhenRequestedAmountIsGreaterThanTargetAmount()
    {
        $controller = $this->getController();
        $payment = $this->getPayment(array(1 => 123.45));
        $payment->getPaymentInstruction()->setState(PaymentInstructionInterface::STATE_VALID);

        $this->callApproveAndDeposit($controller, array($payment, 123.46));
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentInstructionException
     */
    public function testApproveAndDepositThrowsExceptionWhenPaymentInstructionHasPendingTransaction()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $payment->getPaymentInstruction()->setState(PaymentInstructionInterface::STATE_VALID);

        $transaction = new FinancialTransaction();
        $transaction->setState(FinancialTransactionInterface::STATE_PENDING);
        $payment->addTransaction($transaction);

        $this->callApproveAndDeposit($controller, array($payment, 100));
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentException
     * @dataProvider getInvalidPaymentStatesForApproval
     */
    public function testApproveAndDepositDoesNotAcceptInvalidPaymentState($invalidState)
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $payment->setState($invalidState);

        $payment->getPaymentInstruction()->setState(PaymentInstructionInterface::STATE_VALID);

        $this->callApproveAndDeposit($controller, array($payment, 100));
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentInstructionException
     * @dataProvider getInvalidInstructionStatesForApproval
     */
    public function testApproveAndDepositDoesNotAcceptInvalidPaymentInstructionState($invalidState)
    {
        $controller = $this->getController();
        $payment = $this->getPayment();

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState($invalidState);

        $this->callApproveAndDeposit($controller, array($payment, 100));
    }

    public function testApprovePluginReturnsSuccessfulResponse()
    {
        $controller = $this->getController(array(), false);
        $payment = $this->getPayment();
        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
        ;
        $controller->addPlugin($plugin);

        $transaction = new FinancialTransaction();
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setProcessedAmount(50.12);
        $controller
            ->expects($this->once())
            ->method('buildFinancialTransaction')
            ->will($this->returnValue($transaction))
        ;

        $result = $this->callApprove($controller, array($payment, 100));

        $this->assertSame($transaction, $result->getFinancialTransaction());
        $this->assertSame($transaction, $payment->getApproveTransaction());
        $this->assertInstanceOf('JMS\Payment\CoreBundle\PluginController\Result', $result);
        $this->assertTrue(PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode());
        $this->assertSame(PluginInterface::RESPONSE_CODE_SUCCESS, $transaction->getResponseCode());
        $this->assertSame(Result::STATUS_SUCCESS, $result->getStatus());
        $this->assertSame(PluginInterface::REASON_CODE_SUCCESS, $result->getReasonCode());
        $this->assertSame(PaymentInterface::STATE_APPROVED, $payment->getState());
        $this->assertSame(FinancialTransactionInterface::STATE_SUCCESS, $transaction->getState());
        $this->assertEquals(0, $payment->getApprovingAmount());
        $this->assertEquals(50.12, $payment->getApprovedAmount());
        $this->assertEquals(0, $instruction->getApprovingAmount());
        $this->assertEquals(50.12, $instruction->getApprovedAmount());
    }

    public function testApprovePluginReturnsUnsuccessfulResponse()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
        ;
        $controller->addPlugin($plugin);

        $result = $this->callApprove($controller, array($payment, 100));

        $this->assertInstanceOf('JMS\Payment\CoreBundle\PluginController\Result', $result);
        $this->assertSame(Result::STATUS_FAILED, $result->getStatus());
        $this->assertSame(PaymentInterface::STATE_FAILED, $payment->getState());
        $this->assertSame(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE, $result->getFinancialTransaction()->getTransactionType());
        $this->assertSame(FinancialTransactionInterface::STATE_FAILED, $result->getFinancialTransaction()->getState());
        $this->assertSame($result->getFinancialTransaction()->getReasonCode(), $result->getReasonCode());
        $this->assertEquals(0, $payment->getApprovingAmount());
        $this->assertEquals(0, $payment->getApprovedAmount());
        $this->assertEquals(0, $instruction->getApprovingAmount());
        $this->assertEquals(0, $instruction->getApprovedAmount());
    }

    public function testApprovePluginThrowsTimeoutException()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstruction::STATE_VALID);

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
            ->will($this->throwException(new PluginTimeoutException('some error occurred')))
        ;
        $controller->addPlugin($plugin);

        $this->assertEquals(0.0, $instruction->getApprovingAmount());
        $this->assertEquals(0.0, $payment->getApprovingAmount());
        $this->assertSame(PaymentInterface::STATE_NEW, $payment->getState());

        $result = $this->callApprove($controller, array($payment, 123.45));

        $this->assertInstanceOf('JMS\Payment\CoreBundle\PluginController\Result', $result);
        $this->assertEquals(123.45, $instruction->getApprovingAmount());
        $this->assertEquals(123.45, $payment->getApprovingAmount());
        $this->assertSame(PaymentInterface::STATE_APPROVING, $payment->getState());
        $this->assertSame(Result::STATUS_PENDING, $result->getStatus());
        $this->assertSame(PluginInterface::REASON_CODE_TIMEOUT, $result->getReasonCode());
        $this->assertSame(FinancialTransactionInterface::STATE_PENDING, $result->getFinancialTransaction()->getState());
        $this->assertTrue($result->isRecoverable());
    }

    /**
     * @expectedException \RuntimeException
     */
    public function testApprovePluginThrowsException()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $instruction = $payment->getPaymentInstruction();

        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        $instruction->setApprovingAmount(10);

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
            ->will($this->throwException(new \RuntimeException('some error occurred')))
        ;
        $controller->addPlugin($plugin);

        $this->callApprove($controller, array($payment, 100));
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\Exception
     * @expectedMessage The PaymentInstruction can only ever have one pending transaction.
     */
    public function testApproveDoesNotAcceptNewTransactionIfInstructionHasPendingTransaction()
    {
        $controller = $this->getController();
        $payment = $this->getPayment();

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $transaction = new FinancialTransaction();
        $payment->addTransaction($transaction);
        $payment->setState(FinancialTransactionInterface::STATE_PENDING);

        $this->callApprove($controller, array($payment, 100));
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\Exception
     * @expectedMessage The Payment's target amount must equal the requested amount in a retry transaction.
     * @dataProvider getApprovalTestAmounts
     */
    public function testApproveAmountMustEqualPaymentsIfRetry($amount)
    {
        $controller = $this->getController();

        $payment = $this->getPayment();
        $payment->setState(PaymentInterface::STATE_APPROVING);

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $this->callApprove($controller, array($payment, 100));
    }

    public function getApprovalTestAmounts()
    {
        return array(
            array(10),
            array(110),
        );
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\Exception
     * @expectedMessage The Payment's target amount is less than the requested amount.
     */
    public function testApproveAmountCannotBeHigherThanPaymentsIfFirstTry()
    {
        $controller = $this->getController();

        $payment = $this->getPayment(array(1 => 50));

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $this->callApprove($controller, array($payment, 100));
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\Exception
     * @expectedMessage The Payment's state must be STATE_NEW, or STATE_PENDING.
     * @dataProvider getInvalidPaymentStatesForApproval
     */
    public function testApprovePaymentMustHaveValidState($invalidState)
    {
        $controller = $this->getController();
        $payment = $this->getPayment();
        $payment->setState($invalidState);

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $this->callApprove($controller, array($payment, 1));
    }

    public function getInvalidPaymentStatesForApproval()
    {
        return array(
            array(PaymentInterface::STATE_APPROVED),
            array(PaymentInterface::STATE_CANCELED),
            array(PaymentInterface::STATE_EXPIRED),
            array(PaymentInterface::STATE_FAILED),
        );
    }

    /**
     * @expectedException JMS\Payment\CoreBundle\PluginController\Exception\Exception
     * @expectedMessage The PaymentInstruction's state must be STATE_VALID.
     * @dataProvider getInvalidInstructionStatesForApproval
     */
    public function testApprovePaymentInstructionMustHaveValidState($invalidState)
    {
        $controller = $this->getController();
        $payment = $this->getPayment();

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState($invalidState);

        $this->callApprove($controller, array($payment, 1));
    }

    public function getInvalidInstructionStatesForApproval()
    {
        return array(
            array(PaymentInstructionInterface::STATE_CLOSED),
            array(PaymentInstructionInterface::STATE_INVALID),
            array(PaymentInstructionInterface::STATE_NEW),
        );
    }

    public function testDeposit()
    {
        $controller = $this->getController(array(), false);
        $controller->addPlugin($plugin = $this->getPlugin());
        $plugin
            ->expects($this->once())
            ->method('deposit')
        ;

        $controller
            ->expects($this->once())
            ->method('buildFinancialTransaction')
            ->will($this->returnCallback(function () {
                $transaction = new FinancialTransaction();
                $transaction->setProcessedAmount(123.45);
                $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
                $transaction->setReasonCode(PluginInterface::REASON_CODE_SUCCESS);

                return $transaction;
            }))
        ;

        $payment = $this->getPayment();
        $payment->setState(PaymentInterface::STATE_APPROVED);
        $payment->setApprovedAmount(123.45);

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        $instruction->setApprovedAmount(10);

        $result = $this->callDeposit($controller, array($payment, 123.45));

        $this->assertEquals(Result::STATUS_SUCCESS, $result->getStatus(), 'Result status is not success: '.$result->getReasonCode());
        $this->assertEquals(123.45, $payment->getDepositedAmount());
        $this->assertEquals(123.45, $instruction->getDepositedAmount());
        $this->assertEquals(PaymentInterface::STATE_DEPOSITED, $payment->getState());
        $this->assertEquals(0, $payment->getDepositingAmount());
        $this->assertEquals(0, $instruction->getDepositingAmount());
    }

    public function testDepositPluginThrowsFinancialException()
    {
        $controller = $this->getController();
        $controller->addPlugin($plugin = $this->getPlugin());
        $plugin
            ->expects($this->once())
            ->method('deposit')
            ->will($this->throwException(new FinancialException('some error')))
        ;

        $payment = $this->getPayment();
        $payment->setState(PaymentInterface::STATE_APPROVED);
        $payment->setApprovedAmount(10);

        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstruction::STATE_VALID);
        $instruction->setApprovedAmount(10);

        $result = $this->callDeposit($controller, array($payment, 10));
        $this->assertEquals(Result::STATUS_FAILED, $result->getStatus());
        $this->assertEquals(0, $payment->getDepositingAmount());
        $this->assertEquals(0, $payment->getDepositedAmount());
        $this->assertEquals(PaymentInterface::STATE_FAILED, $payment->getState());
        $this->assertEquals(0, $instruction->getDepositingAmount());
        $this->assertEquals(0, $instruction->getDepositedAmount());
    }

    public function testDispatchesEvents()
    {
        $controller = $this->getController(array(), false, true);

        $payment = $this->getPayment();
        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
        ;
        $controller->addPlugin($plugin);

        $transaction = new FinancialTransaction();
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setProcessedAmount(50.12);
        $controller
            ->expects($this->once())
            ->method('buildFinancialTransaction')
            ->will($this->returnValue($transaction))
        ;

        $this->dispatcher
            ->expects($this->at(0))
            ->method('dispatch')
            ->with('payment.state_change', new PaymentStateChangeEvent($payment, PaymentInterface::STATE_NEW))
        ;
        $this->dispatcher
            ->expects($this->at(1))
            ->method('dispatch')
            ->with('payment.state_change', new PaymentStateChangeEvent($payment, PaymentInterface::STATE_APPROVING))
        ;
        $this->dispatcher
            ->expects($this->at(2))
            ->method('dispatch')
            ->with('payment_instruction.state_change', new PaymentInstructionStateChangeEvent($instruction, PaymentInstructionInterface::STATE_VALID))
        ;

        $this->callApprove($controller, array($payment, 100));

        $controller->closePaymentInstruction($instruction);
    }

    protected function getPlugin()
    {
        $plugin = $this->getMockBuilder('JMS\Payment\CoreBundle\Plugin\PluginInterface')->getMock();
        $plugin
            ->expects($this->once())
            ->method('processes')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true))
        ;

        return $plugin;
    }

    protected function getCredit($independent = true, $arguments = array())
    {
        $arguments = $arguments + array(
            $this->getInstruction(),
            123.45,
        );

        $credit = new Credit($arguments[0], $arguments[1]);

        if (false === $independent) {
            $credit->setPayment(new Payment($arguments[0], 200));
        }

        return $credit;
    }

    protected function getPayment($arguments = array())
    {
        $arguments = $arguments + array(
            $this->getInstruction(),
            123.45,
        );

        return new Payment($arguments[0], $arguments[1]);
    }

    protected function getInstruction($arguments = array())
    {
        $arguments = $arguments + array(
            123.45,
            'EUR',
            'foo',
            new ExtendedData(),
        );

        return new PaymentInstruction($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
    }

    protected function getController(array $options = array(), $addTransaction = true, $withDispatcher = false)
    {
        $options = array_merge(array(
            'financial_transaction_class' => 'JMS\Payment\CoreBundle\Entity\FinancialTransaction',
            'result_class' => 'JMS\Payment\CoreBundle\PluginController\Result',
        ), $options);

        $args = array($options);

        if ($withDispatcher) {
            $args[] = $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')->getMock();
        }

        $mock = $this->getMockForAbstractClass(
            'JMS\Payment\CoreBundle\PluginController\PluginController',
            $args
        );

        if ($addTransaction) {
            $mock
                ->expects($this->any())
                ->method('buildFinancialTransaction')
                ->will($this->returnValue(new FinancialTransaction()))
            ;
        }

        return $mock;
    }

    protected function callApprove(PluginController $controller, array $args)
    {
        $reflection = new \ReflectionMethod($controller, 'doApprove');
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $args);
    }

    protected function callDeposit(PluginController $controller, array $args)
    {
        $reflection = new \ReflectionMethod($controller, 'doDeposit');
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $args);
    }

    protected function callCredit(PluginController $controller, array $args)
    {
        $reflection = new \ReflectionMethod($controller, 'doCredit');
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $args);
    }

    protected function callApproveAndDeposit(PluginController $controller, array $args)
    {
        $reflection = new \ReflectionMethod($controller, 'doApproveAndDeposit');
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $args);
    }

    protected function createDependentCredit(PluginController $controller, array $args)
    {
        $reflection = new \ReflectionMethod($controller, 'doCreateDependentCredit');
        $reflection->setAccessible(true);

        return $reflection->invokeArgs($controller, $args);
    }
}
