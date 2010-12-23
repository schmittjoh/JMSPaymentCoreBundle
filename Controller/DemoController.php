<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Controller;

use Bundle\JMS\Payment\CorePaymentBundle\PluginController\Result;

use Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception\ActionRequiredException;
use Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception\Action\VisitUrl;
use Bundle\JMS\Payment\CorePaymentBundle\Entity\ExtendedData;
use Bundle\JMS\Payment\CorePaymentBundle\Entity\PaymentInstruction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

class DemoController extends Controller
{
    public function indexAction()
    {
        $ppc = $this->container->get('payment.plugin_controller');

        $instruction = new PaymentInstruction(123, 'EUR', 'paypal_express_checkout', new ExtendedData());
        $ppc->createPaymentInstruction($instruction);

        $payment = $ppc->createPayment($instruction->getId(), 123);

        $result = $ppc->approve($payment->getId(), 123);
        if (Result::STATUS_PENDING === $result->getStatus()) {
            $ex = $result->getPluginException();
            if ($ex instanceof ActionRequiredException) {
                $action = $ex->getAction();

                if ($action instanceof VisitUrl) {
                    return $this->redirect($action->getUrl());
                }

                throw $ex;
            }
        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
            // you can do your error processing here
            throw new \RuntimeException('Transaction was not successful.');
        }

        return $this->render('PaymentBundle:Demo:index.php');
    }
}
