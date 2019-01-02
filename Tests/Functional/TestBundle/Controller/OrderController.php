<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Controller;

use JMS\Payment\CoreBundle\PluginController\PluginController;
use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\Payment\CoreBundle\Util\Legacy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

/**
 * @Route("/order")
 *
 * @author Johannes
 */
class OrderController extends AbstractController
{
    /**
     * @Route("/{orderId}/payment-details", name = "payment_details")
     * @Template("TestBundle:Order:paymentDetails.html.twig")
     *
     * @param int $orderId
     * @param PluginController $pluginController
     * @return array|Response
     */
    public function paymentDetailsAction($orderId, PluginController $pluginController)
    {
        $order = $this->getDoctrine()->getManager()->getRepository(Order::class)->find($orderId);

        $formType = Legacy::supportsFormTypeClass()
            ? 'JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType'
            : 'jms_choose_payment_method'
        ;

        $form = $this->get('form.factory')->create($formType, null, array(
            'currency' => 'EUR',
            'amount' => $order->getAmount(),
            'predefined_data' => array(
                'test_plugin' => array(
                    'foo' => 'bar',
                ),
            ),
        ));

        $em = $this->getDoctrine()->getManager();

        $request = Legacy::supportsRequestService()
            ? $this->getRequest()
            : $this->get('request_stack')->getCurrentRequest()
        ;

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $instruction = $form->getData();
            $pluginController->createPaymentInstruction($instruction);

            $order->setPaymentInstruction($instruction);
            $em->persist($order);
            $em->flush();

            return new Response('', 201);
        }

        return array('form' => $form->createView());
    }
}
