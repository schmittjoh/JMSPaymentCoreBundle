<?php

namespace Bundle\PaymentBundle\PluginController;

interface PluginControllerInterface
{
    function approve($paymentId, $amount);
    function approveAndDeposit($paymentId, $amount);
    function checkPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function closePaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function createDependentCredit($paymentId, $amount);
    function createIndependentCredit($paymentInstructionId, $amount);
    function createPayment($paymentInstructionId, $amount);
    function createPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function credit($creditId, $amount);
    function deletePaymentInstruction($paymentInstructionId);
    function deposit($paymentId, $amount);
    function editCredit(CreditInterface $credit, $processAmount, $reasonCode, $responseCode, $referenceNumber, ExtendedDataInterface $data);
    function editPayment(PaymentInterface $payment, $processAmount, $reasonCode, $responseCode, $referenceNumber, ExtendedDataInterface $data);
    function editPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function getCredit($creditId);
    function getPayment($paymentId);
    function getPaymentInstruction($paymentInstructionId, $maskSensitiveData = true);
    function getRemainingValueOnPaymentInstruction(PaymentInstruction $paymentInstruction);
    function reverseApproval($paymentId, $amount);
    function reverseCredit($creditId, $amount);
    function reverseDeposit($paymentId, $amount);
    function validatePaymentInstruction(PaymentInstructionInterface $paymentInstruction);
}