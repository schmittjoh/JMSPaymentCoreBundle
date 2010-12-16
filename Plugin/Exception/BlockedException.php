<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception;

/**
 * This exception is thrown whenever a financial transaction cannot be processed.
 * 
 * This exception must only be thrown when the situation is temporary, and there
 * is a chance that the same transaction can be performed successfully against
 * the PaymentInstruction at a later time.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class BlockedException extends Exception
{
}