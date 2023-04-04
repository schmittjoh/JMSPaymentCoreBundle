Events
======

The PluginController dispatches events for certain payment changes. This can be used by your application to perform certain actions, for example, when a payment is successful.

Take a look at `Symfony's documentation <http://symfony.com/doc/current/event_dispatcher.html>`_ for information on how to listen to events.

PaymentInstruction State Change Event
-------------------------------------
**Name**: ``payment_instruction.state_change``

**Class**: ``JMS\Payment\CoreBundle\PluginController\Event\PaymentInstructionStateChangeEvent``

This event is dispatched after the state of a payment instruction changes.

You have access to the ``PaymentInstruction``, the new state and the old state of the payment instruction.


Payment State Change Event
--------------------------
**Name**: ``payment.state_change``

**Class**: ``JMS\Payment\CoreBundle\PluginController\Event\PaymentStateChangeEvent``

This event is dispatched directly after the state of a payment changed. All related entities have already been updated.

You have access to the ``Payment``, the ``PaymentInstruction``, the new state and the old state of the payment.
