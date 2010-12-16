<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception;

/**
 * This exception when the payment is in a pending state.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PaymentPendingException extends BlockedException
{
}