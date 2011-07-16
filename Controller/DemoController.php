<?php

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

namespace JMS\Payment\CoreBundle\Controller;

use JMS\Payment\CoreBundle\PluginController\Result;

use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\Action\VisitUrl;
use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DemoController extends Controller
{
    /**
     * Initiates the payment, and possibly redirects to another payment backend provider. In case
     * of a redirect, you can re-use this action as callback to retry the approval transaction.
     *
     * You can use this action as template for other transactions (such as deposit transactions).
     *
     * @param integer $paymentId
     */
    public function indexAction($paymentId = null)
    {
        $ppc = $this->container->get('payment.plugin_controller');

        // check if this action is called for the first time, or as a callback
        if (null === $paymentId) {
            // under real conditions, you'll likely not hard-code the payment amount,
            // currency, etc. here but retrieve it from a different source (like a form)
            $instruction = new PaymentInstruction(123, 'EUR', 'paypal_express_checkout', new ExtendedData());
            $ppc->createPaymentInstruction($instruction);

            // create the payment for the transaction (this allows you for example to
            // collect money for the same payment instruction in multiple payments).
            // In this case, we collect the entire amount in one payment.
            $paymentId = $ppc->createPayment($instruction->getId(), 123)->getId();
        }

        // try to approve the payment (retries the transaction if not called for the first time).
        // Tip: All methods that perform a transaction return a Result object.
        $result = $ppc->approve($paymentId, 123);

        // some payment backend systems require some form of user interaction to authorize
        // a transaction, in most cases this is a redirect to a different URL, but it could
        // really be anything else, the system doesn't make any assumptions. In the cases
        // where such an interaction is required, the result's status will be set to PENDING.
        if (Result::STATUS_PENDING === $result->getStatus()) {
            $ex = $result->getPluginException();

            if ($ex instanceof ActionRequiredException) {
                $action = $ex->getAction();

                // in this case we are redirect to Paypal
                if ($action instanceof VisitUrl) {
                    return $this->redirect($action->getUrl());
                }

                // no supported action
                throw $ex;
            }
        } else if (Result::STATUS_SUCCESS !== $result->getStatus()) {
            // you can do your error processing here

            // the reasoning code is set by the payment backend provider and indicates what
            // exactly went wrong during the transaction. all transactions are also logged to
            // the database, so you can check this at any time.
            throw new \RuntimeException('Transaction was not successful: '.$result->getReasonCode());
        }

        // transaction was successful
        return $this->render('JMSPaymentCoreBundle:Demo:index.php');
    }
}
