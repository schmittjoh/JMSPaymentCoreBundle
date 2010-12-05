<?php

namespace Bundle\PaymentBundle\Exception;

/**
 * Base Exception for the PaymentBundle
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Exception extends \Exception
{
    protected $properties = array();
    
    public function addProperty($name, $value)
    {
        $this->properties[$name] = $value;
    }
    
    public function addProperties(array $properties)
    {
        $this->properties = array_merge($this->properties, $properties);
    }
    
    public function setProperties(array $properties)
    {
        $this->properties = $properties;
    }
    
    public function getProperties()
    {
        return $this->properties;
    }
}