Usage
=====

Introduction
------------
In this chapter, we will explore how you can integrate JMSPaymentCoreBundle
into your application. We will assume that you already have created an order
object or equivalent. This could look like:

.. code-block :: php

    <?php

    use Doctrine\ORM\Mapping as ORM;
    use JMS\Payment\CoreBundle\Entity\PaymentInstruction;

    class Order
    {
        /** @ORM\OneToOne(targetEntity="JMSPaymentCore:PaymentInstruction") */
        private $paymentInstruction;

        /** @ORM\Column(type="string", unique = true) */
        private $orderNumber;

        /** @ORM\Column(type="decimal", precision = 2) */
        private $amount;

        // ...

        public function __construct($amount, $orderNumber)
        {
            $this->amount = $amount;
            $this->orderNumber = $orderNumber;
        }

        public function getOrderNumber()
        {
            return $this->orderNumber;
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

        // ...
    }

.. note ::

    An order object, or the like is not strictly necessary, but since it is
    regularly available, we will be using it in this chapter for demonstration
    purposes.

Choosing the Payment Method
---------------------------
Usually, you want to give a potential customer some options on how to pay. For
this, JMSPaymentCoreBundle ships with a special form type, ``jms_choose_payment_method``,
which we will leverage.

.. note ::

    In the following examples, we will make use of JMSDiExtraBundle_, and
    SensioFrameworkExtraBundle_. This is by no means required when you implement
    this in your own application though.

.. warning ::

    We have completely left out any security considerations, in a real-world
    scenario, you have to make sure the following actions are sufficiently
    covered by access rules, for example by using @PreAuthorize from
    JMSSecurityExtraBundle_.

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation as DI;
    use JMS\Payment\CoreBundle\Entity\Payment;
    use JMS\Payment\CoreBundle\PluginController\Result;
    use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
    use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
    use Symfony\Component\HttpFoundation\RedirectResponse;

    /**
     * @Route("/payments")
     */
    class PaymentController
    {
        /** @DI\Inject */
        private $request;

        /** @DI\Inject */
        private $router;

        /** @DI\Inject("doctrine.orm.entity_manager") */
        private $em;

        /** @DI\Inject("payment.plugin_controller") */
        private $ppc;

        /**
         * @Route("/{orderNumber}/details", name = "payment_details")
         * @Template
         */
        public function detailsAction(Order $order)
        {
            $form = $this->getFormFactory()->create('jms_choose_payment_method', null, array(
                'amount'   => $order->getAmount(),
                'currency' => 'EUR',
                'default_method' => 'payment_paypal', // Optional
                'predefined_data' => array(
                    'paypal_express_checkout' => array(
                        'return_url' => $this->router->generate('payment_complete', array(
                            'orderNumber' => $order->getOrderNumber(),
                        ), true),
                        'cancel_url' => $this->router->generate('payment_cancel', array(
                            'orderNumber' => $order->getOrderNumber(),
                        ), true)
                    ),
                ),
            ));

            if ('POST' === $this->request->getMethod()) {
                $form->bindRequest($this->request);

                if ($form->isValid()) {
                    $this->ppc->createPaymentInstruction($instruction = $form->getData());

                    $order->setPaymentInstruction($instruction);
                    $this->em->persist($order);
                    $this->em->flush($order);

                    return new RedirectResponse($this->router->generate('payment_complete', array(
                        'orderNumber' => $order->getOrderNumber(),
                    )));
                }
            }

            return array(
                'form' => $form->createView()
            );
        }

        // ...

        /** @DI\LookupMethod("form.factory") */
        protected function getFormFactory() { }
    }

The ``jms_choose_payment_method`` form type will automatically render a form
with all available payment methods. Upon binding, the form type will validate
the data for the chosen payment method, and on success will give us a valid
``PaymentInstruction`` instance back.

You might want to add extra costs for a specific payment method. You can easily
handle this by passing on a closure to the ``amount`` of the form:

.. code-block :: php

    <?php

    use JMS\Payment\CoreBundle\Entity\ExtendedData;

    $form = $this->getFormFactory()->create('jms_choose_payment_method', null, array(
        'amount' => function($currency, $paymentSystemName, ExtendedData $data) use ($order) {
            if ('paypal_express_checkout' == $paymentSystemName) {
                return $order->getAmount() * 1.05;
            }

            return $order->getAmount();
        },

        // ...
    ));

Depositing Money
----------------
In the previous section, we have created our ``PaymentInstruction``. Now, we
will see how we can actually deposit money in our account. As you saw above
in the ``detailsAction``, we redirected the user to the ``payment_complete``
route for which we will now create the corresponding action in our controller:

.. code-block :: php

    <?php

    use JMS\DiExtraBundle\Annotation as DI;
    use JMS\Payment\CoreBundle\Entity\Payment;
    use JMS\Payment\CoreBundle\PluginController\Result;
    use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
    use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
    use Symfony\Component\HttpFoundation\RedirectResponse;

    /**
     * @Route("/payments")
     */
    class PaymentController
    {
        /** @DI\Inject */
        private $request;

        /** @DI\Inject */
        private $router;

        /** @DI\Inject("doctrine.orm.entity_manager") */
        private $em;

        /** @DI\Inject("payment.plugin_controller") */
        private $ppc;

        // ... see previous section

        /**
         * @Route("/{orderNumber}/complete", name = "payment_complete")
         */
        public function completeAction(Order $order)
        {
            $instruction = $order->getPaymentInstruction();
            if (null === $pendingTransaction = $instruction->getPendingTransaction()) {
                $payment = $this->ppc->createPayment($instruction->getId(), $instruction->getAmount() - $instruction->getDepositedAmount());
            } else {
                $payment = $pendingTransaction->getPayment();
            }

            $result = $this->ppc->approveAndDeposit($payment->getId(), $payment->getTargetAmount());
            if (Result::STATUS_PENDING === $result->getStatus()) {
                $ex = $result->getPluginException();

                if ($ex instanceof ActionRequiredException) {
                    $action = $ex->getAction();

                    if ($action instanceof VisitUrl) {
                        return new RedirectResponse($action->getUrl());
                    }

                    throw $ex;
                }
            } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                throw new \RuntimeException('Transaction was not successful: '.$result->getReasonCode());
            }

            // payment was successful, do something interesting with the order
        }
    }

.. _JMSDiExtraBundle: http://jmsyst.com/bundles/JMSDiExtraBundle
.. _JMSSecurityExtraBundle: http://jmsyst.com/bundles/JMSSecurityExtraBundle
.. _SensioFrameworkExtraBundle: http://symfony.com/doc/current/bundles/SensioFrameworkExtraBundle/index.html
