<?php

namespace JMS\Payment\CoreBundle\Plugin\Exception;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * This exception will be thrown when the plugin determines the
 * PaymentInstruction to be invalid.
 *
 * The payment plugin controller will consequentially set the transaction's
 * state to FAILED, and the instruction's state to INVALID.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class InvalidPaymentInstructionException extends FinancialException
{
    private $dataErrors = array();
    private $globalErrors = array();

    public function __construct($message = null, $code = 0, \Exception $previous = null)
    {
        parent::__construct($message ?: 'The payment instruction is invalid.', $code, $previous);
    }

    /**
     * Sets an error map for data parameters.
     *
     * The keys are expected to match the keys of the offending entry in the
     * ExtendedData class.
     *
     * For example, if the "cc_holder" key is missing, this should be set to:
     *
     *    array("cc_holder" => "The credit card holder is required.")
     *
     * @param array $errors
     */
    public function setDataErrors(array $errors)
    {
        $this->dataErrors = $errors;
    }

    /**
     * Sets an error list for the entire PaymentInstruction.
     *
     * This list is globally for the entire PaymentInstruction, and not
     * directly related to any specific data entry.
     *
     * @param array $errors
     */
    public function setGlobalErrors(array $errors)
    {
        $this->globalErrors = $errors;
    }

    public function getDataErrors()
    {
        return $this->dataErrors;
    }

    public function getGlobalErrors()
    {
        return $this->globalErrors;
    }
}
