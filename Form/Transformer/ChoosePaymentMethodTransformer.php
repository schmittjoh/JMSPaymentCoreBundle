<?php

namespace JMS\Payment\CoreBundle\Form\Transformer;

use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;

class ChoosePaymentMethodTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    protected $options;

    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function transform($data)
    {
        if (null === $data) {
            return null;
        }

        if (!$data instanceof PaymentInstruction) {
            throw new TransformationFailedException(sprintf('Unsupported data of type "%s".', $this->getDataType($data)));
        }

        $method = $data->getPaymentSystemName();

        $methodData = array_map(function ($v) {
            return $v[0];
        }, $data->getExtendedData()->all());

        if (isset($this->options['predefined_data'][$method])) {
            $methodData = array_diff_key($methodData, $this->options['predefined_data'][$method]);
        }

        return array(
            'method'        => $method,
            'data_'.$method => $methodData,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function reverseTransform($data)
    {
        if (null === $data) {
            return null;
        }

        if (!is_array($data)) {
            throw new TransformationFailedException(sprintf('Unsupported data of type "%s".', $this->getDataType($data)));
        }

        if (!isset($this->options['amount'])) {
            throw new TransformationFailedException("The 'amount' option must be supplied to the form");
        }

        if (!isset($this->options['currency'])) {
            throw new TransformationFailedException("The 'currency' option must be supplied to the form");
        }

        $method = isset($data['method']) ? $data['method'] : null;

        if (isset($this->options['predefined_data'][$method])) {
            if (!is_array($this->options['predefined_data'][$method])) {
                throw new TransformationFailedException(sprintf('"predefined_data" is expected to be an array for each method, but got "%s" for method "%s".', $this->getDataType($this->options['predefined_data'][$method]), $method));
            }
        }

        $data = isset($data['data_'.$method]) ? $data['data_'.$method] : array();
        $extendedData = new ExtendedData();
        foreach ($data as $key => $value) {
            $extendedData->set($key, $value);
        }

        if (isset($this->options['predefined_data'][$method])) {
            foreach ($this->options['predefined_data'][$method] as $key => $value) {
                $extendedData->set($key, $value);
            }
        }

        $amount = $this->options['amount'];
        if ($amount instanceof \Closure) {
            $amount = $amount($this->options['currency'], $method, $extendedData);
        }

        return new PaymentInstruction($amount, $this->options['currency'], $method, $extendedData);
    }

    private function getDataType($data)
    {
        $type = gettype($data);

        if ($type === 'object') {
            $type = get_class($data);
        }

        return $type;
    }
}
