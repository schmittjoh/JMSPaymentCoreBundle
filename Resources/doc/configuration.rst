Configuration
=============

Initial Configuration
---------------------
The configuration is as easy as choosing a random secret string which we will
be using for encrypting your data if you have requested this:

.. configuration-block ::

    .. code-block :: yaml
    
        jms_payment_core:
            secret: someS3cretP4ssw0rd
            
    .. code-block :: xml
    
        <jms-payment-core secret="someS3cretP4assw0rd" />
        
.. note ::
    
    If you change the secret, then all data encrypted with the old secret 
    will become unreadable.
    
Payment Backend Configuration
-----------------------------
The different :doc:`payment backends <payment_backends>` which are provided by
additional bundles likely also require some form of configuration; please see 
their documentation.

