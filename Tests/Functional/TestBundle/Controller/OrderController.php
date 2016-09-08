<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Controller;

use Symfony\Component\HttpFoundation\Response;
use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\DiExtraBundle\Annotation as DI;
use JMS\SecurityExtraBundle\Annotation\PreAuthorize;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Cache;

/**
 * @Route("/order")
 * @author Johannes
 */
class OrderController
{
    /** @DI\Inject */
    private $em;

    /** @DI\Inject */
    private $request;

    /** @DI\Inject("payment.plugin_controller") */
    private $ppc;

    /**
     * @Route("/{id}/payment-details", name = "payment_details")
     * @Template
     *
     * @param Order $order
     */
    public function paymentDetailsAction(Order $order)
    {
        $form = $this->getFormFactory()->create('jms_choose_payment_method', null, array(
            'currency' => 'EUR',
            'amount' => $order->getAmount(),
            'csrf_protection' => false,
            'predefined_data' => array(
                'paypal_express_checkout' => array(
                    'foo' => 'bar',
                ),
            ),
        ));

        if ('POST' === $this->request->getMethod()) {
            if (method_exists($form, 'submit')) {
                $form->submit($this->request);
            } else {
                $form->bindRequest($this->request);
            }

            if ($form->isValid()) {
                $instruction = $form->getData();
                $this->ppc->createPaymentInstruction($instruction);

                $order->setPaymentInstruction($instruction);
                $this->em->persist($order);
                $this->em->flush();

                return new Response('', 201);
            }
        }

        return array('form' => $form->createView());
    }

    /** @DI\LookupMethod("form.factory") */
    protected function getFormFactory() { }
}
