Encryption
==========

- What is encrypted
- Migrating from mcrypt to defuse


Enabling encryption
-------------------
The only thing you need to do to enable encryption is to configure an encryption key. You can generate a key with the following command:

.. code-block :: bash

    bin/console jms_payment_core:generate-key

And then use it in your configuration:

.. code-block :: yaml

    # app/config/config.yml
    jms_payment_core:
        encryption:
            secret: output_of_above_command

.. warning ::

    If you change ``encryption.secret`` and/or ``encryption.provider``, all encrypted data will become unreadable. See :ref:`encryption-reencrypt` for instructions on how to properly change the encryption key or provider.

Selectively encrypting data
---------------------------
TODO - Usage (form)

Using a custom encryption provider
----------------------------------
TODO (Not recommended)

.. _encryption-reencrypt:

Re-encrypting data
------------------
Coming soon
