<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\Plugin;

use JMS\Payment\CoreBundle\Plugin\Exception\InvalidPaymentInstructionException;

/**
 * Convenience class for building up error messages.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class ErrorBuilder
{
    private $dataErrors = [];

    private $globalErrors = [];

    public function addDataError($field, $messageTemplate): void
    {
        $this->dataErrors[$field] = $messageTemplate;
    }

    public function addGlobalError($messageTemplate): void
    {
        $this->globalErrors[] = $messageTemplate;
    }

    public function hasErrors()
    {
        return $this->dataErrors || $this->globalErrors;
    }

    public function getException()
    {
        $ex = new InvalidPaymentInstructionException();
        $ex->setDataErrors($this->dataErrors);
        $ex->setGlobalErrors($this->globalErrors);

        return $ex;
    }
}
