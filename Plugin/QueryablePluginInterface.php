<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin;

use Bundle\JMS\Payment\CorePaymentBundle\Model\CreditInterface;
use Bundle\JMS\Payment\CorePaymentBundle\Model\PaymentInstructionInterface;
use Bundle\JMS\Payment\CorePaymentBundle\Model\PaymentInterface;

/**
 * This interface can be implemented in addition to PluginInterface
 * if the plugin supports real-time queries.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface QueryablePluginInterface
{
    /**
     * This method gets the available balance for the specified 
     * PaymentInstruction account.
     * 
     * @param PaymentInstructionInterface $paymentInstruction
     * @return float|null Returns the amount that may be consumed by the payment, or null of it cannot be determined
     */
    function getAvailableBalance(PaymentInstructionInterface $paymentInstruction);
    
    /**
     * This method updates the given Credit object with data from the
     * payment backend system.
     * 
     * @param CreditInterface $credit
     * @return void
     */
    function updateCredit(CreditInterface $credit);
    
    /**
     * This method updates the given Payment object with data from the 
     * payment backend system.
     * 
     * @param PaymentInterface $payment
     * @return void
     */
    function updatePayment(PaymentInterface $payment);
}