<?php

namespace Bundle\PaymentBundle\Plugin\Exception;

use Bundle\PaymentBundle\Model\CreditInterface;
use Bundle\PaymentBundle\Model\FinancialTransactionInterface;
use Bundle\PaymentBundle\Model\PaymentInstructionInterface;
use Bundle\PaymentBundle\Model\PaymentInterface;
use Bundle\PaymentBundle\Exception\Exception as PaymentBundleException;

/**
 * Base Exception for plugins
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Exception extends PaymentBundleException
{
    protected $credit;
    protected $financialTransaction;
    protected $payment;
    protected $paymentInstruction;
    
    public function getCredit()
    {
        return $this->credit;
    }
    
    public function getFinancialTransaction()
    {
        return $this->financialTransaction;
    }
    
    public function getPayment()
    {
        return $this->payment;
    }
    
    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }
    
    public function setCredit(CreditInterface $credit)
    {
        $this->credit = $credit;
    }
    
    public function setFinancialTransaction(FinancialTransactionInterface $transaction)
    {
        $this->financialTransaction = $transaction;
    }
    
    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }
    
    public function setPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $this->paymentInstruction = $instruction;
    }
}