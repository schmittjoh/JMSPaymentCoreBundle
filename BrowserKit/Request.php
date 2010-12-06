<?php

namespace Bundle\PaymentBundle\BrowserKit;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;

class Request
{
    protected $method;
    protected $uri;
    public $request;
    public $headers;
    
    public function __construct($uri, $method, array $request = array(), array $headers = array())
    {
        $this->uri = $uri;
        $this->method = $method;
        $this->request = new ParameterBag($request);
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