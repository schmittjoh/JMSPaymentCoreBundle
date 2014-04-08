<?php

namespace JMS\Payment\CoreBundle\Form;

use JMS\Payment\CoreBundle\PluginController\PluginControllerInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
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

    public function __construct(DataTransformerInterface $transformer, PluginControllerInterface $pluginController, array $paymentMethods)
    {
        if (!$paymentMethods) {
            throw new \InvalidArgumentException('There is no payment method available. Did you forget to register concrete payment provider bundles such as JMSPaymentPaypalBundle?');
        }

        $this->transformer = $transformer;
        $this->pluginController = $pluginController;
        $this->paymentMethods = $paymentMethods;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $allowAllMethods = !count($options['allowed_methods']);

        $options['available_methods'] = array();
        foreach ($this->paymentMethods as $method) {
            if (!$allowAllMethods && !in_array($method, $options['allowed_methods'], true)) {
                continue;
            }

            $options['available_methods'][] = $method;
        }

        if (!$options['available_methods']) {
            throw new \RuntimeException(sprintf('You have not selected any payment methods. Available methods: "%s"', implode(', ', $this->paymentMethods)));
        }

        $builder->add('method', 'choice', array(
            'choices' => $this->buildChoices($options['available_methods']),
            'expanded' => true,
            'data' => $options['default_method'],
        ));

        foreach ($options['available_methods'] as $method) {
            $methodOptions = isset($options['method_options'][$method]) ? $options['method_options'] : array();
            $builder->add('data_'.$method, $method, $methodOptions);
        }

        $self = $this;
        $builder->addEventListener(FormEvents::POST_BIND, function($form) use ($self, $options) {
            $self->validate($form, $options);
        });

        $self->transformer->setOptions($options);

        $builder->addModelTransformer($self->transformer, true);
    }

    public function validate(FormEvent $event, array $options)
    {
        $form        = $event->getForm();
        $instruction = $form->getData();

        if (null === $instruction->getPaymentSystemName()) {
            $form->addError(new FormError('form.error.payment_method_required'));

            return;
        }
        if (!in_array($instruction->getPaymentSystemName(), $options['available_methods'], true)) {
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

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'allowed_methods' => array(),
            'default_method'  => null,
            'predefined_data' => array(),
        ));

        $resolver->setRequired(array(
            'amount',
            'currency',
        ));

        $resolver->setAllowedTypes(array(
            'allowed_methods' => 'array',
            'amount'          => array('numeric', 'closure'),
            'currency'        => 'string',
            'predefined_data' => 'array',
        ));
    }

    public function getName()
    {
        return 'jms_choose_payment_method';
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

    private function buildChoices(array $methods)
    {
        $choices = array();
        foreach ($methods as $method) {
            $choices[$method] = 'form.label.'.$method;
        }

        return $choices;
    }
}
