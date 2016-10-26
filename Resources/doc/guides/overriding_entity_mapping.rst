Overriding entity mapping
=========================

By default, this bundle sets the type of database columns which store amounts to ``decimal`` with the ``precision`` set to ``10`` and ``scale`` set to ``5``. This means that the greatest amount you are able to process is ``99999.99999``.

In case you need to accept payments of greater value, it's possible to override the entity mapping supplied by this bundle and use a custom one. Keep reading for instructions on how to do this.

.. note::

    In a future major release, amounts will be stored as strings, thus removing this limitation.

Copying the mapping files
-------------------------
Start by copying the mapping files from this bundle to your application:

.. code-block :: shell

    cd my-app
    mkdir -p app/Resources/config/JMSPaymentCoreBundle
    cp vendor/jms/payment-core-bundle/JMS/Payment/CoreBundle/Resources/config/doctrine/* app/Resources/config/JMSPaymentCoreBundle/

You now have a copy of the following mapping files under ``app/Resources/config/JMSPaymentCoreBundle``:

- ``Credit.orm.xml``
- ``FinancialTransaction.orm.xml``
- ``Payment.orm.xml``
- ``PaymentInstruction.orm.xml``

Configuring custom mapping
--------------------------
The next step is to tell Symfony to use your copy of the files instead of the ones supplied by this bundle:

.. code-block :: yaml

    # app/config/config.yml

    doctrine:
        orm:
            # ...
            mappings:
                JMSPaymentCoreBundle:
                    type: xml
                    dir: '%kernel.root_dir%/../app/Resources/config/JMSPaymentCoreBundle'
                    prefix: JMS\Payment\CoreBundle\Entity
                    alias: JMSPaymentCoreBundle

Overriding decimal columns
--------------------------
Symfony is now using your custom mapping. Taking ``PaymentInstruction.orm.xml`` as an example, we can increase the maximum value of the ``amount`` column as follows:

.. code-block :: xml

    <!-- app/Resources/config/JMSPaymentCoreBundle/PaymentInstruction.orm.xml -->

    <!-- Set maximum value to 9999999999.99999 -->
    <field name="amount" type="decimal" precision="15" scale="5" />

.. warning::

    Make sure you change the definition of **all** the ``decimal`` columns in **all** the mapping files.

Updating the database
---------------------
Now that you changed the mapping, you need to update your database schema.

If you're not using database migrations:

.. code-block :: bash

    bin/console doctrine:schema:update

Or, if you're using migrations:

.. code-block :: bash

    bin/console doctrine:migrations:diff
    bin/console doctrine:migrations:migrate
