<?php

namespace Bundle\PaymentBundle\Entity;

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
        if (!array_key_exists($name, $this->data)) {
            throw new \InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }
        
        return $this->data[$name][1];
    }
    
    public function set($name, $value, $encrypt = true)
    {
        $data = new \SplFixedArray(2);
        $data[0] = $value;
        $data[1] = $encrypt;
        
        $this->data[$name] = $data;
    }
    
    public function get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \InvalidArgumentException(sprintf('There is no data with key "%s".', $name));
        }
        
        return $this->data[$name][0];
    }
    
    public function has($name)
    {
        return array_key_exists($name, $this->data);
    }
}