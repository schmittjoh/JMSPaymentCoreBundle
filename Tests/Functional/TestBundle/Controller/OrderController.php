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
 * @Template("TestBundle:Order:order.html.twig")
 *
 * @author Johannes
 */
class OrderController extends Controller
{
    /**
     * @Route("/{id}/payment", name="payment")
     *
     * @param Order $order
     */
    public function paymentAction(Order $order)
    {
        return $this->handleRequest($order, array(
            'paypal_express_checkout' => array(
                'foo' => 'bar',
            ),
        ));
    }

    private function handleRequest($order, array $predefinedData)
    {
        $formData = array(
            'currency' => 'EUR',
            'amount' => $order->getAmount(),
            'predefined_data' => $predefinedData,
        );

        $formType = Legacy::supportsFormTypeName()
            ? 'jms_choose_payment_method'
            : 'JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType'
        ;

        $form = $this->get('form.factory')->create($formType, null, $formData);

        $request = Legacy::supportsRequestService()
            ? $this->getRequest()
            : $this->get('request_stack')->getCurrentRequest()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $ppc = $this->get('payment.plugin_controller');

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
