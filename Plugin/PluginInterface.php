<?php

namespace Bundle\PaymentBundle\Plugin;

interface PluginInterface
{
    function approve(FinancialTransactionInterface $transaction, $retry);
    function approveAndDeposit(FinancialTransactionInterface $transaction, $retry);
    function checkPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function credit(FinancialTransactionInterface $transaction, $retry);
    function deposit(FinancialTransactionInterface $transaction, $retry);
    function reverseCredit(FinancialTransactionInterface $transaction, $retry);
    function reverseDeposit(FinancialTransactionInterface $transaction, $retry);
    function validatePaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function getName();
    function isIndependentCreditSupported();
}