<?php

namespace JMS\Payment\CoreBundle;

use JMS\Payment\CoreBundle\DependencyInjection\Compiler\AddPaymentMethodFormTypesPass;
use JMS\Payment\CoreBundle\DependencyInjection\Compiler\AddPaymentPluginsPass;
use JMS\Payment\CoreBundle\DependencyInjection\Compiler\ConfigureEncryptionPass;
use JMS\Payment\CoreBundle\DependencyInjection\Compiler\LegacyEncryptionPass;
use JMS\Payment\CoreBundle\Entity\ExtendedDataType;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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

class JMSPaymentCoreBundle extends Bundle
{
    public function boot()
    {
        if ($this->container->has('payment.encryption')) {
            ExtendedDataType::setEncryptionService($this->container->get('payment.encryption'));
        }
    }

    public function build(ContainerBuilder $builder)
    {
        parent::build($builder);

        $builder->addCompilerPass(new AddPaymentPluginsPass());
        $builder->addCompilerPass(new AddPaymentMethodFormTypesPass());
        $builder->addCompilerPass(new LegacyEncryptionPass());
        $builder->addCompilerPass(new ConfigureEncryptionPass());
    }
}
