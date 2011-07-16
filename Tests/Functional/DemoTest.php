<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

class DemoTest extends BaseTestCase
{
	public function testCreatePayment()
	{
	    $client = $this->createClient();
		$this->importDatabaseSchema();

        $client->request('GET', '/payment');
        $response = $client->getResponse();

        $this->assertEquals(302, $response->getStatusCode());
	}
}