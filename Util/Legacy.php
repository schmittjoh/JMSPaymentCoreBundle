<?php

namespace JMS\Payment\CoreBundle\Util;

use Symfony\Component\HttpKernel\Kernel;

/**
 * @internal This class is meant for internal use and will not be subject to
 * semantic versioning. Do not use in code outside this bundle
 */
class Legacy
{
    /**
     * Symfony 2.8 adds support for form type as FQCN.
     *
     * Instead of referencing types by name, they should be referenced by their
     * fully-qualified class name (FQCN).
     */
    public static function supportsFormTypeClass()
    {
        return method_exists(
            'Symfony\Component\Form\AbstractType',
            'getBlockPrefix'
        );
    }

    /**
     * Before Symfony 2.6, setAllowedTypes() and addAllowedTypes() expected the
     * values to be given as an array mapping option names to allowed types:
     * $resolver->setAllowedTypes(array('port' => array('null', 'int')));
     */
    public static function supportsOptionsResolverSetAllowedTypesAsArray()
    {
        return !method_exists(
            'Symfony\Component\OptionsResolver\OptionsResolver',
            'setDefined'
        );
    }

    /**
     * Symfony 2.7 introduced the `choices_as_values` option to keep backward
     * compatibility with the old way of handling the choices option. When set
     * to false (or omitted), the choice keys are used as the underlying value
     * and the choice values are shown to the user.
     *
     * In Symfony 3.0, the `choices_as_values` option doesn't exist, but the
     * choice type behaves as if it were set to true.
     *
     * See http://symfony.com/doc/2.7/reference/forms/types/choice.html#choices-as-values
     */
    public static function formChoicesAsValues()
    {
        return method_exists(
            'Symfony\Component\Form\AbstractType',
            'configureOptions'
        );
    }

    /**
     * When using `choices_as_values` before Symfony 3.0, one must make sure to
     * set the `choices_as_values` option to true
     */
    public static function needsChoicesAsValuesOption()
    {
        return self::formChoicesAsValues() && method_exists(
            'Symfony\Component\Form\FormTypeInterface',
            'setDefaultOptions'
        );
    }

    /**
     * Symfony 3.0 removes Symfony\Component\Security\Core\Util\SecureRandom.
     */
    public static function supportsSecureRandom()
    {
        return class_exists('Symfony\Component\Security\Core\Util\SecureRandom');
    }

    /**
     * Symfony 3.0 moves ExecutionContext
     * from Symfony\Component\Validator\ExecutionContext
     * to Symfony\Component\Validator\Context\ExecutionContext.
     */
    public static function isOldPathExecutionContext()
    {
        return !class_exists('Symfony\Component\Validator\Context\ExecutionContext');
    }

    /**
     * Symfony 2.4 introduced the RequestStack service to replace the Request service,
     * which is removed in 3.0.
     */
    public static function supportsRequestService()
    {
        return !class_exists('Symfony\Component\HttpFoundation\RequestStack');
    }
}
