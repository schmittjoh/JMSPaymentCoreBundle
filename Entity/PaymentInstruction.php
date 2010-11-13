<?php

namespace Bundle\PaymentBundle\Entity\PaymentInstruction;

use Doctrine\Common\Collections\ArrayCollection;

class PaymentInstruction implements PaymentInstructionInterface
{
    protected $id;
    protected $amount;
    protected $currency;
    protected $paymentSystemName;
    protected $extendedData;
    protected $state;
    protected $credits;
    protected $payments;
    protected $createdAt;
    protected $updatedAt;
    
    public function __construct($amount, $currency, $paymentSystemName, ExtendedDataInterface $data = null)
    {
        $this->amount = $amount;
        $this->currency = $currency;
        $this->paymentSystemName = $paymentSystemName;
        $this->extendedData = $data;
        $this->state = self::STATE_NEW;
        $this->credits = new ArrayCollection();
        $this->payments = new ArrayCollection();
        $this->createdAt = new \Date();
    }
    
    public function getAmount()
    {
        return $this->amount;
    }
    
    public function getCurrency()
    {
        return $this->currency;
    }
    
    public function getPaymentSystemName()
    {
        return $this->paymentSystemName;
    }
    
    public function getExtendedData()
    {
        return $this->extendedData;
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getApprovingAmount()
    {
        $amount = 0.0;
        foreach ($this->payments as $payment) {
            $amount += $payment->getApprovingAmount();
        }
        
        return $amount;
    }
    
    public function getApprovedAmount()
    {
        $amount = 0.0;
        foreach ($this->payments as $payment) {
            $amount += $payment->getApprovedAmount();
        }
        
        return $amount;
    }
    
    public function getCreditedAmount()
    {
        $amount = 0.0;
        foreach ($this->credits as $credit) {
            $amount += $credit->getCreditedAmount();
        }
        
        return $amount;
    }
    
    public function getCreditingAmount()
    {
        $amount = 0.0;
        foreach ($this->credits as $credit) {
            $amount += $credit->getCreditingAmount();
        }
        
        return $amount;
    }
    
    public function getDepositedAmount()
    {
        $amount = 0.0;
        foreach ($this->payments as $payment) {
            $amount += $payment->getDepositedAmount();
        }
        
        return $amount;
    }
    
    public function getDepositingAmount()
    {
        $amount = 0.0;
        foreach ($this->payments as $payment) {
            $amount += $payment->getDepositingAmount();
        }
        
        return $amount;
    }
    
    public function getCredits()
    {
        return $this->credits;
    }
    
    public function getPayments()
    {
        return $this->payments;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}