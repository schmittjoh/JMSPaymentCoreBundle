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
    public function getConfigTreeBuilder()
    {
        $tb = new TreeBuilder();

        return $tb
            ->root('jms_payment_core', 'array')
                ->children()
                    ->scalarNode('secret')->isRequired()->cannotBeEmpty()->end()
                    ->scalarNode('orm')
                        ->defaultValue('entity')
                        ->validate()
                            ->ifNotInArray(array('propel', 'entity'))
                            ->thenInvalid('Invalid orm "%s"')
                        ->end()
                    ->end()
                ->end()
            ->end();
    }
}