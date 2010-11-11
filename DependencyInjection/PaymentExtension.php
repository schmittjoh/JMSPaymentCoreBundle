<?php

namespace Bundle\PaymentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

use Symfony\Component\DependencyInjection\Extension\Extension;

class PaymentExtension extends Extension
{
	public function configLoad(array $config, ContainerBuilder $container)
	{
		$xmlLoader = new XmlFileLoader($container, __DIR__.'/../Resources/config');
		$xmlLoader->load('orm.xml');
	}
	
	public function getNamespace()
	{
		
	}
	
	public function getXsdValidationBasePath()
	{
		
	}
	
	public function getAlias()
	{
		return 'payment';
	}
}