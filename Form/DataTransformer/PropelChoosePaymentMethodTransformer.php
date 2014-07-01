<?php

namespace JMS\Payment\CoreBundle\Form\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;
use JMS\Payment\CoreBundle\Propel\ExtendedData;
use JMS\Payment\CoreBundle\Propel\PaymentInstruction;

class PropelChoosePaymentMethodTransformer implements DataTransformerInterface
{
    /**
     * @var array
     */
    protected $options;

    /**
     * @param array $options
     */
    public function setOptions(array $options)
    {
        $this->options = $options;
    }

    /**
     * @param PaymentInstruction $data
     *
     * @return null
     * @throws \RuntimeException
     */
    public function transform($data)
    {
        if (null === $data) {
            return null;
        }

        if ($data instanceof PaymentInstruction) {
            $method = $data->getPaymentSystemName();
            $methodData = array_map(function($v) { return $v[0]; }, $data->getExtendedData()->all());
            if (isset($this->options['predefined_data'][$method])) {
                $methodData = array_diff_key($methodData, $this->options['predefined_data'][$method]);
            }

            return array(
                'method'        => $method,
                'data_'.$method => $methodData,
            );
        }

        throw new \RuntimeException(sprintf('Unsupported data of type "%s".', ('object' === $type = gettype($data)) ? get_class($data) : $type));
    }

    /**
     * @param array $data
     *
     * @return \JMS\Payment\CoreBundle\Model\PaymentInstruction
     * @throws \RuntimeException
     */
    public function reverseTransform($data)
    {
        $method = isset($data['method']) ? $data['method'] : null;
        $data = isset($data['data_'.$method]) ? $data['data_'.$method] : array();

        $extendedData = new ExtendedData();
        foreach ($data as $k => $v) {
            $extendedData->set($k, $v);
        }

        if (isset($this->options['predefined_data'][$method])) {
            if (!is_array($this->options['predefined_data'][$method])) {
                throw new \RuntimeException(sprintf('"predefined_data" is expected to be an array for each method, but got "%s" for method "%s".', json_encode($this->options['extra_data'][$method]), $method));
            }

            foreach ($this->options['predefined_data'][$method] as $k => $v) {
                $extendedData->set($k, $v);
            }
        }

        $instruction = new PaymentInstruction(
            $this->options['amount'],
            $this->options['currency'],
            $method,
            $extendedData
        );

        return $instruction;
    }
}