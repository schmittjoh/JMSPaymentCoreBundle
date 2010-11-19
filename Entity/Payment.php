<?php

namespace Bundle\PaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;

class Payment implements PaymentInterface
{
    protected $approvedAmount;
    protected $approvingAmount;
    protected $depositedAmount;
    protected $depositingAmount;
    protected $expirationDate;
    protected $id;
    protected $paymentInstruction;
    protected $reversingApprovedAmount;
    protected $reversingDepositedAmount;
    protected $state;
    protected $targetAmount;
    protected $transactions;
    protected $attentionRequired;
    protected $expired;
    
    public function __construct(PaymentInstruction $paymentInstruction, $amount)
    {
        $this->approvedAmount = 0.0;
        $this->approvingAmount = 0.0;
        $this->depositedAmount = 0.0;
        $this->depositingAmount = 0.0;
        $this->paymentInstruction = $paymentInstruction;
        $this->reversingApprovedAmount = 0.0;
        $this->reversingDepositedAmount = 0.0;
        $this->state = self::STATE_NEW;
        $this->targetAmount = $amount;
        $this->transactions = new ArrayCollection;
        $this->attentionRequired = false;
        $this->expired = false;
        
        $this->paymentInstruction->addPayment($this);
    }
    
    public function addTransaction(FinancialTransaction $transaction)
    {
        $this->transactions->add($transaction);
        $transaction->setPayment($this);
    }
    
    public function getApprovedAmount()
    {
        return $this->approvedAmount;
    }
    
    public function getApproveTransaction()
    {
        foreach ($this->transactions as $transaction) {
            $type = $transaction->getTransactionType();
            
            if (FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE === $type
                || FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT === $type) {
                    
                return $transaction;
            }
        }
        
        return null;
    }
    
    public function getApprovingAmount()
    {
        return $this->approvingAmount;
    }
    
    public function getDepositedAmount()
    {
        return $this->depositedAmount;   
    }
    
    public function getDepositingAmount()
    {
        return $this->depositingAmount;
    }
    
    public function getDepositTransactions()
    {
        return $this->transactions->filter(function($transaction) {
           return FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT === $transaction->getTransactionType(); 
        });
    }
    
    public function getExpirationDate()
    {
        return $this->expirationDate;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }
    
    public function getPendingTransaction()
    {
        foreach ($this->transactions as $transaction) {
            if (FinancialTransactionInterface::STATE_PENDING === $transaction->getState()) {
                return $transaction;
            }
        }
        
        return null;
    }
    
    public function getReverseApprovalTransactions()
    {
        return $this->transactions->filter(function($transaction) {
           return FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_APPROVAL === $transaction->getTransactionType(); 
        });
    }
    
    public function getReverseDepositTransactions()
    {
        return $this->transactions->filter(function($transaction) {
           return FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_DEPOSIT === $transaction->getTransactionType(); 
        });
    }
    
    public function getReversingApprovedAmount()
    {
        return $this->reversingApprovedAmount;
    }
    
    public function getReversingDepositedAmount()
    {
        return $this->reversingDepositedAmount;
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function getTargetAmount()
    {
        return $this->targetAmount;
    }
    
    public function getTransactions()
    {
        return $this->transactions;
    }
    
    public function hasPendingTransaction()
    {
        return null !== $this->getPendingTransaction();
    }
    
    public function isAttentionRequired()
    {
        return $this->attentionRequired;
    }
    
    public function isExpired()
    {
        if (true === $this->expired) {
            return true;
        }
        
        if (null !== $this->expirationDate) {
            return $this->expirationDate->getTimestamp() < time();
        }
        
        return false;
    }
    
    public function setApprovedAmount($amount)
    {
        $this->approvedAmount = $amount;
    }
    
    public function setApprovingAmount($amount)
    {
        $this->approvingAmount = $amount;
    }
    
    public function setAttentionRequired($boolean)
    {
        $this->attentionRequired = !!$boolean;
    }
    
    public function setDepositedAmount($amount)
    {
        $this->depositedAmount = $amount;
    }
    
    public function setDepositingAmount($amount)
    {
        $this->depositingAmount = $amount;
    }
    
    public function setExpirationDate(\DateTime $date)
    {
        $this->expirationDate = $date;
    }
    
    public function setExpired($boolean)
    {
        $this->expired = !!$boolean;
    }
    
    public function setReversingApprovedAmount($amount)
    {
        $this->reversingApprovedAmount = $amount;
    }
    
    public function setReversingDepositedAmount($amount)
    {
        $this->reversingDepositedAmount = $amount;
    }
    
    public function setState($state)
    {
        $this->state = $state;
    }
}