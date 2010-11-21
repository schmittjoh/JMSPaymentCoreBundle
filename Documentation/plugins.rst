Plugins
=======

A plugin is a flexible way of providing access to a specific payment back end, 
payment processor, or payment service provider. Each plugin is a *stateless* 
service managed by Symfony's dependency injection container.

Payment plugins are extensions to the payment plugin controller (PPC). While the
PPC concerns itself with persistence, database transaction management, etc., it does 
not provide any direct connectivity with payment backend systems. This is where
plugins come in. 

A plugin may implement a list of financial transactions:

- approve (aka authorization) transactions
- deposit (aka charge) transactions
- credit (aka refund) transactions
- reversals of above transactions 


Some of these transactions might not make sense for some plugins because the respective
payment backend system simply may not provide similar capabilities. In these cases,
you still have to implement the method, but you are free to throw a ``FunctionNotSupportedException``.

The following is a list of sample plugin types, and recommended methods to implement (non exhaustive):

+----------------------------+-------------+------------------+------------------+ 
| Financial Transaction      | Credit Card | Electronic Check | Gift Certificate |
+============================+=============+==================+==================+
| checkPaymentInstruction    |      x      |         x        |         x        |
+----------------------------+-------------+------------------+------------------+
| validatePaymentInstruction |      x      |         x        |         x        |
+----------------------------+-------------+------------------+------------------+
| approveAndDeposit          |      x      |         x        |        \-        |
+----------------------------+-------------+------------------+------------------+
| approve                    |      x      |        \-        |         x        |
+----------------------------+-------------+------------------+------------------+
| reverseApproval            |      x      |        \-        |        \-        |
+----------------------------+-------------+------------------+------------------+
| deposit                    |      x      |         x        |         x        |
+----------------------------+-------------+------------------+------------------+
| reverseDeposit             |      x      |        \-        |        \-        |
+----------------------------+-------------+------------------+------------------+
| credit                     |      x      |        \-        |        \-        |
+----------------------------+-------------+------------------+------------------+
| reverseCredit              |      x      |        \-        |        \-        |
+----------------------------+-------------+------------------+------------------+


Available Exceptions
--------------------
The following lists available exceptions which can be thrown from plugins, and the
associated changes the plugin controller will perform. Of course, you can also add
your own exceptions, but it is recommend that you sub-class an existing exception when
doing so. All exceptions are in the namespace ``Bundle\PaymentBundle\Plugin\Exception``.

+------------------------------------+-----------------------------+---------------------------+
| Class                              | Description                 | Payment Plugin Controller |
|                                    |                             | Interpretation            |
+====================================+=============================+===========================+
| Exception                          | Base exception used by all  | Causes any transaction to |
|                                    | exceptions thrown from      | be rolled back. Exception |
|                                    | plugins.                    | will be re-thrown.        |
+------------------------------------+-----------------------------+---------------------------+
| FunctionNotSupportedException      | This exception is thrown    | In most cases, this causes|
|                                    | whenever a method on the    | any transactions to be    |
|                                    | interface is not supported  | rolled back. Notable      |
|                                    | by the plugin.              | exceptions to this rule:  |
|                                    |                             | checkPaymentInstruction,  |
|                                    |                             | validatePaymentInstruction|
+------------------------------------+-----------------------------+---------------------------+
| InvalidDataException               | This exception is thrown    | Causes any transaction to |
|                                    | whenever the plugin realizes| be rolled back. Exception |
|                                    | that the data associated    | will be re-thrown.        |
|                                    | with the transaction is     |                           |
|                                    | invalid.                    |                           |
+------------------------------------+-----------------------------+---------------------------+
| InvalidPaymentInstructionException | This exception is typically | Causes PaymentInstruction |
|                                    | thrown from within either   | to be set to              |
|                                    | checkPaymentInstruction, or | STATE_INVALID.            |
|                                    | validatePaymentInstruction. |                           |
+------------------------------------+-----------------------------+---------------------------+
| TimeoutException                   | This exception is thrown    | Sets the transaction to   |
|                                    | when there is an enduring   | STATE_PENDING, and        |
|                                    | communicaton problem with   | converts the exception to |
|                                    | the payment backend system. | a Result object.          |
+------------------------------------+-----------------------------+---------------------------+


Implementing a Custom Plugin
----------------------------
The easiest way is to simply extend the provided ``Plugin`` class, and override
the remaining abstract methods::

    class PaypalPlugin extends \Bundle\PaymentBundle\Plugin\Plugin
    {
        // this method is called by the plugin controller to check whether this
        // plugin can process the given payment system
        public function processes($name)
        {
            return 'paypal' === $name;
        }
        
        // for most cases, it's save to just return false here, for more info
        // you can read the credit section
        public function isIndependentCreditSupported()
        {
            return false;
        }
    }
    
That's it! You created your first plugin :) 

