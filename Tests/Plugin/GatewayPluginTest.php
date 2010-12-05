<?php

namespace Bundle\PaymentBundle\Tests\Plugin;

use Bundle\PaymentBundle\BrowserKit\Request;

class GatewayPluginTest extends \PHPUnit_Framework_TestCase
{
    public function testRequest()
    {
        if (!extension_loaded('curl')) {
            $this->markTestSkipped('cURL is not loaded.');
        }
        
        $plugin = $this->getPlugin();
        
        // not sure if there is a better approach to testing this
        $request = new Request('https://github.com/schmittjoh/PaymentBundle/raw/master/Tests/Plugin/Fixtures/sampleResponse', 'GET');
        $response = $plugin->request($request);
        
        $this->assertEquals(file_get_contents(__DIR__.'/Fixtures/sampleResponse'), $response->getContent());
        $this->assertEquals(200, $response->getStatus());
        $this->assertEquals('200 OK', $response->getHeader('Status'));
    }
    
    protected function getPlugin()
    {
        return $this->getMockForAbstractClass('Bundle\PaymentBundle\Plugin\GatewayPlugin', array(true));
    }
}