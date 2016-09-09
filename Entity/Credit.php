<?php

namespace JMS\Payment\CoreBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
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

class Credit implements CreditInterface
{
    private $attentionRequired;
    private $createdAt;
    private $creditedAmount;
    private $creditingAmount;
    private $id;

    /**
     * @var \JMS\Payment\CoreBundle\Entity\Payment
     */
    private $payment;

    /**
     * @var \JMS\Payment\CoreBundle\Entity\PaymentInstruction
     */
    private $paymentInstruction;

    /**
     * @var \Doctrine\Common\Collections\ArrayCollection|\JMS\Payment\CoreBundle\Entity\FinancialTransaction[]
     */
    private $transactions;

    private $reversingAmount;
    private $state;
    private $targetAmount;
    private $updatedAt;

    public function __construct(PaymentInstructionInterface $paymentInstruction, $amount)
    {
        $this->attentionRequired = false;
        $this->creditedAmount = 0.0;
        $this->creditingAmount = 0.0;
        $this->paymentInstruction = $paymentInstruction;
        $this->transactions = new ArrayCollection();
        $this->reversingAmount = 0.0;
        $this->state = self::STATE_NEW;
        $this->targetAmount = $amount;
        $this->createdAt = new \DateTime();

        $this->paymentInstruction->addCredit($this);
    }

    public function addTransaction(FinancialTransaction $transaction)
    {
        $this->transactions->add($transaction);
        $transaction->setCredit($this);
    }

    public function getCreditedAmount()
    {
        return $this->creditedAmount;
    }

    public function getCreditingAmount()
    {
        return $this->creditingAmount;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Entity\FinancialTransaction|null
     */
    public function getCreditTransaction()
    {
        foreach ($this->transactions as $transaction) {
            if (FinancialTransactionInterface::TRANSACTION_TYPE_CREDIT === $transaction->getTransactionType()) {
                return $transaction;
            }
        }

        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Entity\Payment
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Entity\PaymentInstruction
     */
    public function getPaymentInstruction()
    {
        return $this->paymentInstruction;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Entity\FinancialTransaction|null
     */
    public function getPendingTransaction()
    {
        foreach ($this->transactions as $transaction) {
            if (FinancialTransactionInterface::STATE_PENDING === $transaction->getState()) {
                return $transaction;
            }
        }

        return null;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|\JMS\Payment\CoreBundle\Entity\FinancialTransaction[]
     */
    public function getReverseCreditTransactions()
    {
        return $this->transactions->filter(function ($transaction) {
            return FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_CREDIT === $transaction->getTransactionType();
        });
    }

    public function getReversingAmount()
    {
        return $this->reversingAmount;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getTargetAmount()
    {
        return $this->targetAmount;
    }

    /**
     * @return ArrayCollection
     */
    public function getTransactions()
    {
        return $this->transactions;
    }

    public function isAttentionRequired()
    {
        return $this->attentionRequired;
    }

    public function isIndependent()
    {
        return null === $this->payment;
    }

    public function setAttentionRequired($boolean)
    {
        $this->attentionRequired = (bool) $boolean;
    }

    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    public function hasPendingTransaction()
    {
        return null !== $this->getPendingTransaction();
    }

    public function setCreditedAmount($amount)
    {
        $this->creditedAmount = $amount;
    }

    public function setCreditingAmount($amount)
    {
        $this->creditingAmount = $amount;
    }

    public function setReversingAmount($amount)
    {
        $this->reversingAmount = $amount;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function onPreSave()
    {
        $this->updatedAt = new \DateTime();
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
