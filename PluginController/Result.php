<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\Entity\FinancialTransactionInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\Plugin\Exception\Exception as PluginException;

class Result
{
    const STATUS_FAILED = 1;
    const STATUS_PENDING = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_UNKNOWN = 4;
    
    protected $credit;
    protected $financialTransaction;
    protected $payment;
    protected $paymentInstruction;
    protected $paymentRequiresAttention;
    protected $pluginException;
    protected $reasonCode;
    protected $recoverable;
    protected $status;
    
    public function __construct()
    {
        $args = func_get_args();
        $nbArgs = count($args);
        
        if (3 === $nbArgs && $args[0] instanceof FinancialTransactionInterface) {
            $this->constructFinancialTransactionResult($args[0], $args[1], $args[2]);
        }
        else if (3 === $nbArgs && $args[0] instanceof PaymentInstructionInterface) {
            $this->constructPaymentInstructionResult($args[0], $args[1], $args[2]);
        }
        else {
            throw new \InvalidArgumentException('The given arguments are not supported.');
        }
    }
    
    public function getPluginException()
    {
        return $this->pluginException;
    }
    
    public function getFinancialTransaction()
    {
        return $this->financialTransaction;
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
        return $this->credit;
    }
    
    public function getPayment()
    {
        return $this->payment;
    }
    
    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }
    
    public function isPaymentRequiresAttention()
    {
        if (null === $this->payment) {
            throw new \LogicException('The result contains no payment.');
        }
        
        return $this->payment->isAttentionRequired();
    }
    
    public function isRecoverable()
    {
        return $this->recoverable;
    }
    
    public function setPluginException(PluginException $exception)
    {
        $this->pluginException = $exception;
    }
    
    public function setRecoverable($boolean = true) 
    {
        $this->recoverable = !!$boolean;
    }

    protected function constructFinancialTransactionResult(FinancialTransactionInterface $transaction, $status, $reasonCode)
    {
        $this->financialTransaction = $transaction;
        $this->credit = $transaction->getCredit();
        $this->payment = $transaction->getPayment();
        $this->paymentInstruction = null !== $this->credit ? $this->credit->getPaymentInstruction() : $this->payment->getPaymentInstruction();
        $this->status = $status;
        $this->reasonCode = $reasonCode;
        $this->paymentRequiresAttention = false;
        $this->recoverable = false;
    }
    
    protected function constructPaymentInstructionResult(PaymentInstructionInterface $instruction, $status, $reasonCode)
    {
        $this->paymentInstruction = $instruction;
        $this->status = $status;
        $this->reasonCode = $reasonCode;
        $this->paymentRequiresAttention = false;
        $this->recoverable = false;
    }
}