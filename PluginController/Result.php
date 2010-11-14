<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\Entity\FinancialTransactionInterface;
use Bundle\PaymentBundle\Plugin\Exception\Exception as PluginException;

class Result
{
    const STATUS_FAILED = 1;
    const STATUS_PENDING = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_UNKNOWN = 4;
    
    protected $transaction;
    protected $status;
    protected $reasonCode;
    protected $pluginException;
    protected $paymentRequiresAttention;
    protected $recoverable;
    
    public function __construct(FinacialTransactionInterface $transaction, $status, $reasonCode)
    {
        $this->transaction = $transaction;
        $this->status = $status;
        $this->reasonCode = $reasonCode;
        $this->paymentRequiresAttention = false;
        $this->recoverable = false;
    }
    
    public function getPluginException()
    {
        return $this->pluginException;
    }
    
    public function getFinancialTransaction()
    {
        return $this->transaction;
    }
    
    public function getStatus()
    {
        return $this->status;
    }
    
    public function getReasonCode()
    {
        return $this->reasonCode;
    }
    
    public function getCredit()
    {
        return $this->transaction->getCredit();
    }
    
    public function getPayment()
    {
        return $this->transaction->getPayment();
    }
    
    public function getPaymentInstruction()
    {
        $type = $this->transaction->getTransactionType();
        
        if (FinancialTransactionInterface::TRANSACTION_TYPE_CREDIT === $type
            || FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_CREDIT === $type) {

            return $this->transaction->getCredit()->getPaymentInstruction();
        }
        else {
            return $this->transaction->getPayment()->getPaymentInstruction();
        }
    }
    
    public function isPaymentRequiresAttention()
    {
        return $this->paymentRequiresAttention;
    }
    
    public function isRecoverable()
    {
        return $this->recoverable;
    }
    
    public function setPaymentRequiresAttention($boolean = true)
    {
        $this->paymentRequiresAttention = !!$boolean;
    }
    
    public function setPluginException(PluginException $exception)
    {
        $this->pluginException = $exception;
    }
    
    public function setRecoverable($boolean = true) 
    {
        $this->recoverable = !!$boolean;
    }
}