<?php

namespace JMS\Payment\CoreBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;

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
        foreach ($container->findTaggedServiceIds('payment.method_form_type') as $id => $attributes) {
            $definition = $container->getDefinition($id);

            // check that this definition is also registered as a form type
            $attributes = $definition->getTag('form.type');
            if (!$attributes) {
                throw new \RuntimeException(sprintf('The service "%s" is marked as payment method form type (tagged with "payment.method_form_type"), but is not registered as a form type with the Form Component. Please also add a "form.type" tag.', $id));
            }

            if (!isset($attributes[0]['alias'])) {
                throw new \RuntimeException(sprintf('Please define an alias attribute for tag "form.type" of service "%s".', $id));
            }

            $paymentMethodFormTypes[] = $attributes[0]['alias'];
        }

        $container->getDefinition('payment.form.choose_payment_method_type')
            ->addArgument($paymentMethodFormTypes);
    }
}