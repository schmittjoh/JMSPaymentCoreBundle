<?php

namespace Bundle\PaymentBundle\Tests\PluginController;

use Bundle\PaymentBundle\Entity\FinancialTransaction;

use Bundle\PaymentBundle\Entity\FinancialTransactionInterface;
use Bundle\PaymentBundle\Entity\ExtendedData;
use Bundle\PaymentBundle\Entity\Payment;
use Bundle\PaymentBundle\Entity\PaymentInstruction;
use Bundle\PaymentBundle\Plugin\PluginInterface;
use Bundle\PaymentBundle\Plugin\Exception\Exception as PluginException;
use Bundle\PaymentBundle\Plugin\Exception\TimeoutException as PluginTimeoutException;
use Bundle\PaymentBundle\Entity\PaymentInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\PluginController\Result;
use Bundle\PaymentBundle\PluginController\PluginController;

class PluginControllerTest extends \PHPUnit_Framework_TestCase
{
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
        
        $transaction = new FinancialTransaction;
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
        $transaction->setProcessedAmount(50.12);
        $controller
            ->expects($this->once())
            ->method('buildFinancialTransaction')
            ->will($this->returnValue($transaction))
        ;
        
        $result = $this->callApprove($controller, array($payment, 100));
        
        $this->assertSame($transaction, $result->getFinancialTransaction());
        $this->assertInstanceOf('Bundle\PaymentBundle\PluginController\Result', $result);
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
        $instruction  = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        
        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
        ;
        $controller->addPlugin($plugin);
        
        $result = $this->callApprove($controller, array($payment, 100));
        
        $this->assertInstanceOf('Bundle\PaymentBundle\PluginController\Result', $result);
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

        $this->assertInstanceOf('Bundle\PaymentBundle\PluginController\Result', $result);
        $this->assertEquals(123.45, $instruction->getApprovingAmount());
        $this->assertEquals(123.45, $payment->getApprovingAmount());
        $this->assertSame(PaymentInterface::STATE_APPROVING, $payment->getState());
        $this->assertSame(Result::STATUS_PENDING, $result->getStatus());
        $this->assertSame(PluginInterface::REASON_CODE_TIMEOUT, $result->getReasonCode());
        $this->assertSame(FinancialTransactionInterface::STATE_PENDING, $result->getFinancialTransaction()->getState());
        $this->assertTrue($result->isRecoverable());
    }
    
    /**
     * @expectedException \Exception
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
            ->will($this->throwException(new \Exception('some error occurred')))
        ;
        $controller->addPlugin($plugin);
        
        $this->callApprove($controller, array($payment, 100));
    }
    
    /**
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
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
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
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
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
     * @expectedMessage The Payment's target amount is less than the requested amount.
     */
    public function testApproveAmountCannotBeHigherThanPaymentsIfFirstTry()
    {
        $controller = $this->getController();
        
        $payment = $this->getPayment(array(), array(1 => 50));
        
        $instruction = $payment->getPaymentInstruction();
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        
        $this->callApprove($controller, array($payment, 100));
    }
    
    /**
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
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
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
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
    
    
    
    protected function getPlugin()
    {
        $plugin = $this->getMock('Bundle\PaymentBundle\Plugin\PluginInterface');
        $plugin
            ->expects($this->once())
            ->method('processes')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true))
        ;
        
        return $plugin;
    }
    
    protected function getPayment($mockMethods = array(), $arguments = array())
    {
        $arguments = $arguments + array(
            $this->getInstruction(),
            123.45
        );
        
        if (count($mockMethods) === 0) {
            return new Payment($arguments[0], $arguments[1]);
        }
        
        return $this->getMock(
        	'Bundle\PaymentBundle\Entity\Payment',
            $mockMethods,
            $arguments
        );
    }
    
    protected function getInstruction($mockMethods = array(), $arguments = array())
    {
        $arguments = $arguments + array(
            123.45,
            'EUR',
            'foo',
            new ExtendedData()
        );
        
        if (count($mockMethods) === 0) {
            return new PaymentInstruction($arguments[0], $arguments[1], $arguments[2], $arguments[3]);
        }
        
        return $this->getMock(
        	'Bundle\PaymentBundle\Entity\PaymentInstruction',
            $mockMethods, 
            $arguments
        );
    }
    
    protected function getController(array $options = array(), $addTransaction = true)
    {
        $options = array_merge(array(
            'financial_transaction_class' => 'Bundle\PaymentBundle\Entity\FinancialTransaction',
            'result_class' => 'Bundle\PaymentBundle\PluginController\Result',
        ), $options);
        
        $mock = $this->getMockForAbstractClass(
        	'Bundle\PaymentBundle\PluginController\PluginController',
            array($options)
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
}