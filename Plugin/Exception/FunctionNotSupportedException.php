<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception;

/**
 * This exception is thrown to indicate that a specific transaction is not
 * supported by the plugin.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FunctionNotSupportedException extends Exception
{
}