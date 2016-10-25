Payment form
============
This bundle ships with a form type that automatically renders a ``choice`` (radio button, select) so that the user can choose their preferred payment method.

Additionally, each payment plugin you have installed, includes a specific form that is also rendered. This form is dependent on the payment method itself, different methods will have different forms.

As an example, if you have both the PayPal and Paymill plugins installed, both their forms will be rendered. In PayPal's case, the form is empty (since the user does not enter any information on your site) but for Paymill a Credit Card form is rendered.

.. tip ::
    See the :doc:`guides/accepting_payments` guide for detailed instructions on how to integrate the form in your application, namely how to handle form submission.

Creating the form
-----------------
When creating the form you need to specify at least the ``amount`` and ``currency`` options. See below for all the available options.

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'   => '10.42',
        'currency' => 'EUR',
    ]);

.. note ::

    If your Symfony version is earlier than ``3.0``, you must refer to the form by its alias instead of using the class directly:

    .. code-block :: php

        // src/AppBundle/Controller/OrdersController.php

        $form = $this->createForm('jms_choose_payment_method', null, [
            'amount'   => '10.42',
            'currency' => 'EUR',
        ]);

Changing how the form looks
---------------------------
If you need to change how the form looks, you can use form theming, which allows you to customize how each element of the form is rendered. Our theme will be implemented in a separate Twig file, which we will then reference from the template where the form is rendered.

.. tip ::

    See the form component's `documentation <https://symfony.com/doc/current/form/form_customization.html>`_ for more information about form theming

Start by creating an empty theme file:

.. code-block :: twig

    {# src/AppBundle/Resources/views/Orders/theme.html.twig #}

    {% extends 'form_div_layout.html.twig' %}

.. note ::

    We're extending Symfony's default ``form_div_layout.html.twig`` theme. If your application is setup to use another theme, you probably want to extend that one instead.

And then reference it from the template where the form is rendered:

.. code-block :: twig

    {# src/AppBundle/Resources/views/Orders/show.html.twig #}

    {% form_theme form 'AppBundle:Orders:theme.html.twig' %}

    {{ form_start(form) }}
        {{ form_widget(form) }}
        <input type="submit" value="Pay € {{ order.amount }}" />
    {{ form_end(form) }}

Hiding the payment method radio button
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
When the form only has one available payment method (either because only one payment plugin is installed or because you used the ``allowed_methods`` option) you likely want to hide the payment method radio button completely. You can do so as follows:

.. code-block :: twig

    {# src/AppBundle/Resources/views/Orders/theme.html.twig #}

    {# Don't render the radio button's label #}
    {% block _jms_choose_payment_method_method_label %}
    {% endblock %}

    {# Hide each entry in the radio button #}
    {% block _jms_choose_payment_method_method_widget %}
        <div style="display: none;">
            {{ parent() }}
        </div>
    {% endblock %}

.. tip ::
    If you hide the radio button, you will want to use the :ref:`form-default-method` option to automatically select the payment method.

Available options
-----------------

``amount``
~~~~~~~~~~
**Mandatory**

The amount (i.e. total price) of the payment.

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'   => '10.42',
        'currency' => 'EUR',
    ]);

You might want to add extra costs for a specific payment method. You can implement this by passing a closure instead of a static value:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Entity\ExtendedData;
    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $amount = '10.42';

    $amountClosure = function ($currency, $paymentSystemName, ExtendedData $data) use ($amount) {
        if ($paymentSystemName === 'paypal_express_checkout') {
            return $amount * 1.05;
        }

        return $amount;
    };

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'   => $amountClosure,
        'currency' => 'EUR',
    ]);

``currency``
~~~~~~~~~~~~
**Mandatory**

The three-letter currency code, i.e. ``EUR`` or ``USD``.

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'   => '10.42',
        'currency' => 'EUR',
    ]);

``predefined_data``
~~~~~~~~~~~~~~~~~~~
**Optional**

**Default**: ``[]``

The payment plugins likely require you to provide additional configuration in order to create a payment. You can do this by passing an array to the ``predefined_data`` option of the form.

As an example, if we would be using the Stripe plugin, we would need to provide a ``description``, which would look like the following:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $predefinedData = [
        'stripe_checkout' => [
            'description' => 'My product',
        ],
    ];

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'          => '10.42',
        'currency'        => 'EUR',
        'predefined_data' => $predefinedData,
    ]);

If you would be using multiple payment backends, the ``$predefinedData`` array would have an entry for each of the methods:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    $predefinedData = [
        'paypal_express_checkout' => [...],
        'stripe_checkout'         => [...],
    ];

``allowed_methods``
~~~~~~~~~~~~~~~~~~~
**Optional**

**Default**: ``[]``

In case you wish to constrain the methods presented to the user, use the ``allowed_methods`` option:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'          => '10.42',
        'currency'        => 'EUR',
        'allowed_methods' => ['paypal_express_checkout']
    ]);

.. _form-default-method:

``default_method``
~~~~~~~~~~~~~~~~~~
**Optional**

**Default**: ``null``

By default, no payment method is selected in the radio button, which means users must select one themselves. This is the case even if you only have one payment method available.

If you wish to set a default payment method, you can use the ``default_method`` option:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'          => '10.42',
        'currency'        => 'EUR',
        'default_method'  => 'paypal_express_checkout',
    ]);

``choice_options``
~~~~~~~~~~~~~~~~~~
**Optional**

**Default**: ``[]``

Pass options to the payment method ``choice`` type. See the `ChoiceType refererence <https://symfony.com/doc/current/reference/forms/types/choice.html>`_ for all available options.

For example, to display a select instead of a radio button, set the ``expanded`` option to ``false``:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'         => '10.42',
        'currency'       => 'EUR',
        'choice_options' => [
            'expanded' => false,
        ],
    ]);

``method_options``
~~~~~~~~~~~~~~~~~~
**Optional**

**Default**: ``[]``

Pass options to each payment method's form type. For example, to hide the main label of the PayPal Express Checkout form, set the ``label`` option to ``false``:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;

    $form = $this->createForm(ChoosePaymentMethodType::class, null, [
        'amount'         => '10.42',
        'currency'       => 'EUR',
        'method_options' => [
            'paypal_express_checkout' => [
                'label' => false,
            ],
        ],
    ]);
