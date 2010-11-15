<?php

namespace Bundle\PaymentBundle\Plugin;

use Bundle\PaymentBundle\Entity\FinancialTransactionInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;

interface PluginInterface
{
    const RESPONSE_CODE_SUCCESS = 'success';
    const REASON_CODE_SUCCESS = 'none';
    const REASON_CODE_TIMEOUT = 'timeout';
    const REASON_CODE_INVALID = 'invalid';
    
    function approve(FinancialTransactionInterface $transaction, $retry);
    function approveAndDeposit(FinancialTransactionInterface $transaction, $retry);
    
    /**
     * This method checks whether all required parameters exist in the given
     * PaymentInstruction, and whether they are syntactically correct.
     * 
     * This method is meant to perform a fast parameter validation; no connection
     * to any payment back-end system should be made at this stage.
     *     
     * In case, this method is not implemented. The PaymentInstruction will
     * be considered to be valid.
     *     
     * @param PaymentInstructionInterface $paymentInstruction
     * @throws Bundle\PaymentBundle\Plugin\Exception\InvalidPaymentInstructionException if the the PaymentInstruction is not valid
     * @return void
     */
    function checkPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function credit(FinancialTransactionInterface $transaction, $retry);
    function deposit(FinancialTransactionInterface $transaction, $retry);
    function reverseApproval(FinancialTransactionInterface $transaction, $retry);
    function reverseCredit(FinancialTransactionInterface $transaction, $retry);
    function reverseDeposit(FinancialTransactionInterface $transaction, $retry);
    function validatePaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    
    /**
     * Whether this plugin can process payments for the given payment system. 
     * 
     * A plugin may support multiple payment systems. In these cases, the requested 
     * payment system for a specific transaction  can be determined by looking at 
     * the PaymentInstruction which will always be accessible either directly, or 
     * indirectly.
     * 
     * @param string $paymentSystemName
     * @return boolean
     */
    function processes($paymentSystemName);
    
    /**
     * Whether independent credit is supported by this plugin.
     * 
     * Dependent Credit: The Credit depends on the existence of a Payment, and
     * the Credit's amount must not be greater than the deposited amount of the
     * related Payment.
     * 
     * Independent Credit: The Credit does not depend on a Payment, but can be
     * awarded "independently" to a PaymentInstruction. The amount is not restricted
     * by any deposited amount.
     * 
     * @return boolean
     */
    function isIndependentCreditSupported();
}