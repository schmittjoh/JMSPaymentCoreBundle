The Model
=========

Introduction
------------
Before we are going to see how we can conduct payments, let me 
give you a quick overlook over the model classes, and their purpose.

PaymentInstruction
------------------
A ``PaymentInstruction`` is the first object that you need to create. It contains
information such as the total amount, the payment method, the currency, and
any data that is necessary for the payment method, for example credit card
information.

.. tip ::

    Any payment related data may be automatically encrypted if you request this.

TODO: Include payment instruction state flow here

Payment
-------
Each ``PaymentInstruction`` may be splitted up into several payments. A ``Payment``
always holds an amount, and the current state of the work-flow, such as
initiated, approved, deposited, etc.

This allows you for example to request a fraction of the total amount to be
deposited before an order ships, and the rest afterwards.

TODO: Include payment state flow here

FinancialTransaction
--------------------
Each ``Payment`` may have several transactions. Each ``FinancialTransaction``
represents a specific interaction with the payment backend. In the case of
a credit card payment, this could for example be an authorization transaction.

.. note ::
    
    There may only ever be one open transaction for each ``PaymentInstruction`` 
    at a time. This is enforced, and guaranteed.

TODO: Include transaction state flow here
    