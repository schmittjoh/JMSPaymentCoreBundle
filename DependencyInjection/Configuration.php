<?php

namespace JMS\Payment\CoreBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

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

class Configuration implements ConfigurationInterface
{
    private $alias;

    public function __construct($alias)
    {
        $this->alias = $alias;
    }

    public function getConfigTreeBuilder()
    {
        $builder = new TreeBuilder();

        $builder->root($this->alias, 'array')
            ->children()
                ->arrayNode('encryption')
                    ->canBeEnabled()
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('provider')->defaultValue('defuse_php_encryption')->end()
                        ->scalarNode('secret')->cannotBeEmpty()->end()
                    ->end()
                    ->validate()
                        ->ifTrue(function ($config) {
                            return $config['enabled'] && !array_key_exists('secret', $config);
                        })
                        ->thenInvalid('An encryption secret is required')
                    ->end()
                ->end()
                ->scalarNode('secret')
                    ->cannotBeEmpty()
                    ->info($this->getSecretDeprecationMessage())
                ->end()
            ->end()
            ->beforeNormalization()
                ->ifTrue(function ($config) {
                    return !empty($config['secret']);
                })
                ->then(function ($config) {
                    @trigger_error($this->getSecretDeprecationMessage(), E_USER_DEPRECATED);

                    $config['encryption'] = array(
                        'enabled'  => true,
                        'provider' => 'mcrypt',
                        'secret'   => $config['secret'],
                    );

                    return $config;
                })
            ->end()
        ;

        return $builder;
    }

    private function getSecretDeprecationMessage()
    {
        return "The 'secret' configuration option has been deprecated in favor of 'encryption.secret' and will be removed in 2.0. Please note that if you start using 'encryption.secret' you also need to set 'encryption.provider' to 'mcrypt' since mcrypt is not the default when using the 'encryption.*' options.";
    }
}
