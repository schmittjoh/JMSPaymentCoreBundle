<?php

namespace Bundle\PaymentBundle\Plugin;

interface QueryablePluginInterface extends PluginInterface
{
    function getAvailableBalance(PluginContextInterface $context, PaymentInstructionInterface $paymentInstruction);
    function getCredit(PluginContextInterface $context, CreditInterface $credit);
    function getPayment(PluginContextInterface $context, PaymentInterface $payment);
}