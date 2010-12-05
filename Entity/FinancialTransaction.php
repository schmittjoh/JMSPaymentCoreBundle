<?php

namespace Bundle\PaymentBundle\Entity;

use Bundle\PaymentBundle\Model\CreditInterface;
use Bundle\PaymentBundle\Model\ExtendedDataInterface;
use Bundle\PaymentBundle\Model\FinancialTransactionInterface;
use Bundle\PaymentBundle\Model\PaymentInterface;

class FinancialTransaction implements FinancialTransactionInterface
{
    protected $credit;
    protected $extendedData;
    protected $id;
    protected $payment;
    protected $processedAmount;
    protected $reasonCode;
    protected $referenceNumber;
    protected $requestedAmount;
    protected $responseCode;
    protected $state;
    protected $createdAt;
    protected $updatedAt;
    protected $trackingId;
    protected $transactionType;
    
    public function __construct()
    {
        $this->state = self::STATE_NEW;
        $this->createdAt = new \DateTime();
        $this->processedAmount = 0.0;
        $this->requestedAmount = 0.0;
    }
    
    public function getCredit()
    {
        return $this->credit;
    }
    
    public function getExtendedData()
    {
        if (null !== $this->extendedData) {
            return $this->extendedData;
        }
        
        if (null !== $this->payment) {
            return $this->payment->getPaymentInstruction()->getExtendedData();
        }
        else {
            return $this->credit->getPaymentInstruction()->getExtendedData();
        }
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getPayment()
    {
        return $this->payment;
    }
    
    public function getProcessedAmount()
    {
        return $this->processedAmount;
    }
    
    public function getReasonCode()
    {
        return $this->reasonCode;
    }
    
    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }
    
    public function getRequestedAmount()
    {
        return $this->requestedAmount;
    }
    
    public function getResponseCode()
    {
        return $this->responseCode;
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function getCreatedAt()
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
    
    public function getTrackingId()
    {
        return $this->trackingId;
    }
    
    public function getTransactionType()
    {
        return $this->transactionType;
    }
    
    public function onPrePersist()
    {
        if (null !== $this->id) {
            $this->updatedAt = new \DateTime;
        }
    }
    
    public function setCredit(CreditInterface $credit)
    {
        $this->credit = $credit;
    }
    
    public function setExtendedData(ExtendedDataInterface $data)
    {
        $this->extendedData = $data;
    }
    
    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }
    
    public function setProcessedAmount($amount)
    {
        $this->processedAmount = $amount;
    }
    
    public function setReasonCode($code)
    {
        $this->reasonCode = $code;
    }
    
    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }
    
    public function setRequestedAmount($amount)
    {
        $this->requestedAmount = $amount;
    }
    
    public function setResponseCode($code)
    {
        $this->responseCode = $code;
    }
    
    public function setState($state)
    {
        $this->state = $state;
    }
    
    public function setTrackingId($id)
    {
        $this->trackingId = $id;
    }
    
    public function setTransactionType($type)
    {
        $this->transactionType = $type;
    }
}