<?php

namespace JMS\Payment\CoreBundle\DependencyInjection\Compiler;

use JMS\Payment\CoreBundle\Util\Legacy;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Wires payment method types.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AddPaymentMethodFormTypesPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition('payment.form.choose_payment_method_type')) {
            return;
        }

        $paymentMethodFormTypes = array();
        foreach ($container->findTaggedServiceIds('payment.method_form_type') as $id => $attrs) {
            $definition = $container->getDefinition($id);

            // check that this definition is also registered as a form type
            $attrs = $definition->getTag('form.type');
            if (!$attrs) {
                throw new \RuntimeException(sprintf('The service "%s" is marked as payment method form type (tagged with "payment.method_form_type"), but is not registered as a form type with the Form Component. Please also add a "form.type" tag.', $id));
            }

            if (!isset($attrs[0]['alias'])) {
                throw new \RuntimeException(sprintf('Please define an alias attribute for tag "form.type" of service "%s".', $id));
            }

            $paymentMethodFormTypes[$attrs[0]['alias']] = Legacy::supportsFormTypeName()
                ? $attrs[0]['alias']
                : $definition->getClass()
            ;
        }

        $container->getDefinition('payment.form.choose_payment_method_type')
            ->addArgument($paymentMethodFormTypes);
    }
}
