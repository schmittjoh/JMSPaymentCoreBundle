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

