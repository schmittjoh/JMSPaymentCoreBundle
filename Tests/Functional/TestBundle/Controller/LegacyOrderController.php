<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Controller;

use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\Payment\CoreBundle\Util\Legacy;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Response;

class LegacyOrderController extends Controller
{
    public function paymentDetailsAction($orderId)
    {
        $order = $this->getDoctrine()->getManager()->getRepository(Order::class)->find($orderId);

        $formType = Legacy::supportsFormTypeClass()
            ? 'JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType'
            : 'jms_choose_payment_method'
        ;

        /** @var FormFactory $formFactory */
        $formFactory = $this->get('form.factory');

        $form = $formFactory->create($formType, null, array(
            'currency' => 'EUR',
            'amount' => $order->getAmount(),
            'predefined_data' => array(
                'test_plugin' => array(
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

        return $this->render('TestBundle:Order:paymentDetails.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
