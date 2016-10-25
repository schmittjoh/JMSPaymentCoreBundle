Accepting payments
==================
In this guide, we explore how to accept payments using this bundle, by building a simplified *Checkout* system from scratch.

.. tip ::
    In no way are you forced to use the presented system in your application, this is merely the simplest way to show this bundle in action. We recomend you follow the steps below and, once you grasp how this bundle works, think about the best way to integrate it into your application.

.. warning ::

    We have completely left out any security considerations. In a real-world scenario, you must make sure a user is not able to access other users' data.

The Order entity
----------------
The ``Order`` entity represents what is being purchased and usually contains:

- ``$id``: The unique id of the order
- ``$amount``: The total price
- ``$paymentInstruction``: The ``PaymentInstruction`` instance

.. tip ::

    If you're wondering what a ``PaymentInstruction`` is, take a look at :doc:`The Model </model>`, though you don't strictly need to understand it to follow the instructions below.

Here's the full code for a minimal ``Order`` entity:

.. code-block :: php

    // src/AppBundle/Entity/Order.php

    namespace AppBundle\Entity;

    use Doctrine\ORM\Mapping as ORM;
    use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

    /**
     * @ORM\Table(name="orders")
     * @ORM\Entity
     */
    class Order
    {
        /**
         * @ORM\Column(name="id", type="integer")
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="AUTO")
         */
        private $id;

        /** @ORM\OneToOne(targetEntity="JMS\Payment\CoreBundle\Entity\PaymentInstruction") */
        private $paymentInstruction;

        /** @ORM\Column(type="decimal", precision=10, scale=5) */
        private $amount;

        public function __construct($amount)
        {
            $this->amount = $amount;
        }

        public function getId()
        {
            return $this->id;
        }

        public function getAmount()
        {
            return $this->amount;
        }

        public function getPaymentInstruction()
        {
            return $this->paymentInstruction;
        }

        public function setPaymentInstruction(PaymentInstruction $instruction)
        {
            $this->paymentInstruction = $instruction;
        }
    }

.. warning ::

    Note that the ``precision`` and ``scale`` in the ``$amount`` column definition are set to ``10`` and ``5``, respectively. This is consistent with the mapping this bundle uses internally and means that the greatest amount you will be able to accept is ``99999.99999``.

    See the :doc:`overriding_entity_mapping` guide for instructions on how to override this limit.

Before proceeding, make sure you update your database schema, in order to create the ``orders`` table:

.. code-block :: bash

    bin/console doctrine:schema:update

Or, if using migrations:

.. code-block :: bash

    bin/console doctrine:migrations:diff
    bin/console doctrine:migrations:migrate

The Controller
--------------
Each step of our *Checkout* process will be implemented as an *action* in an ``OrdersController``. All routes will be *namespaced* under ``/orders``.

Go ahead and create the controller:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    namespace AppBundle\Controller;

    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;

    /**
     * @Route("/orders")
     */
    class OrdersController extends Controller
    {
    }

Creating an Order
------------------
The first step in our *Checkout* process is to create an ``Order``, which we will do in a ``newAction``. This action acts as the *bridge* between the *Checkout* process and the rest of your application.

To simplify, we will only be passing an ``amount`` (the total price of the items being purchased) as a parameter to the action. In a real world application you would probably pass the ``$id`` of a *Shopping Cart*, or a similar entity that holds information about the items being purchased.

Create the ``newAction`` in the ``OrdersController``:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use AppBundle\Entity\Order;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

    /**
     * @Route("/new/{amount}")
     */
    public function newAction($amount)
    {
        $em = $this->getDoctrine()->getManager();

        $order = new Order($amount);
        $em->persist($order);
        $em->flush();

        return $this->redirect($this->generateUrl('app_orders_show', [
            'id' => $order->getId(),
        ]));
    }

If you navigate to ``/orders/new/42.24``, a new ``Order`` will be inserted in the database with ``42.24`` as the ``amount`` and you will be redirected to the ``showAction``, which we will create next.

Creating the payment form
-------------------------
Once the ``Order`` has been created, the next step in our *Checkout* process is to display it, along with the payment form. We will be doing this in a ``showAction``:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use AppBundle\Entity\Order;
    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
    use Symfony\Component\HttpFoundation\Request;

    /**
     * @Route("/{id}/show")
     * @Template
     */
    public function showAction(Request $request, Order $order)
    {
        $form = $this->createForm(ChoosePaymentMethodType::class, null, [
            'amount'   => $order->getAmount(),
            'currency' => 'EUR',
        ]);

        return [
            'order' => $order,
            'form'  => $form->createView(),
        ];
    }

.. note ::

    If your Symfony version is earlier than ``3.0``, you must refer to the form by its alias instead of using the class directly:

    .. code-block :: php

        // src/AppBundle/Controller/OrdersController.php

        $form = $this->createForm('jms_choose_payment_method', null, [
            'amount'   => $order->getAmount(),
            'currency' => 'EUR',
        ]);

And the corresponding template:

