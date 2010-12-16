<?php

namespace Bundle\JMS\Payment\CorePaymentBundle;

use Bundle\JMS\Payment\CorePaymentBundle\Entity\ExtendedDataType;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CorePaymentBundle extends Bundle
{
    public function boot()
    {
        // FIXME: only add type when using Doctrine2 entities
        if (false === Type::hasType(ExtendedDataType::NAME)) {
            ExtendedDataType::setEncryptionService($this->container->get('payment.encryption_service'));
            Type::addType(ExtendedDataType::NAME, 'Bundle\JMS\Payment\CorePaymentBundle\Entity\ExtendedDataType');
        }
    }
}
