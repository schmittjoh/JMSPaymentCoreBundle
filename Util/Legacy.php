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
     * Symfony 3.0 removes support for form type names.
     *
     * Instead of referencing types by name, they must be referenced by their
     * fully-qualified class name (FQCN).
     */
    public static function supportsFormTypeName()
    {
        return version_compare(Kernel::VERSION, '3.0.0', '<');
    }

    /**
     * Symfony 3.0 requires using `configureOptions` instead of `setDefaultOptions`
     * in form types.
     */
    public static function supportsFormTypeConfigureOptions()
    {
        return version_compare(Kernel::VERSION, '3.0.0', '<');
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
        return version_compare(Kernel::VERSION, '3.0.0', '<');
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
