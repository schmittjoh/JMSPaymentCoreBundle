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
The configuration is as simple as setting a random secret which will be used for encrypting data, in case this is requested.

You can generate the secret with the following command:

.. code-block :: bash

    # Feel free to increase the length of the generated string by
    # passing a larger number to openssl_random_pseudo_bytes
    php -r 'echo bin2hex(openssl_random_pseudo_bytes(16))."\n";'

And then use it in your configuration:

.. configuration-block ::

    .. code-block :: yaml

        # app/config/config.yml
        jms_payment_core:
            secret: yoursecret

    .. code-block :: xml

        <!-- app/config/config.xml -->
        <jms-payment-core secret="yoursecret" />

.. note ::

    If you change the secret, all data encrypted with the old secret will become unreadable.

Create database tables
----------------------
This bundle requires a few database tables to function. You can create these tables as follows.

If you're not using database migrations:

.. code-block :: bash

    bin/console doctrine:schema:update

Or, if you're using migrations:

.. code-block :: bash

    bin/console doctrine:migrations:diff
    bin/console doctrine:migrations:migrate

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
