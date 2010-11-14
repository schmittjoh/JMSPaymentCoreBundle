<?php

namespace Bundle\PaymentBundle\Tests\PluginController;

use Bundle\PaymentBundle\Entity\PaymentInterface;

use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;

class PluginControllerTest extends \PHPUnit_Framework_TestCase
{
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
        return $this->getMockForAbstractClass(
        	'Bundle\PaymentBundle\PluginController\PluginController',
            array($options)
        );
    }
}