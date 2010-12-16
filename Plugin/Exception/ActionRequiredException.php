<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Plugin\Exception;

/**
 * This exception is thrown when an user action is required in order
 * to complete the transaction.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ActionRequiredException extends BlockedException
{
    protected $action;
    
    public function getAction()
    {
        return $this->action;
    }
    
    public function setAction($action)
    {
        $this->action = $action;
    }
}