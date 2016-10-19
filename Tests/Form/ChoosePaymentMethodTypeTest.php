<?php

namespace JMS\Payment\CoreBundle\Tests\Form\ChoosePaymentMethodTypeTest;

use JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType;
use JMS\Payment\CoreBundle\Util\Legacy;
use JMS\Payment\PaypalBundle\Form\ExpressCheckoutType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\TypeTestCase;
use Symfony\Component\HttpKernel\Kernel;

class ChoosePaymentMethodTypeTest extends TypeTestCase
{
    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage amount
     */
    public function testAmountIsRequired()
    {
        $form = $this->createForm(array(
            'amount' => null,
        ));
    }

    /**
     * @expectedException Symfony\Component\OptionsResolver\Exception\InvalidOptionsException
     * @expectedExceptionMessage currency
     */
    public function testCurrencyIsRequired()
    {
        $form = $this->createForm(array(
            'currency' => null,
        ));
    }

    public function testMethod()
    {
        $form = $this->createForm();
        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->has('method'));
    }

    public function testMethodData()
    {
        $form = $this->createForm();

        foreach (array('foo', 'bar') as $method) {
            $this->assertTrue($form->has('data_'.$method));

            $config = $form->get('data_'.$method)->getConfig();

            $this->assertInstanceOf(
                'JMS\Payment\PaypalBundle\Form\ExpressCheckoutType',
                $config->getType()->getInnerType()
            );
        }
    }

    public function testMethodChoices()
    {
        if (Legacy::formChoicesAsValues()) {
            $this->markTestSkipped();
        }

        $form = $this->createForm();

        $this->assertArraySubset(array(
            'form.label.foo' => 'foo',
            'form.label.bar' => 'bar',
        ), $form->get('method')->getConfig()->getOption('choices'));
    }

    public function testLegacyMethodChoices()
    {
        if (!Legacy::formChoicesAsValues()) {
            $this->markTestSkipped();
        }

        $form = $this->createForm();

        $expected = array(
            'foo' => 'form.label.foo',
            'bar' => 'form.label.bar',
        );

        if (version_compare(Kernel::VERSION, '2.7.0', '>=')) {
            $expected = array(
                'foo' => 0,
                'bar' => 1,
            );
        }

        $this->assertArraySubset($expected, $form->get('method')->getConfig()->getOption('choices'));
    }

    public function testDefaultMethod()
    {
        $form = $this->createForm(array(
            'default_method' => 'foo',
        ));

        $this->assertEquals('foo', $form->get('method')->getConfig()->getOption('data'));
    }

    public function testAllowedMethods()
    {
        if (Legacy::formChoicesAsValues()) {
            $this->markTestSkipped();
        }

        $form = $this->createForm(array(
            'allowed_methods' => array('bar'),
        ));

        $choices = $form->get('method')->getConfig()->getOption('choices');
        $this->assertArrayNotHasKey('form.label.foo', $choices);
        $this->assertArraySubset(array('form.label.bar' => 'bar'), $choices);

        $this->assertTrue($form->has('data_bar'));
        $this->assertFalse($form->has('data_foo'));
    }

    public function testLegacyAllowedMethods()
    {
        if (!Legacy::formChoicesAsValues()) {
            $this->markTestSkipped();
        }

        $form = $this->createForm(array(
            'allowed_methods' => array('bar'),
        ));

        $choices = $form->get('method')->getConfig()->getOption('choices');
        $this->assertArrayNotHasKey('foo', $choices);
        $this->assertArraySubset(array('bar' => 'form.label.bar'), $choices);
    }

    public function testMethodOptions()
    {
        $form = $this->createForm(array('method_options' => array(
            'foo' => array(
                'attr' => array('foo_attr'),
            ),
            'bar' => array(
                'attr' => array('bar_attr'),
            ),
        )));

        foreach (array('foo', 'bar') as $method) {
            $this->assertArraySubset(
                array($method.'_attr'),
                $form->get('data_'.$method)->getConfig()->getOption('attr')
            );
        }
    }

    public function testChoiceOptions()
    {
        $form = $this->createForm(array('choice_options' => array(
            'expanded' => false,
            'data' => 'baz',
        )));

        $config = $form->get('method')->getConfig();
        $this->assertFalse($config->getOption('expanded'));
        $this->assertEquals('baz', $config->getOption('data'));
    }

    private function createForm($options = array(), $data = array())
    {
        $options = array_merge(array(
            'amount' => '10.42',
            'currency' => 'EUR',
        ), $options);

        $form = Legacy::supportsFormTypeName()
            ? 'jms_choose_payment_method'
            : 'JMS\Payment\CoreBundle\Form\ChoosePaymentMethodType'
        ;

        $form = $this->factory->create($form, null, $options);
        $form->submit($data);

        return $form;
    }

    protected function setUp()
    {
        $this->pluginController = $this->getMockBuilder('JMS\Payment\CoreBundle\PluginController\PluginControllerInterface')
            ->getMock();

        parent::setUp();
    }

    protected function getExtensions()
    {
        $pluginType = new ExpressCheckoutType();

        if (Legacy::supportsFormTypeName()) {
            $pluginTypeName = $pluginType->getName();
        } else {
            $pluginTypeName = get_class($pluginType);
        }

        $type = new ChoosePaymentMethodType($this->pluginController, array(
            'foo' => $pluginTypeName,
            'bar' => $pluginTypeName,
        ));

        if (Legacy::supportsFormTypeName()) {
            $extensions = array(
                $pluginType->getName() => $pluginType,
                $type->getName() => $type,
            );
        } else {
            $extensions = array($pluginType, $type);
        }

        return array(new PreloadedExtension($extensions, array()));
    }
}
