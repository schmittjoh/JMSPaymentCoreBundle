<?php

namespace Bundle\PaymentBundle\Controller;

use Bundle\PaymentBundle\Entity\ExtendedData;
use Bundle\PaymentBundle\Entity\PaymentInstruction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DemoController extends Controller
{
    public function indexAction()
    {
        $ppc = $this->container->get('payment.plugin_controller');
        $instruction = new PaymentInstruction(123, 'EUR', 'paypal_express_checkout', new ExtendedData());
        $ppc->createPaymentInstruction($instruction);
        
        return $this->render('PaymentBundle:Demo:index.php');
    }
}
