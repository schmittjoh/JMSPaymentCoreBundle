<?php

namespace Bundle\PaymentBundle\Tests\Entity;

use Bundle\PaymentBundle\Entity\Payment;
use Bundle\PaymentBundle\Entity\FinancialTransaction;
use Bundle\PaymentBundle\Entity\PaymentInstruction;
use Bundle\PaymentBundle\Entity\ExtendedData;

class PaymentInstructionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorDoesNotRequireExtendedData()
    {
        $instruction = new PaymentInstruction(123.12, 'EUR', 'foosystem');
        
        $this->assertEquals(123.12, $instruction->getAmount());
        $this->assertEquals('EUR', $instruction->getCurrency());
        $this->assertEquals('foosystem', $instruction->getPaymentSystemName());
        $this->assertNull($instruction->getExtendedData());
        $this->assertSame(FinancialTransaction::STATE_NEW, $instruction->getState());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $instruction->getCredits());
        $this->assertinstanceOf('Doctrine\Common\Collections\ArrayCollection', $instruction->getPayments());
        $this->assertEquals(0.0, $instruction->getApprovingAmount());
        $this->assertEquals(0.0, $instruction->getApprovedAmount());
        $this->assertEquals(0.0, $instruction->getDepositingAmount());
        $this->assertEquals(0.0, $instruction->getDepositedAmount());
        $this->assertEquals(0.0, $instruction->getCreditingAmount());
        $this->assertEquals(0.0, $instruction->getCreditedAmount());
        $this->assertNull($instruction->getId());
        $this->assertTrue(time() - $instruction->getCreatedAt()->getTimestamp() < 10);
        $this->assertNull($instruction->getUpdatedAt());
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
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $instruction->getCredits());
        $this->assertInstanceOf('Doctrine\Common\Collections\ArrayCollection', $instruction->getPayments());
        $this->assertEquals(0.0, $instruction->getApprovingAmount());
        $this->assertEquals(0.0, $instruction->getApprovedAmount());
        $this->assertEquals(0.0, $instruction->getDepositingAmount());
        $this->assertEquals(0.0, $instruction->getDepositedAmount());
        $this->assertEquals(0.0, $instruction->getCreditingAmount());
        $this->assertEquals(0.0, $instruction->getCreditedAmount());
        $this->assertNull($instruction->getId());
        $this->assertTrue(time() - $instruction->getCreatedAt()->getTimestamp() < 10);
        $this->assertNull($instruction->getUpdatedAt());
    }
    
    public function testAddPayment()
    {
        $instruction = $this->getInstruction();
        $payment = new Payment();
        
        $this->assertEquals(0, count($instruction->getPayments()));
        
        $instruction->addPayment($payment);
        
        $this->assertEquals(1, count($instruction->getPayments()));
        $this->assertSame($payment, $instruction->getPayments()->get(0));
        $this->assertSame($payment->getPaymentInstruction(), $instruction);
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
        );
    }
    
    protected function getInstruction()
    {
        return new PaymentInstruction(123.45, 'EUR', 'foo');
    }
}