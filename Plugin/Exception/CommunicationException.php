<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception;

/**
 * This exception is thrown when a plugin experiences a connectivity problem
 * while communication with a payment backend system.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class CommunicationException extends Exception
{
}