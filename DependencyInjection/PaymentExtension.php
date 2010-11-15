<?php

namespace Bundle\PaymentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

class PaymentExtension extends Extension
{
	public function configLoad(array $config, ContainerBuilder $container)
	{
		$xmlLoader = new XmlFileLoader($container, __DIR__.'/../Resources/config');
		$xmlLoader->load('payment.xml');
		$xmlLoader->load('orm.xml');
		
		if (isset($config['plugins'])) {
		    $controller = $container->getDefinition('payment.plugin_controller');
		    
		    foreach ((array) $config['plugins'] as $pluginName) {
		        $controller->addMethodCall('addPlugin', array(new Reference($this->getPluginId($pluginName))));
		    }
		}
	}
	
	protected function getPluginId($name)
	{
	    return 'payment.plugin.'.$name;
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