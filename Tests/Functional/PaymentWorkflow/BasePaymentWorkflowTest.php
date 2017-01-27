<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\PaymentWorkflow;

use JMS\Payment\CoreBundle\Tests\Functional\BaseTestCase;
use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;

abstract class BasePaymentWorkflowTest extends BaseTestCase
{
    protected function getRawExtendedData($order)
    {
        $em = self::$kernel->getContainer()->get('em');

        $stmt = $em->getConnection()->prepare('
            SELECT extended_data
            FROM payment_instructions
            WHERE id = '.$order->getPaymentInstruction()->getId()
        );

        $stmt->execute();
        $result = $stmt->fetchAll();

        return unserialize($result[0]['extended_data']);
    }

    protected function doRequest($order, $route)
    {
        $client = $this->createClient();
        $this->importDatabaseSchema();

        $em = self::$kernel->getContainer()->get('em');
        $router = self::$kernel->getContainer()->get('router');

        $em->persist($order);
        $em->flush();

        $crawler = $client->request('GET', $router->generate($route, array('id' => $order->getId())));
        $form = $crawler->selectButton('submit_btn')->form();
        $form['jms_choose_payment_method[method]']->select('paypal_express_checkout');
        $client->submit($form);

        return $client->getResponse();
    }

    protected function refreshOrder($order)
    {
        $em = self::$kernel->getContainer()->get('em');
        $em->clear();

        return $em->getRepository('TestBundle:Order')->find($order->getId());
    }
}
