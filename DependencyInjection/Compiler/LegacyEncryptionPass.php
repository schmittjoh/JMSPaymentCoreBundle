<?php

namespace JMS\Payment\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The service `payment.encryption_service` has been deprecated in favor of
 * `payment.encryption.mcrypt`. This compiler pass makes sure parameters specified
 * for `payment.encryption_service` are instead set for `payment.encryption.mcrypt`.
 *
 * @deprecated 1.3 Will be removed in 2.0
 */
class LegacyEncryptionPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->has('payment.encryption_service')) {
            return;
        }

        if (!$container->has('payment.encryption.mcrypt')) {
            return;
        }

        $parameters = array(
            'class'  => 'JMS\Payment\CoreBundle\Cryptography\MCryptEncryptionService',
            'secret' => '',
            'cipher' => 'rijndael-256',
            'mode'   => 'ctr',
        );

        foreach ($parameters as $parameter => $defaultValue) {
            if (!$container->hasParameter('payment.encryption_service.'.$parameter)) {
                continue;
            }

            if (!$container->hasParameter('payment.encryption.mcrypt.'.$parameter)) {
                continue;
            }

            $legacyValue = $container->getParameter('payment.encryption_service.'.$parameter);
            $modernValue = $container->getParameter('payment.encryption.mcrypt.'.$parameter);

            // Parameters set for payment.encryption.mcrypt take precedence over
            // ones set for payment.encryption_service
            if ($modernValue !== $defaultValue) {
                $container->setParameter('payment.encryption.mcrypt.'.$parameter, $modernValue);
            } elseif ($legacyValue !== $defaultValue) {
                $container->setParameter('payment.encryption.mcrypt.'.$parameter, $legacyValue);
                @trigger_error('payment.encryption_service.'.$parameter.' has been deprecated in favor of payment.encryption.mcrypt.'.$parameter.' and will be removed in 2.0', E_USER_DEPRECATED);
            }
        }
    }
}
