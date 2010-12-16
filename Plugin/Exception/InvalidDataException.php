<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception;

/**
 * This exception is thrown whenever the plugin realizes that the data
 * provided along with the transaction is invalid for the given transaction.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class InvalidDataException extends Exception
{
}