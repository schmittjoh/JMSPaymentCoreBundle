Setup
=====

Installation
-------------
Install with composer:

.. code-block :: bash

    composer require jms/payment-core-bundle

And register the bundle in your ``AppKernel.php``:

.. code-block :: php

    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new JMS\Payment\CoreBundle\JMSPaymentCoreBundle(),
        );
    }

Configuration
-------------
The configuration is as simple as setting an encryption key which will be used for encrypting data. You can generate a random key with the following command:

.. code-block :: bash

    bin/console jms_payment_core:generate-key

And then use it in your configuration:

.. code-block :: yaml

    # app/config/config.yml
    jms_payment_core:
        encryption:
            secret: output_of_above_command

.. warning ::

    If you change the ``secret`` or the ``crypto`` provider, all encrypted data will become unreadable.

Create database tables
----------------------
This bundle requires a few database tables, which you can create as follows.

If you're not using database migrations:

.. code-block :: bash

    bin/console doctrine:schema:update

Or, if you're using migrations:

.. code-block :: bash

    bin/console doctrine:migrations:diff
    bin/console doctrine:migrations:migrate

.. note ::

    It's assumed you have entity auto mapping enabled, which is usually the case. If you don't, you need to either enable it:

    .. code-block :: yaml

        # app/config/config.yml
        doctrine:
            orm:
                auto_mapping: true

    Or explicitly register the configuration from this bundle:

    .. code-block :: yaml

        # app/config/config.yml
        doctrine:
            orm:
                mappings:
                    JMSPaymentCoreBundle: ~

.. _setup-configure-plugin:

Configure a payment backend
---------------------------
In addition to setting up this bundle, you will also need to install a *plugin* for each payment backend you intend to support. Plugins are simply bundles you add to your application, as you would with any other Symfony bundle.

.. tip ::

    See :doc:`Available payment backends <backends>` for the list of existing plugins.

Using the `Paypal plugin <https://github.com/schmittjoh/JMSPaymentPaypalBundle>`_ as an example, you would install it with composer:

.. code-block :: bash

    composer require jms/payment-paypal-bundle

Register it in your ``AppKernel.php``:

.. code-block :: php

    // app/AppKernel.php

    public function registerBundles()
    {
        $bundles = array(
            // ...
            new JMS\Payment\CoreBundle\JMSPaymentCoreBundle(),
            new JMS\Payment\PaypalBundle\JMSPaymentPaypalBundle(),
        );
    }

And configure it:

.. code-block :: yaml

    # app/config/config.yml

    jms_payment_paypal:
        username: your api username
        password: your api password
        signature: your api signature

.. note ::

    :doc:`Other plugins <backends>` will require different configuration. Take a look at their documentation for complete instructions.

Next steps
----------
If you have no prior experience with this bundle or payment processing in general, you should follow the :doc:`guides/accepting_payments` guide. Otherwise, proceed to the :doc:`payment_form` chapter.
