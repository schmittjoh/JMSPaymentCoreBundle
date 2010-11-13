<?php

namespace Bundle\PaymentBundle\Plugin;

interface PluginInterface
{
    function approve(PluginContextInterface $context, FinancialTransactionInterface $transaction, $retry);
    function approveAndDeposit(PluginContextInterface $context, FinancialTransactionInterface $transaction, $retry);
    function checkPaymentInstruction(PluginContextInterface $context, PaymentInstructionInterface $paymentInstruction);
    function credit(PluginContextInterface $context, FinancialTransactionInterface $transaction, $retry);
    function deposit(PluginContextInterface $context, FinancialTransactionInterface $transaction, $retry);
    function reverseCredit(PluginContextInterface $context, FinancialTransactionInterface $transaction, $retry);
    function reverseDeposit(PluginContextInterface $context, FinancialTransactionInterface $transaction, $retry);
    function validatePaymentInstruction(PluginContextInterface $context, PaymentInstructionInterface $paymentInstruction);
}