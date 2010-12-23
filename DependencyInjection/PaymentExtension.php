<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

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
		    $controller = $container->findDefinition('payment.plugin_controller');

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