<?php

namespace Bundle\PaymentBundle\Entity;

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
    protected $approvingAmount;
    protected $approvedAmount;
    protected $creditingAmount;
    protected $creditedAmount;
    protected $depositedAmount;
    protected $depositingAmount;
    
    public function __construct($amount, $currency, $paymentSystemName, ExtendedData $data = null)
    {
        $this->amount = $amount;
        $this->approvedAmount = 0.0;
        $this->approvingAmount = 0.0;
        $this->createdAt = new \DateTime;
        $this->creditedAmount = 0.0;
        $this->creditingAmount = 0.0;
        $this->credits = new ArrayCollection();
        $this->currency = $currency;
        $this->depositingAmount = 0.0;
        $this->depositedAmount = 0.0;
        $this->extendedData = $data;
        $this->payments = new ArrayCollection();
        $this->paymentSystemName = $paymentSystemName;
        $this->state = self::STATE_NEW;
    }
    
    public function addPayment(PaymentInterface $payment)
    {
        $this->payments->add($payment);
        $payment->setPaymentInstruction($this);
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
        return $this->approvingAmount;
    }
    
    public function getApprovedAmount()
    {
        return $this->approvedAmount;
    }
    
    public function getCreditedAmount()
    {
        return $this->creditedAmount;
    }
    
    public function getCreditingAmount()
    {
        return $this->creditingAmount;
    }
    
    public function getDepositedAmount()
    {
        return $this->depositedAmount;
    }
    
    public function getDepositingAmount()
    {
        return $this->depositingAmount;
    }
    
    public function getCredits()
    {
        return $this->credits;
    }
    
    public function getPayments()
    {
        return $this->payments;
    }
    
    public function getPendingTransaction()
    {
        foreach ($this->payments as $payment) {
            if (null !== $transaction = $payment->getPendingTransaction()) {
                return $transaction;
            }
        }
        
        foreach ($this->credits as $credit) {
            if (null !== $transaction = $credit->getPendingTransaction()) {
                return $transaction;
            }
        }
        
        return null;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    
    public function hasPendingTransaction()
    {
        return null !== $this->getPendingTransaction();
    }
    
    public function setApprovingAmount($amount) 
    {
        $this->approvingAmount = $amount;
    }
    
    public function setApprovedAmount($amount)
    {
        $this->approvedAmount = $amount;
    }
    
    public function setCreditedAmount($amount)
    {
        $this->creditedAmount = $amount;
    }
    
    public function setCreditingAmount($amount)
    {
        $this->creditingAmount = $amount;
    }
    
    public function setDepositedAmount($amount)
    {
        $this->depositedAmount = $amount;
    }
    
    public function setDepositingAmount($amount)
    {
        $this->depositingAmount = $amount;
    }
}