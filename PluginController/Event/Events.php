<?php

namespace JMS\Payment\CoreBundle\PluginController\Event;

abstract class Events
{
    /**
     * This event is dispatched after the state of a payment instruction changes.
     */
    const PAYMENT_INSTRUCTION_STATE_CHANGE = 'payment_instruction.state_change';

    /**
     * This event is dispatched after the state of a payment changes. All
     * related entities like PaymentInstruction have already been updated.
     */
    const PAYMENT_STATE_CHANGE = 'payment.state_change';

    final private function __construct()
    {
    }
}
