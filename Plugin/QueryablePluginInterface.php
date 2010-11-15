<?php

namespace Bundle\PaymentBundle\Plugin;

interface QueryablePluginInterface extends PluginInterface
{
    /**
     * This method gets the available balance for the specified 
     * PaymentInstruction account.
     * 
     * @param PaymentInstructionInterface $paymentInstruction
     * @return float|null Returns the amount that may be consumed by the payment, or null of it cannot be determined
     */
    function getAvailableBalance(PaymentInstructionInterface $paymentInstruction);
    function getCredit(CreditInterface $credit);
    function getPayment(PaymentInterface $payment);
}