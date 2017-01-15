<?php

namespace JMS\Payment\CoreBundle\Tests\Functional;

class PaymentWorkflowNoEncryptionTest extends PaymentWorkflowTest
{
    protected static function createKernel(array $options = array())
    {
        return parent::createKernel(array('config' => 'config_no_encryption.yml'));
    }
}
