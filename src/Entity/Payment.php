<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\Entity;

use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

class Payment implements PaymentInterface
{
    private float $approvedAmount;

    private float $approvingAmount;

    private DateTime $createdAt;

    private float $creditedAmount;

    private float $creditingAmount;

    private float $depositedAmount;

    private float $depositingAmount;

    private ?DateTime $expirationDate;

    private $id;

    private PaymentInstruction $paymentInstruction;

    private float $reversingApprovedAmount;

    private float $reversingCreditedAmount;

    private float $reversingDepositedAmount;

    private int $state;

    private $targetAmount;

    /**
     * @var ArrayCollection<FinancialTransactionInterface>
     */
    private ArrayCollection $transactions;

    private $attentionRequired;

    private bool $expired;

    private $updatedAt;

    public function __construct(PaymentInstruction $paymentInstruction, $amount)
    {
        $this->approvedAmount = 0.0;
        $this->approvingAmount = 0.0;
        $this->createdAt = new DateTime();
        $this->creditedAmount = 0.0;
        $this->creditingAmount = 0.0;
        $this->depositedAmount = 0.0;
        $this->depositingAmount = 0.0;
        $this->paymentInstruction = $paymentInstruction;
        $this->reversingApprovedAmount = 0.0;
        $this->reversingCreditedAmount = 0.0;
        $this->reversingDepositedAmount = 0.0;
        $this->state = self::STATE_NEW;
        $this->targetAmount = $amount;
        $this->transactions = new ArrayCollection();
        $this->attentionRequired = false;
        $this->expired = false;

        $this->paymentInstruction->addPayment($this);
    }

    public function addTransaction(FinancialTransactionInterface $transaction): void
    {
        $this->transactions->add($transaction);
        $transaction->setPayment($this);
    }

    public function getApprovedAmount(): float
    {
        return $this->approvedAmount;
    }

    public function getApproveTransaction(): ?FinancialTransactionInterface
    {
        foreach ($this->transactions as $transaction) {
            $type = $transaction->getTransactionType();

            if (FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE === $type
                || FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT === $type) {
                return $transaction;
            }
        }

        return null;
    }

    public function getApprovingAmount(): float
    {
        return $this->approvingAmount;
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
     * @return Collection<FinancialTransactionInterface>
     */
    public function getDepositTransactions(): Collection
    {
        return $this->transactions->filter(
            fn (
                $transaction
            ) => FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT === $transaction->getTransactionType()
        );
    }

    public function getExpirationDate(): DateTime
    {
        return $this->expirationDate;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPaymentInstruction(): PaymentInstructionInterface
    {
        return $this->paymentInstruction;
    }

    public function getPendingTransaction(): ?FinancialTransactionInterface
    {
        foreach ($this->transactions as $transaction) {
            if (FinancialTransactionInterface::STATE_PENDING === $transaction->getState()) {
                return $transaction;
            }
        }

        return null;
    }

    /**
     * @return Collection<FinancialTransactionInterface>
     */
    public function getReverseApprovalTransactions(): Collection
    {
        return $this->transactions->filter(
            fn (
                $transaction
            ) => FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_APPROVAL === $transaction->getTransactionType()
        );
    }

    /**
     * @return Collection<FinancialTransactionInterface>
     */
    public function getReverseDepositTransactions(): Collection
    {
        return $this->transactions->filter(
            fn (
                $transaction
            ) => FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_DEPOSIT === $transaction->getTransactionType()
        );
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

    public function getState(): int
    {
        return $this->state;
    }

    public function getTargetAmount()
    {
        return $this->targetAmount;
    }

    /**
     * @return Collection<FinancialTransactionInterface>
     */
    public function getTransactions(): Collection
    {
        return $this->transactions;
    }

    public function hasPendingTransaction(): bool
    {
        return null !== $this->getPendingTransaction();
    }

    public function isAttentionRequired(): bool
    {
        return $this->attentionRequired;
    }

    public function isExpired(): bool
    {
        if (true === $this->expired) {
            return true;
        }

        if (null !== $this->expirationDate) {
            return $this->expirationDate->getTimestamp() < time();
        }

        return false;
    }

    public function onPreSave(): void
    {
        $this->updatedAt = new DateTime();
    }

    public function setApprovedAmount($amount): void
    {
        $this->approvedAmount = $amount;
    }

    public function setApprovingAmount($amount): void
    {
        $this->approvingAmount = $amount;
    }

    public function setAttentionRequired($boolean): void
    {
        $this->attentionRequired = (bool) $boolean;
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

    public function setExpirationDate(DateTime $date): void
    {
        $this->expirationDate = $date;
    }

    public function setExpired($boolean): void
    {
        $this->expired = (bool) $boolean;
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

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }
}
