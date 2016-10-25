JMSPaymentCoreBundle
====================
A unified API for processing payments with Symfony

Introduction
-------------
This bundle provides the foundation for different payment backends. It abstracts away the differences between payment protocols, and offers a simple, and unified API for performing financial transactions.

Features:

- Simple, unified API (integrate once and use any payment provider)
- Persistence of financial entities (such as payments, transactions, etc.)
- Transaction management including retry logic
- Encryption of sensitive data
- Support for :doc:`many <backends>` payment backends out of the box
- Easily support other payment backends

Getting started
---------------
Once you followed the :doc:`setup` instructions, if you have no prior experience with this bundle or payment processing in general, you should follow the :doc:`guides/accepting_payments` guide.

Once you grasp how this bundle works, take a look at the :doc:`payment_form` chapter to learn how to customize the form.

License
-------
- Code: `Apache2 <http://www.apache.org/licenses/LICENSE-2.0.html>`_
- Docs: `CC BY-NC-ND 3.0 <http://creativecommons.org/licenses/by-nc-nd/3.0/>`_

.. toctree ::
    :hidden:
    :maxdepth: 1

    setup
    payment_form
    events
    plugins
    model
    backends

.. toctree::
   :hidden:
   :caption: Guides
   :maxdepth: 1
   :glob:

   guides/*