.. code-block :: twig

    {# src/AppBundle/Resources/views/Orders/show.html.twig #}

    Total price: € {{ order.amount }}

    {{ form_start(form) }}
        {{ form_widget(form) }}
        <input type="submit" value="Pay € {{ order.amount }}" />
    {{ form_end(form) }}

If you now refresh the page in your browser, you should see the template rendered, with all the payment methods you have installed. The form includes a radio button so the user can select the payment method they wish to use.

.. tip ::

    If you get a ``There is no payment method available`` exception, you haven't configured any payment backends yet. Please see :ref:`setup-configure-plugin` for information on how to do this.

.. tip ::
    See :doc:`/payment_form` for information on all the available options you can pass to the form.

Handling form submission
------------------------
We'll handle form submission in the same action which renders the form. Upon binding, the form type will validate the data for the chosen payment method and, on success, give us back a valid ``PaymentInstruction`` instance.

We'll *attach* this ``PaymentInstruction`` to the ``Order`` and then redirect to the ``paymentCreateAction``. In case the form is not valid, we don't redirect and the template is re-rendered with form errors displayed.

Note that no remote calls to the payment backend are made in this action, we're simply manipulating data in the local database.

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use AppBundle\Entity\Order;
    use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
    use Symfony\Component\HttpFoundation\Request;

    /**
     * @Route("/{id}/show")
     * @Template
     */
    public function showAction(Request $request, Order $order)
    {
        $form = $this->createForm(ChoosePaymentMethodType::class, null, [
            'amount'   => $order->getAmount(),
            'currency' => 'EUR',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $ppc = $this->get('payment.plugin_controller');
            $ppc->createPaymentInstruction($instruction = $form->getData());

            $order->setPaymentInstruction($instruction);

            $em = $this->getDoctrine()->getManager();
            $em->persist($order);
            $em->flush($order);

            return $this->redirect($this->generateUrl('app_orders_paymentcreate', [
                'id' => $order->getId(),
            ]));
        }

        return [
            'order' => $order,
            'form'  => $form->createView(),
        ];
    }

Depositing money
----------------
In the previous section, we created our ``PaymentInstruction`` and redirected to the ``paymentCreateAction``. In this section we will be implementing that action.

Creating a ``Payment`` instance
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Let's start by creating a private method in our controller, which will aid us in creating the ``Payment`` instance. No remote calls will be made yet.

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    private function createPayment($order)
    {
        $instruction = $order->getPaymentInstruction();
        $pendingTransaction = $instruction->getPendingTransaction();

        if ($pendingTransaction !== null) {
            return $pendingTransaction->getPayment();
        }

        $ppc = $this->get('payment.plugin_controller');
        $amount = $instruction->getAmount() - $instruction->getDepositedAmount();

        return $ppc->createPayment($instruction->getId(), $amount);
    }

Issuing the payment
~~~~~~~~~~~~~~~~~~~
Now we'll call the ``createPayment`` method we implemented in the previous section in a new ``createPaymentAction``, where we will actually create a payment through the payment backend and, if successful, redirect the user to a ``paymentCompleteAction``:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use AppBundle\Entity\Order;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use JMS\Payment\CoreBundle\PluginController\Result;

    /**
     * @Route("/{id}/payment/create")
     */
    public function paymentCreateAction(Order $order)
    {
        $payment = $this->createPayment($order);

        $ppc = $this->get('payment.plugin_controller');
        $result = $ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());

        if ($result->getStatus() === Result::STATUS_SUCCESS) {
            return $this->redirect($this->generateUrl('app_orders_paymentcomplete', [
                'id' => $order->getId(),
            ]));
        }

        throw $result->getPluginException();

        // In a real-world application you wouldn't throw the exception. You would,
        // for example, redirect to the showAction with a flash message informing
        // the user that the payment was not successful.
    }


.. tip ::

    If you get an ``Unable to generate a URL`` exception, the transaction was successful. We just haven't created that action yet, we will be doing so later.

    If you get an ``ActionRequiredException``, you are using a payment backend which requires *offsite* operations. In the next section we explain what this means and how to support it.

Performing the payment *offsite*
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Certain payment backends (e.g. Paypal) require the user to go their site to actually perform the payment. In that case, ``$result`` will have status ``Pending`` and we need to redirect the user to a given URL.

We would add the following to our action:


.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
    use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
    use JMS\Payment\CoreBundle\PluginController\Result;

    if ($result->getStatus() === Result::STATUS_PENDING) {
        $ex = $result->getPluginException();

        if ($ex instanceof ActionRequiredException) {
            $action = $ex->getAction();

            if ($action instanceof VisitUrl) {
                return $this->redirect($action->getUrl());
            }
        }
    }

    throw $result->getPluginException();

.. tip ::

    If you get an exception, you probably didn't configure the payment plugin correctly. Take a look at the respective plugin's documentation and make sure you followed the instructions.

Displaying a *Payment complete* page
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
The last step in out *Checkout* process is to tell the user the payment was successful. We wil be doing so in a ``paymentCompleteAction``, to which we have been redirected from the ``paymentCreateAction``:

.. code-block :: php

    // src/AppBundle/Controller/OrdersController.php

    use Symfony\Component\HttpFoundation\Response;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

    /**
     * @Route("/{id}/payment/complete")
     */
    public function paymentCompleteAction(Order $order)
    {
        return new Response('Payment complete');
    }
