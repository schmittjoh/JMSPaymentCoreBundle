<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

use JMS\Payment\CoreBundle\Tests\Functional\TestBundle\Entity\Order;
use JMS\Payment\CoreBundle\Util\Number;

abstract class BasePaymentWorkflowTest extends BaseTestCase
{
    protected function getRawExtendedData($paymentInstruction)
    {
        $em = self::$kernel->getContainer()->get('em');

        $stmt = $em->getConnection()->prepare(
            'SELECT extended_data FROM payment_instructions WHERE id = '.$paymentInstruction->getId()
        );

        $stmt->execute();
        $result = $stmt->fetchAll();

        return unserialize($result[0]['extended_data']);
    }

    protected function doTestPaymentDetails()
    {
        $client = $this->createClient();
        $this->importDatabaseSchema();

        $em = self::$kernel->getContainer()->get('em');
        $router = self::$kernel->getContainer()->get('router');

        $order = new Order(123.45);
        $em->persist($order);
        $em->flush();

        $crawler = $client->request('GET', $router->generate('payment_details', array('id' => $order->getId())));
        $form = $crawler->selectButton('submit_btn')->form();
        $form['jms_choose_payment_method[method]']->select('paypal_express_checkout');
        $client->submit($form);

        $response = $client->getResponse();
        $this->assertSame(201, $response->getStatusCode(), substr($response, 0, 2000));

        $em->clear();
        $order = $em->getRepository('TestBundle:Order')->find($order->getId());
        $this->assertTrue(Number::compare(123.45, $order->getPaymentInstruction()->getAmount(), '=='));
        $this->assertEquals('bar', $order->getPaymentInstruction()->getExtendedData()->get('foo'));

        return $order;
    }
}
