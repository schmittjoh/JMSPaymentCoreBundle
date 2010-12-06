<?php

namespace Bundle\PaymentBundle\Plugin;

use Bundle\PaymentBundle\BrowserKit\Request;
use Bundle\PaymentBundle\Plugin\Exception\CommunicationException;
use Symfony\Component\BrowserKit\Response;

/**
 * This plugin was specifically designed as a base class for plugins that
 * connect to an external payment backend system.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class GatewayPlugin extends Plugin
{
    protected $curlOptions;
    
    public function __construct($isDebug)
    {
        parent::__construct($isDebug);
        
        $this->curlOptions = array();
    }
    
    public function setCurlOption($name, $value)
    {
        $this->curlOptions[$name] = $value;
    }
    
    /**
     * A small helper to url-encode an array
     * 
     * @param array $encode
     * @return string
     */
    protected function urlEncodeArray(array $encode)
    {
        $encoded = '';
        foreach ($encode as $name => $value) {
            $encoded .= '&'.urlencode($name).'='.urlencode($value);
        }
        
        return substr($encoded, 1);
    }
    
    /**
     * Performs a request to an external payment service
     * 
     * @throws CommunicationException when an curl error occurs
     * @param Request $request
     * @param mixed $parameters either an array for form-data, or an url-encoded string
     * @return Response
     */
    public function request(Request $request)
    {
        if (!extension_loaded('curl')) {
            throw new \RuntimeException('The cURL extension must be loaded.');
        }
        
        $curl = curl_init();
        curl_setopt_array($curl, $this->curlOptions);
        curl_setopt($curl, CURLOPT_URL, $request->getUri());
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HEADER, true);
        
        // add headers
        $headers = array();
        foreach ($request->headers->all() as $name => $value) {
            if (is_array($value)) {
                foreach ($value as $subValue) {
                    $headers[] = sprintf('%s: %s', $name, $subValue);
                }
            }
            else {
                $headers[] = sprintf('%s: %s', $name, $value);
            }
        }
        if (count($headers) > 0) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        
        // set method
        $method = strtoupper($request->getMethod());
        if ('POST' === $method) {
            curl_setopt($curl, CURLOPT_POST, true);
            
            if (!$request->headers->has('Content-Type') || 'multipart/form-data' !== $request->headers->get('Content-Type')) {
                $postFields = $this->urlEncodeArray($request->request->all());
            }
            else {
                $postFields = $request->request->all();
            }
            
            curl_setopt($curl, CURLOPT_POSTFIELDS, $postFields);
        }
        else if ('PUT' === $method) {
            curl_setopt($curl, CURLOPT_PUT, true);
        }
        else if ('HEAD' === $method) {
            curl_setopt($curl, CURLOPT_NOBODY, true);
        }
        
        // perform the request
        if (false === $returnTransfer = curl_exec($curl)) {
            throw new CommunicationException(
                'cURL Error: '.curl_error($curl), curl_errno($curl)
            );
        }
        
        $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $headers = array();
        if (preg_match_all('#^([^:\r\n]+):\s+([^\n\r]+)#m', substr($returnTransfer, 0, $headerSize), $matches)) {
            foreach ($matches[1] as $key => $name) {
                $headers[$name] = $matches[2][$key];
            }
        }
        
        $response = new Response(
            substr($returnTransfer, $headerSize),
            curl_getinfo($curl, CURLINFO_HTTP_CODE),
            $headers
        );
        curl_close($curl);
        
        return $response;
    }
}