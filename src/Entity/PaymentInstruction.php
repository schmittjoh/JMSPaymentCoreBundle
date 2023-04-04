<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;

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

class PaymentInstruction implements PaymentInstructionInterface
{
    private $amount;

    private float $approvedAmount;

    private float $approvingAmount;

    private DateTime $createdAt;

    private float $creditedAmount;

    private float $creditingAmount;

    private ArrayCollection $credits;

    private $currency;

    private float $depositedAmount;

    private float $depositingAmount;

    private ?ExtendedData $extendedData;

    private ?ExtendedData $extendedDataOriginal;

    private $id;

    private ArrayCollection $payments;

    private string $paymentSystemName;

    private float $reversingApprovedAmount;

    private float $reversingCreditedAmount;

    private float $reversingDepositedAmount;

    private int $state;

    private DateTime $updatedAt;

    public function __construct($amount, $currency, string $paymentSystemName, ExtendedData $data = null)
    {
        if (null === $data) {
            $data = new ExtendedData();
        }

        $this->amount = $amount;
        $this->approvedAmount = 0.0;
        $this->approvingAmount = 0.0;
        $this->createdAt = new DateTime();
        $this->creditedAmount = 0.0;
        $this->creditingAmount = 0.0;
        $this->credits = new ArrayCollection();
        $this->currency = $currency;
        $this->depositingAmount = 0.0;
        $this->depositedAmount = 0.0;
        $this->extendedData = $data;
        $this->extendedDataOriginal = clone $data;
        $this->payments = new ArrayCollection();
        $this->paymentSystemName = $paymentSystemName;
        $this->reversingApprovedAmount = 0.0;
        $this->reversingCreditedAmount = 0.0;
        $this->reversingDepositedAmount = 0.0;
        $this->state = self::STATE_NEW;
    }

    /**
     * This method adds a Credit container to this PaymentInstruction.
     *
     * This method is called automatically from Credit::__construct().
     */
    public function addCredit(CreditInterface $credit): void
    {
        if ($credit->getPaymentInstruction() !== $this) {
            throw new InvalidArgumentException('This credit container belongs to another instruction.');
        }

        $this->credits->add($credit);
    }

    /**
     * This method adds a Payment container to this PaymentInstruction.
     *
     * This method is called automatically from Payment::__construct().
     *
     * @param Payment $payment
     */
    public function addPayment(Payment $payment): void
    {
        if ($payment->getPaymentInstruction() !== $this) {
            throw new InvalidArgumentException('This payment container belongs to another instruction.');
        }

        $this->payments->add($payment);
    }

    public function getAmount()
    {
        return $this->amount;
    }

    public function getCurrency()
    {
        return $this->currency;
    }

    public function getPaymentSystemName(): string
    {
        return $this->paymentSystemName;
    }

    public function getExtendedData(): ExtendedData
    {
        return $this->extendedData;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getApprovingAmount(): float
    {
        return $this->approvingAmount;
    }

    public function getApprovedAmount(): float
    {
        return $this->approvedAmount;
    }

    public function getCreditedAmount(): float
    {
        return $this->creditedAmount;
    }

    public function getCreditingAmount(): float
    {
        return $this->creditingAmount;
    }

    public function getDepositedAmount(): float
    {
        return $this->depositedAmount;
    }

    public function getDepositingAmount(): float
    {
        return $this->depositingAmount;
    }

    /**
     * @return Credit[]
     */
    public function getCredits(): array
    {
        return $this->credits;
    }

    /**
     * @return Payment[]
     */
    public function getPayments(): array
    {
        return $this->payments;
    }

    public function getPendingTransaction(): ?FinancialTransactionInterface
    {
        foreach ($this->payments as $payment) {
            if (null !== $transaction = $payment->getPendingTransaction()) {
                return $transaction;
            }
        }

        foreach ($this->credits as $credit) {
            if (null !== $transaction = $credit->getPendingTransaction()) {
                return $transaction;
            }
        }

        return null;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getReversingApprovedAmount(): float
    {
        return $this->reversingApprovedAmount;
    }

    public function getReversingCreditedAmount(): float
    {
        return $this->reversingCreditedAmount;
    }

    public function getReversingDepositedAmount(): float
    {
        return $this->reversingDepositedAmount;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function hasPendingTransaction(): bool
    {
        return null !== $this->getPendingTransaction();
    }

    public function onPostLoad(): void
    {
        $this->extendedDataOriginal = clone $this->extendedData;
    }

    public function onPreSave(): void
    {
        $this->updatedAt = new Datetime();

        // this is necessary until Doctrine adds an interface for comparing
        // value objects. Right now this is done by referential equality
        if (
            null !== $this->extendedDataOriginal
            && false === $this->extendedData->equals($this->extendedDataOriginal)
        ) {
            $this->extendedData = clone $this->extendedData;
        }
    }

    public function setApprovingAmount($amount): void
    {
        $this->approvingAmount = $amount;
    }

    public function setApprovedAmount($amount): void
    {
        $this->approvedAmount = $amount;
    }

    public function setCreditedAmount($amount): void
    {
        $this->creditedAmount = $amount;
    }

    public function setCreditingAmount($amount): void
    {
        $this->creditingAmount = $amount;
    }

    public function setDepositedAmount($amount): void
    {
        $this->depositedAmount = $amount;
    }

    public function setDepositingAmount($amount): void
    {
        $this->depositingAmount = $amount;
    }

    public function setReversingApprovedAmount($amount): void
    {
        $this->reversingApprovedAmount = $amount;
    }

    public function setReversingCreditedAmount($amount): void
    {
        $this->reversingCreditedAmount = $amount;
    }

    public function setReversingDepositedAmount($amount): void
    {
        $this->reversingDepositedAmount = $amount;
    }

    public function setState($state): void
    {
        $this->state = $state;
    }
}
