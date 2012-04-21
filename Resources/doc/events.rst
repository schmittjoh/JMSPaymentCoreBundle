Events
======

Introduction
------------
The PluginController dispatches events for certain payment changes. This can be
used by your application to perform certain actions for example when a payment
is successful.

.. tip ::

    For a list of all available events, you can also take a look at the class
    ``JMS\Payment\CoreBundle\PluginController\Event\Events``.

Payment State Change Event
--------------------------
**Name**: ``payment.state_change``

**Event Class**: ``JMS\Payment\CoreBundle\PluginController\Event\PaymentStateChangeEvent``

This event is dispatched directly after the state of a payment changed. All 
related entities have already been updated.

You have access to the ``Payment``, the ``PaymentInstruction``, the new state, and
the old state of the payment.
