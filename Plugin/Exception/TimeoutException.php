<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception;

/**
 * This exception is thrown when the plugin experiences a connection timeout.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class TimeoutException extends BlockedException
{
}