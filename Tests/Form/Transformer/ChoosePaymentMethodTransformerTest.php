<?php

namespace JMS\Payment\CoreBundle\Tests\Form\Transformer;

use JMS\Payment\CoreBundle\Entity\ExtendedData;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Form\Transformer\ChoosePaymentMethodTransformer;

class ChoosePaymentMethodTransformerTest extends \PHPUnit_Framework_TestCase
{
    public function testTransformNullData()
    {
        $this->assertNull($this->transform(null));
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage JMS\Payment\CoreBundle\Tests\Form\Transformer\ChoosePaymentMethodTransformerTest
     */
    public function testTransformNotPaymentInstructionObject()
    {
        $this->transform(new self());
    }

    public function testTransform()
    {
        $method = 'foo';
        $data = new ExtendedData();
        $data->set('bar', 'baz');

        $transformed = $this->transform(new PaymentInstruction('10.42', 'EUR', $method, $data));

        $this->assertArraySubset(array(
            'method' => 'foo',
            'data_foo' => array(
                'bar' => 'baz',
            ),
        ), $transformed);
    }

    public function testTransformPredefinedData()
    {
        $method = 'foo';
        $data = new ExtendedData();
        $data->set('bar', 'baz');

        $options = array(
            'predefined_data' => array(
                $method => array(
                    'bar' => 'bar_predefined',
                ),
            ),
        );

        $transformed = $this->transform(new PaymentInstruction('10.42', 'EUR', $method, $data), $options);

        $this->assertArrayNotHasKey('bar', $transformed['data_foo']);
    }

    public function testReverseTransformNullData()
    {
        $this->assertNull($this->reverseTransform(null));
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage JMS\Payment\CoreBundle\Tests\Form\Transformer\ChoosePaymentMethodTransformerTest
     */
    public function testReverseTransformNotArray()
    {
        $this->reverseTransform(new self());
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The 'amount' option must be supplied to the form
     */
    public function testReverseTransformNoAmount()
    {
        $this->reverseTransform(array());
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage The 'currency' option must be supplied to the form
     */
    public function testReverseTransformNoCurrency()
    {
        $this->reverseTransform(array(), array('amount' => '10.42'));
    }

    public function testReverseTransform()
    {
        $options = array(
            'currency' => 'EUR',
            'amount' => '10.42',
        );

        $pi = $this->reverseTransform(array('method' => 'foo'), $options);

        $this->assertInstanceOf('JMS\Payment\CoreBundle\Entity\PaymentInstruction', $pi);
        $this->assertEquals('foo', $pi->getPaymentSystemName());
        $this->assertEquals('10.42', $pi->getAmount());
        $this->assertEquals('EUR', $pi->getCurrency());
    }

    public function testReverseTransformAmountClosure()
    {
        $options = array(
            'currency' => 'EUR',
            'amount' => function () {
                return '10.42';
            },
        );

        $pi = $this->reverseTransform(array(), $options);

        $this->assertEquals('10.42', $pi->getAmount());
    }

    /**
     * @expectedException Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage JMS\Payment\CoreBundle\Tests\Form\Transformer\ChoosePaymentMethodTransformerTest
     */
    public function testReverseTransformPredefinedDataWrongType()
    {
        $options = array(
            'currency' => 'EUR',
            'amount' => '10.42',
            'predefined_data' => array(
                'foo' => new self(),
            ),
        );

        $pi = $this->reverseTransform(array('method' => 'foo'), $options);
    }

    public function testReverseTransformPredefinedData()
    {
        $options = array(
            'currency' => 'EUR',
            'amount' => '10.42',
            'predefined_data' => array(
                'foo' => array(
                    'bar' => 'baz',
                ),
            ),
        );

        $pi = $this->reverseTransform(array('method' => 'foo'), $options);

        $this->assertEquals('baz', $pi->getExtendedData()->get('bar'));
    }

    private function transform($instruction, $options = array())
    {
        $transformer = new ChoosePaymentMethodTransformer();
        $transformer->setOptions($options);

        return $transformer->transform($instruction);
    }

    private function reverseTransform($data, $options = array())
    {
        $transformer = new ChoosePaymentMethodTransformer();
        $transformer->setOptions($options);

        return $transformer->reverseTransform($data);
    }
}
