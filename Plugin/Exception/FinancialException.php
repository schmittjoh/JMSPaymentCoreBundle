<?php

namespace Bundle\PaymentBundle\Plugin\Exception;

/**
 * This exception is thrown whenever a transaction against the backend system 
 * fails due to a financial error.
 * 
 * Example: Invalid credit card information is found while performing an approve
 * 			transaction. 
 * 
 * The plugin should set all error codes to ease debugging.
 * For further information, please see PluginException.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FinancialException extends Exception
{
}