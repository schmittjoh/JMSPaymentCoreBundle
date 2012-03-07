<?php

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

            // Set the return/cancel Url
            $ext_data = $instruction->getExtendedData();
            $ext_data->set('return_url', $this->get('router')->generate('approvePayment', array('paymentId' => $paymentId), true));
            $ext_data->set('cancel_url', $this->get('router')->generate('cancelPayment', array(), true));

            // Set the details of the Payment
            // How to set this Parameters: https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_WPCustomizing
           $instruction->getExtendedData()->set('checkout_params',  array('L_PAYMENTREQUEST_0_NAME0' => 'Some super cool Toy',
                                                                    'L_PAYMENTREQUEST_0_NUMBER0' => '123',
                                                                    'L_PAYMENTREQUEST_0_AMT0' => '123',
                                                                    'L_PAYMENTREQUEST_0_QTY0' => '1',
                                                                    'PAYMENTREQUEST_0_CURRENCYCODE' => 'EUR'));

        }

        // try to approve the payment (retries the transaction if not called for the first time).
        // Tip: All methods that perform a transaction return a Result object.
        $result = $ppc->approveAndDeposit($paymentId, 123);

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
        // Here I return to my Homepage with a Flash-Message
        $this->get('session')->setFlash('empfehlung_flash', 'Die Bezahlung war erfolgreich!');
        return $this->redirect($this->generateUrl('Homepage'));
    }
}

