<?php

namespace JMS\Payment\CoreBundle\Form;

use JMS\Payment\CoreBundle\Form\Transformer\ChoosePaymentMethodTransformer;
use JMS\Payment\CoreBundle\PluginController\PluginControllerInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Util\Legacy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form Type for Choosing a Payment Method.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ChoosePaymentMethodType extends AbstractType
{
    private $pluginController;
    private $paymentMethods;
    private $transformer;

    public function __construct(PluginControllerInterface $pluginController, array $paymentMethods)
    {
        if (!$paymentMethods) {
            throw new \InvalidArgumentException('There is no payment method available. Did you forget to register concrete payment provider bundles such as JMSPaymentPaypalBundle?');
        }

        $this->pluginController = $pluginController;
        $this->paymentMethods = $paymentMethods;
    }

    public function setDataTransformer(DataTransformerInterface $transformer)
    {
        $this->transformer = $transformer;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['available_methods'] = $this->getPaymentMethods($options['allowed_methods']);

        $this->buildChoiceList($builder, $options);

        foreach ($options['available_methods'] as $method => $class) {
            $methodOptions = isset($options['method_options'][$method]) ? $options['method_options'][$method] : array();
            $builder->add('data_'.$method, $class, $methodOptions);
        }

        $self = $this;
        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($form) use ($self, $options) {
            $self->validate($form, $options);
        });

        // To maintain BC, we instantiate a new ChoosePaymentMethodTransformer in
        // case it hasn't been supplied.
        $transformer = $this->transformer
            ? $this->transformer
            : new ChoosePaymentMethodTransformer()
        ;

        $transformer->setOptions($options);
        $builder->addModelTransformer($transformer);
    }

    protected function buildChoiceList(FormBuilderInterface $builder, array $options)
    {
        $methods = $options['available_methods'];
        $choiceOptions = $options['choice_options'];

        $options = array_merge(array(
            'expanded' => true,
            'data' => $options['default_method'],
        ), $options);

        // Remove unwanted options
        $options = array_intersect_key($options, array_flip(array(
            'expanded',
            'data',
        )));

        $options = array_merge($options, $choiceOptions);

        $options['choices'] = array();
        foreach (array_keys($methods) as $method) {
            $label = 'form.label.'.$method;

            if (Legacy::formChoicesAsValues()) {
                $options['choices'][$method] = $label;
            } else {
                $options['choices'][$label] = $method;
            }
        }

        $type = Legacy::supportsFormTypeName()
            ? 'choice'
            : 'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
        ;

        $builder->add('method', $type, $options);
    }

    public function validate(FormEvent $event, array $options)
    {
        $form = $event->getForm();
        $instruction = $form->getData();

        if (null === $instruction->getPaymentSystemName()) {
            $form->addError(new FormError('form.error.payment_method_required'));

            return;
        }

        if (!array_key_exists($instruction->getPaymentSystemName(), $options['available_methods'])) {
            $form->addError(new FormError('form.error.invalid_payment_method'));

            return;
        }

        $result = $this->pluginController->checkPaymentInstruction($instruction);
        if (Result::STATUS_SUCCESS !== $result->getStatus()) {
            $this->applyErrorsToForm($form, $result);

            return;
        }

        $result = $this->pluginController->validatePaymentInstruction($instruction);
        if (Result::STATUS_SUCCESS !== $result->getStatus()) {
            $this->applyErrorsToForm($form, $result);
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(array(
            'amount',
            'currency',
        ));

        $resolver->setDefaults(array(
            'predefined_data' => array(),
            'allowed_methods' => array(),
            'default_method'  => null,
            'method_options'  => array(),
            'choice_options'  => array(),
        ));

        $allowedTypes = array(
            'amount'          => array('numeric', 'closure'),
            'currency'        => 'string',
            'predefined_data' => 'array',
            'allowed_methods' => 'array',
            'default_method'  => array('null', 'string'),
            'method_options'  => 'array',
            'choice_options'  => 'array',
        );

        if (Legacy::supportsFormTypeConfigureOptions()) {
            $resolver->setAllowedTypes($allowedTypes);
        } else {
            foreach ($allowedTypes as $key => $value) {
                $resolver->addAllowedTypes($key, $value);
            }
        }
    }

    public function getBlockPrefix()
    {
        return 'jms_choose_payment_method';
    }

    /**
     * Legacy support for Symfony < 3.0.
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $this->configureOptions($resolver);
    }

    /**
     * Legacy support for Symfony < 3.0.
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    private function applyErrorsToForm(FormInterface $form, Result $result)
    {
        $ex = $result->getPluginException();

        $globalErrors = $ex->getGlobalErrors();
        $dataErrors = $ex->getDataErrors();

        // add a generic error message
        if (!$dataErrors && !$globalErrors) {
            $form->addError(new FormError('form.error.invalid_payment_instruction'));

            return;
        }

        foreach ($globalErrors as $error) {
            $form->addError(new FormError($error));
        }

        foreach ($dataErrors as $path => $error) {
            $path = explode('.', $path);
            $field = $form;
            do {
                $field = $field->get(array_shift($path));
            } while ($path);

            $field->addError(new FormError($error));
        }
    }

    private function getPaymentMethods($allowedMethods)
    {
        $allowAllMethods = empty($allowedMethods);
        $availableMethods = array();

        foreach ($this->paymentMethods as $methodKey => $methodClass) {
            if (!$allowAllMethods && !in_array($methodKey, $allowedMethods, true)) {
                continue;
            }

            $availableMethods[$methodKey] = $methodClass;
        }

        if (empty($availableMethods)) {
            throw new \RuntimeException(sprintf(
                'You have not selected any payment methods. Available methods: "%s"',
                implode(', ', $this->paymentMethods)
            ));
        }

        return $availableMethods;
    }
}
