<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Controller;

use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\Payment\CoreBundle\Util\Legacy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/order")
 *
 * @author Johannes
 */
class OrderController extends Controller
{
    /**
     * @Route("/{id}/payment-details", name = "payment_details")
     * @Template("TestBundle:Order:paymentDetails.html.twig")
     *
     * @param Order $order
     */
    public function paymentDetailsAction(Order $order)
    {
        $formType = Legacy::supportsFormTypeName()
            ? 'jms_choose_payment_method'
            : 'JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType'
        ;

        $form = $this->get('form.factory')->create($formType, null, array(
            'currency' => 'EUR',
            'amount' => $order->getAmount(),
            'predefined_data' => array(
                'paypal_express_checkout' => array(
                    'foo' => 'bar',
                ),
            ),
        ));

        $em = $this->getDoctrine()->getManager();
        $ppc = $this->get('payment.plugin_controller');

        $request = Legacy::supportsRequestService()
            ? $this->getRequest()
            : $this->get('request_stack')->getCurrentRequest()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $instruction = $form->getData();
            $ppc->createPaymentInstruction($instruction);

            $order->setPaymentInstruction($instruction);
            $em->persist($order);
            $em->flush();

            return new Response('', 201);
        }

        return array('form' => $form->createView());
    }
}
