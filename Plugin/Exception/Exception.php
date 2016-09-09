<?php

namespace JMS\Payment\CoreBundle\Plugin\Exception;

use JMS\Payment\CoreBundle\Exception\Exception as PaymentBundleException;
use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;

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
 * Base Exception for plugins.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class Exception extends PaymentBundleException
{
    protected $credit;
    protected $financialTransaction;
    protected $payment;
    protected $paymentInstruction;

    /**
     * @return CreditInterface
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @return FinancialTransactionInterface
     */
    public function getFinancialTransaction()
    {
        return $this->financialTransaction;
    }

    /**
     * @return PaymentInterface
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return PaymentInstructionInterface
     */
    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }

    public function setCredit(CreditInterface $credit)
    {
        $this->credit = $credit;
    }

    public function setFinancialTransaction(FinancialTransactionInterface $transaction)
    {
        $this->financialTransaction = $transaction;
    }

    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    public function setPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $this->paymentInstruction = $instruction;
    }
}
