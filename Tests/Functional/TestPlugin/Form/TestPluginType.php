<?php

namespace JMS\Payment\CoreBundle\Tests\Functional\TestPlugin\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class TestPluginType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
    }
    public function getName()
    {
        return 'test_plugin';
    }
}