JMSPaymentCoreBundle
====================

Introduction
------------
JMSPaymentCoreBundle provides the foundation for different payment backends.
It abstracts away the differences between payment protocols, and offers a
simple, and unified API for performing financial transactions.

Key Points:

- Simple, Unified API (integrate once, and use any payment provider)
- Persistence of Financial Entities (such as payments, transactions, etc.)
- Transaction Management including Retry Logic
- Encyption of Sensitive Data

Documentation
-------------

.. toctree ::
    :hidden:
    
    installation
    configuration
    model
    usage
    events
    plugins
    payment_backends

- :doc:`Installation <installation>`
- :doc:`Configuration <configuration>`
- :doc:`The Model <model>`
- :doc:`Usage <usage>`
- :doc:`Available Payment Backends <payment_backends>`

License
-------

The code is released under the business-friendly `Apache2 license`_. 

Documentation is subject to the `Attribution-NonCommercial-NoDerivs 3.0 Unported
license`_.

.. _Apache2 license: http://www.apache.org/licenses/LICENSE-2.0.html
.. _Attribution-NonCommercial-NoDerivs 3.0 Unported license: http://creativecommons.org/licenses/by-nc-nd/3.0/

