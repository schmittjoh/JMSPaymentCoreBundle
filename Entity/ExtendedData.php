<?php

namespace Bundle\PaymentBundle\Entity;

use Bundle\PaymentBundle\Model\ExtendedDataInterface;

class ExtendedData implements ExtendedDataInterface
{
    protected $data;
    
    public function __construct()
    {
        $this->data = array();
    }
    
    public function remove($name)
    {
        unset($this->data[$name]);
    }
    
    public function isEncryptionRequired($name)
    {
        if (!isset($this->data[$name])) {
            throw new \InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }
        
        return $this->data[$name][1];
    }
    
    public function set($name, $value, $encrypt = true)
    {
        $this->data[$name] = array($value, $encrypt);
    }
    
    public function get($name)
    {
        if (!isset($this->data[$name])) {
            throw new \InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }
        
        return $this->data[$name][0];
    }
    
    public function has($name)
    {
        return isset($this->data[$name]);
    }
}