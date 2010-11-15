<?php

namespace Bundle\PaymentBundle\Tests\PluginController;

use Bundle\PaymentBundle\Plugin\PluginInterface;

use Bundle\PaymentBundle\Plugin\Exception\Exception as PluginException;
use Bundle\PaymentBundle\Plugin\Exception\TimeoutException as PluginTimeoutException;
use Bundle\PaymentBundle\Entity\PaymentInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\PluginController\Result;

class PluginControllerTest extends \PHPUnit_Framework_TestCase
{
    public function testApprovePluginThrowsTimeoutException()
    {
        $controller = $this->getController();
        
        $instruction = $this->getInstruction();
        $instruction   
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInstructionInterface::STATE_VALID))
        ;
        $instruction
            ->expects($this->once())
            ->method('hasPendingTransaction')
            ->will($this->returnValue(false))
        ;
        $instruction
            ->expects($this->once())
            ->method('getApprovingAmount')
            ->will($this->returnValue(10))
        ;
        $instruction
            ->expects($this->once())
            ->method('setApprovingAmount')
            ->with($this->equalTo(110))
        ;
        $instruction
            ->expects($this->once())
            ->method('getPaymentSystemName')
            ->will($this->returnValue('foo'))
        ;
            
        $payment = $this->getPayment();
        $payment
            ->expects($this->exactly(2))
            ->method('getPaymentInstruction')
            ->will($this->returnValue($instruction))
        ;
        $payment
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInterface::STATE_NEW))
        ;
        $payment
            ->expects($this->once())
            ->method('getTargetAmount')
            ->will($this->returnValue(123))
        ;
        $payment
            ->expects($this->once())
            ->method('setApprovingAmount')
            ->with($this->equalTo(100))
        ;
        $payment
            ->expects($this->once())
            ->method('setState')
            ->with(PaymentInterface::STATE_APPROVING)
        ;
            
        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
            ->will($this->throwException(new PluginTimeoutException('some error occurred')))
        ;
        $plugin
            ->expects($this->once())
            ->method('processes')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true))
        ;
        $controller->addPlugin($plugin);
        
        $controller
            ->expects($this->once())
            ->method('getPayment')
            ->with(123)
            ->will($this->returnValue($payment))
        ;
        
        $result = $controller->approve(123, 100);
        $transaction = $result->getFinancialTransaction();
        
        $this->assertInstanceOf('Bundle\PaymentBundle\PluginController\Result', $result);
        $this->assertInstanceOf('Bundle\PaymentBundle\Entity\FinancialTransaction', $transaction);
        $this->assertInstanceOf('Bundle\PaymentBundle\Plugin\Exception\Exception', $result->getPluginException());
        $this->assertEquals(PluginInterface::REASON_CODE_TIMEOUT, $result->getReasonCode());
        $this->assertSame($payment, $result->getPayment());
        $this->assertSame($instruction, $result->getPaymentInstruction());
        $this->assertTrue($result->isRecoverable());
        $this->assertEquals(Result::STATUS_PENDING, $result->getStatus());
    }
    
    public function testApprovePluginThrowsException()
    {
        $controller = $this->getController();
        
        $instruction = $this->getInstruction();
        $instruction   
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInstructionInterface::STATE_VALID))
        ;
        $instruction
            ->expects($this->once())
            ->method('hasPendingTransaction')
            ->will($this->returnValue(false))
        ;
        $instruction
            ->expects($this->once())
            ->method('getApprovingAmount')
            ->will($this->returnValue(10))
        ;
        $instruction
            ->expects($this->once())
            ->method('setApprovingAmount')
            ->with($this->equalTo(110))
        ;
        $instruction
            ->expects($this->once())
            ->method('getPaymentSystemName')
            ->will($this->returnValue('foo'))
        ;
            
        $payment = $this->getPayment();
        $payment
            ->expects($this->exactly(2))
            ->method('getPaymentInstruction')
            ->will($this->returnValue($instruction))
        ;
        $payment
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInterface::STATE_NEW))
        ;
        $payment
            ->expects($this->once())
            ->method('getTargetAmount')
            ->will($this->returnValue(123))
        ;
        $payment
            ->expects($this->once())
            ->method('setApprovingAmount')
            ->with($this->equalTo(100))
        ;
        $payment
            ->expects($this->once())
            ->method('setState')
            ->with(PaymentInterface::STATE_APPROVING)
        ;
            
        $plugin = $this->getPlugin();
        $plugin
            ->expects($this->once())
            ->method('approve')
            ->will($this->throwException(new PluginException('some error occurred')))
        ;
        $plugin
            ->expects($this->once())
            ->method('processes')
            ->with($this->equalTo('foo'))
            ->will($this->returnValue(true))
        ;
        $controller->addPlugin($plugin);
        
        $controller
            ->expects($this->once())
            ->method('getPayment')
            ->with(123)
            ->will($this->returnValue($payment))
        ;
        
        $result = $controller->approve(123, 100);
        $transaction = $result->getFinancialTransaction();
        
        $this->assertInstanceOf('Bundle\PaymentBundle\PluginController\Result', $result);
        $this->assertInstanceOf('Bundle\PaymentBundle\Entity\FinancialTransaction', $transaction);
        $this->assertInstanceOf('Bundle\PaymentBundle\Plugin\Exception\Exception', $result->getPluginException());
        $this->assertEquals($transaction->getReasonCode(), $result->getReasonCode());
        $this->assertSame($payment, $result->getPayment());
        $this->assertSame($instruction, $result->getPaymentInstruction());
        $this->assertTrue($result->isPaymentRequiresAttention());
        $this->assertEquals(Result::STATUS_UNKNOWN, $result->getStatus());
    }
    
    /**
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
     * @expectedMessage The PaymentInstruction can only ever have one pending transaction.
     */
    public function testApproveDoesNotAcceptNewTransactionIfInstructionHasPendingTransaction()
    {
        $controller = $this->getController();
        
        $instruction = $this->getInstruction();
        $instruction   
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInstructionInterface::STATE_VALID))
        ;
        $instruction
            ->expects($this->once())
            ->method('hasPendingTransaction')
            ->will($this->returnValue(true))
        ;
            
        $payment = $this->getPayment();
        $payment
            ->expects($this->once())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($instruction))
        ;
        $payment
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInterface::STATE_NEW))
        ;
        $payment
            ->expects($this->once())
            ->method('getTargetAmount')
            ->will($this->returnValue(1234))
        ;
            
        $controller
            ->expects($this->once())
            ->method('getPayment')
            ->with(123)
            ->will($this->returnValue($payment))
        ;
        
        $controller->approve(123, 100);
    }
    
    /**
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
     * @expectedMessage The Payment's target amount must equal the requested amount in a retry transaction.
     * @dataProvider getApprovalTestAmounts
     */
    public function testApproveAmountMustEqualPaymentsIfRetry($amount)
    {
        $controller = $this->getController();
        
        $instruction = $this->getInstruction();
        $instruction   
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInstructionInterface::STATE_VALID))
        ;
            
        $payment = $this->getPayment();
        $payment
            ->expects($this->once())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($instruction))
        ;
        $payment
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInterface::STATE_APPROVING))
        ;
        $payment
            ->expects($this->once())
            ->method('getTargetAmount')
            ->will($this->returnValue($amount))
        ;
            
        $controller
            ->expects($this->once())
            ->method('getPayment')
            ->with(123)
            ->will($this->returnValue($payment))
        ;
        
        $controller->approve(123, 100);
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
        
        $instruction = $this->getInstruction();
        $instruction   
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInstructionInterface::STATE_VALID))
        ;
            
        $payment = $this->getPayment();
        $payment
            ->expects($this->once())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($instruction))
        ;
        $payment
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInterface::STATE_NEW))
        ;
        $payment
            ->expects($this->once())
            ->method('getTargetAmount')
            ->will($this->returnValue(50))
        ;
            
        $controller
            ->expects($this->once())
            ->method('getPayment')
            ->with(123)
            ->will($this->returnValue($payment))
        ;
        
        $controller->approve(123, 100);
    }
    
    /**
     * @expectedException Bundle\PaymentBundle\PluginController\Exception\Exception
     * @expectedMessage The Payment's state must be STATE_NEW, or STATE_PENDING.
     * @dataProvider getInvalidPaymentStatesForApproval
     */
    public function testApprovePaymentMustHaveValidState($invalidState)
    {
        $controller = $this->getController();
        
        $instruction = $this->getInstruction();
        $instruction   
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue(PaymentInstructionInterface::STATE_VALID))
        ;
            
        $payment = $this->getPayment();
        $payment
            ->expects($this->once())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($instruction))
        ;
        $payment
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue($invalidState))
        ;
            
        $controller
            ->expects($this->once())
            ->method('getPayment')
            ->with(123)
            ->will($this->returnValue($payment))
        ;
        
        $controller->approve(123, 1);
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
        
        $instruction = $this->getInstruction();
        $instruction   
            ->expects($this->once())
            ->method('getState')
            ->will($this->returnValue($invalidState))
        ;
            
        $payment = $this->getPayment();
        $payment
            ->expects($this->once())
            ->method('getPaymentInstruction')
            ->will($this->returnValue($instruction))
        ;
        
        $controller
            ->expects($this->once())
            ->method('getPayment')
            ->with(123)
            ->will($this->returnValue($payment))
        ;
        
        $controller->approve(123, 1);
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
        return $this->getMock('Bundle\PaymentBundle\Plugin\PluginInterface');
    }
    
    protected function getPayment()
    {
        return $this->getMock('Bundle\PaymentBundle\Entity\PaymentInterface');
    }
    
    protected function getInstruction()
    {
        return $this->getMock('Bundle\PaymentBundle\Entity\PaymentInstructionInterface');
    }
    
    protected function getController(array $options = array())
    {
        $options = array_merge(array(
            'financial_transaction_class' => 'Bundle\PaymentBundle\Entity\FinancialTransaction',
            'result_class' => 'Bundle\PaymentBundle\PluginController\Result',
        ), $options);
        
        return $this->getMockForAbstractClass(
        	'Bundle\PaymentBundle\PluginController\PluginController',
            array($options)
        );
    }
}