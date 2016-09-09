<?php

namespace JMS\Payment\CoreBundle\PluginController;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\Exception as PluginException;

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

class Result
{
    const STATUS_FAILED = 1;
    const STATUS_PENDING = 2;
    const STATUS_SUCCESS = 3;
    const STATUS_UNKNOWN = 4;

    /**
     * @var \JMS\Payment\CoreBundle\Model\CreditInterface|null
     */
    protected $credit;

    /**
     * @var \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface|null
     */
    protected $financialTransaction;

    /**
     * @var \JMS\Payment\CoreBundle\Model\PaymentInterface|null
     */
    protected $payment;

    /**
     * @var \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface
     */
    protected $paymentInstruction;

    protected $paymentRequiresAttention;
    protected $pluginException;
    protected $reasonCode;
    protected $recoverable;
    protected $status;

    public function __construct()
    {
        $args = func_get_args();
        $nbArgs = count($args);

        if (3 === $nbArgs && $args[0] instanceof FinancialTransactionInterface) {
            $this->constructFinancialTransactionResult($args[0], $args[1], $args[2]);
        } elseif (3 === $nbArgs && $args[0] instanceof PaymentInstructionInterface) {
            $this->constructPaymentInstructionResult($args[0], $args[1], $args[2]);
        } else {
            throw new \InvalidArgumentException('The given arguments are not supported.');
        }
    }

    public function getPluginException()
    {
        return $this->pluginException;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface|null
     */
    public function getFinancialTransaction()
    {
        return $this->financialTransaction;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getReasonCode()
    {
        return $this->reasonCode;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\CreditInterface|null
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\PaymentInterface|null
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface
     */
    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }

    public function isAttentionRequired()
    {
        if (null === $this->payment && null === $this->credit) {
            throw new \LogicException('The result contains neither a payment, nor a credit.');
        }

        return null !== $this->payment ? $this->payment->isAttentionRequired() : $this->credit->isAttentionRequired();
    }

    public function isRecoverable()
    {
        return $this->recoverable;
    }

    public function setPluginException(PluginException $exception)
    {
        $this->pluginException = $exception;
    }

    public function setRecoverable($boolean = true)
    {
        $this->recoverable = (bool) $boolean;
    }

    protected function constructFinancialTransactionResult(FinancialTransactionInterface $transaction, $status, $reasonCode)
    {
        $this->financialTransaction = $transaction;
        $this->credit = $transaction->getCredit();
        $this->payment = $transaction->getPayment();
        $this->paymentInstruction = null !== $this->credit ? $this->credit->getPaymentInstruction() : $this->payment->getPaymentInstruction();
        $this->status = $status;
        $this->reasonCode = $reasonCode;
        $this->recoverable = false;
    }

    protected function constructPaymentInstructionResult(PaymentInstructionInterface $instruction, $status, $reasonCode)
    {
        $this->paymentInstruction = $instruction;
        $this->status = $status;
        $this->reasonCode = $reasonCode;
        $this->recoverable = false;
    }
}
