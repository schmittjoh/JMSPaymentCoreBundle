<?php

namespace JMS\Payment\CoreBundle\Form;

use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\PluginController\PluginControllerInterface;
use JMS\Payment\CoreBundle\PluginController\Result;
use JMS\Payment\CoreBundle\Util\Legacy;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
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

    public function __construct(PluginControllerInterface $pluginController, array $paymentMethods)
    {
        if (!$paymentMethods) {
            throw new \InvalidArgumentException('There is no payment method available. Did you forget to register concrete payment provider bundles such as JMSPaymentPaypalBundle?');
        }

        $this->pluginController = $pluginController;
        $this->paymentMethods = $paymentMethods;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options['available_methods'] = $this->getPaymentMethods($options['allowed_methods']);

        $choiceType = Legacy::supportsFormTypeName()
            ? 'choice'
            : 'Symfony\Component\Form\Extension\Core\Type\ChoiceType'
        ;

        $builderOptions = array(
            'expanded' => true,
            'data'     => $options['default_method'],
        );

        $builderOptions['choices'] = array();
        foreach ($options['available_methods'] as $methodKey => $methodClass) {
            $label = 'form.label.'.$methodKey;

            if (Legacy::formChoicesAsValues()) {
                $builderOptions['choices'][$methodKey] = $label;
            } else {
                $builderOptions['choices'][$label] = $methodKey;
            }
        }

        $builder->add('method', $choiceType, $builderOptions);

        foreach ($options['available_methods'] as $methodKey => $methodClass) {
            $methodOptions = isset($options['method_options'][$methodKey]) ? $options['method_options'] : array();
            $builder->add('data_'.$methodKey, $methodClass, $methodOptions);
        }

        $self = $this;
        $builder->addEventListener(FormEvents::POST_SUBMIT, function ($form) use ($self, $options) {
            $self->validate($form, $options);
        });

        $builder->addModelTransformer(new CallbackTransformer(
            function ($data) use ($self, $options) {
                return $self->transform($data, $options);
            },
            function ($data) use ($self, $options) {
                return $self->reverseTransform($data, $options);
            }
        ), true);
    }

    public function transform($data, array $options)
    {
        if (null === $data) {
            return null;
        }

        if ($data instanceof PaymentInstruction) {
            $method = $data->getPaymentSystemName();

            $methodData = array_map(function ($v) {
                return $v[0];
            }, $data->getExtendedData()->all());

            if (isset($options['predefined_data'][$method])) {
                $methodData = array_diff_key($methodData, $options['predefined_data'][$method]);
            }

            return array(
                'method'        => $method,
                'data_'.$method => $methodData,
            );
        }

        throw new \RuntimeException(sprintf('Unsupported data of type "%s".', ('object' === $type = gettype($data)) ? get_class($data) : $type));
    }

    public function reverseTransform($data, array $options)
    {
        $method = isset($data['method']) ? $data['method'] : null;
        $data = isset($data['data_'.$method]) ? $data['data_'.$method] : array();

        $extendedData = new ExtendedData();
        foreach ($data as $k => $v) {
            $extendedData->set($k, $v);
        }

        if (isset($options['predefined_data'][$method])) {
            if (!is_array($options['predefined_data'][$method])) {
                throw new \RuntimeException(sprintf('"predefined_data" is expected to be an array for each method, but got "%s" for method "%s".', json_encode($options['extra_data'][$method]), $method));
            }

            foreach ($options['predefined_data'][$method] as $k => $v) {
                $extendedData->set($k, $v);
            }
        }

        $amount = $this->computeAmount($options['amount'], $options['currency'], $method, $extendedData);

        return new PaymentInstruction($amount, $options['currency'], $method, $extendedData);
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
        $resolver->setDefaults(array(
            'allowed_methods' => array(),
            'default_method'  => null,
            'predefined_data' => array(),
        ));

        $resolver->setRequired(array(
            'amount',
            'currency',
        ));

        if (Legacy::supportsFormTypeConfigureOptions()) {
            $resolver->setAllowedTypes(array(
                'allowed_methods' => 'array',
                'amount'          => array('numeric', 'closure'),
                'currency'        => 'string',
                'predefined_data' => 'array',
            ));
        } else {
            $resolver
                ->setAllowedTypes('allowed_methods', 'array')
                ->setAllowedTypes('amount', array('numeric', 'closure'))
                ->setAllowedTypes('currency', 'string')
                ->setAllowedTypes('predefined_data', 'array')
            ;
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

    private function computeAmount($amount, $currency, $method, ExtendedData $extendedData)
    {
        if ($amount instanceof \Closure) {
            return $amount($currency, $method, $extendedData);
        }

        return $amount;
    }

    private function getPaymentMethods($allowedMethods = array())
    {
        $allowAllMethods = !count($allowedMethods);
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
