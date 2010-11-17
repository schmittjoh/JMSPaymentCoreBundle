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

		if (isset($config['secret'])) {
		    $container->setParameter('payment.encryption_service.secret', $config['secret']);
		}
		
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
		return 'http://www.symfony-project.org/schema/dic/payment';
	}
	
	public function getXsdValidationBasePath()
	{
		return __DIR__.'/../Resources/config/schema';
	}
	
	public function getAlias()
	{
		return 'payment';
	}
}