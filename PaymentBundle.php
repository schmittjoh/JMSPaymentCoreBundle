<?php

namespace Bundle\PaymentBundle;

use Bundle\PaymentBundle\Entity\ExtendedDataType;
use Doctrine\DBAL\Types\Type;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class PaymentBundle extends Bundle
{
    public function boot()
    {
        // FIXME: only add type when using Doctrine2 entities
        if (false === Type::hasType(ExtendedDataType::NAME)) {
            ExtendedDataType::setEncryptionService($this->container->get('payment.encryption_service'));
            Type::addType(ExtendedDataType::NAME, 'Bundle\PaymentBundle\Entity\ExtendedDataType');
        }
    }
}
