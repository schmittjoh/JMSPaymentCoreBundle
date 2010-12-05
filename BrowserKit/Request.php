<?php

namespace Bundle\PaymentBundle\BrowserKit;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

class Request
{
    protected $method;
    protected $uri;
    public $request;
    public $headers;
    
    public function __construct($uri, $method, array $request = array(), array $headers = array())
    {
        $this->method = $method;
        $this->uri = $uri;
        $this->request = new ParameterBag($parameters);
        $this->headers = new HeaderBag($headers);
    }
    
    public function getMethod()
    {
        return $this->method;
    }
    
    public function getUri()
    {
        return $this->uri;
    }
}