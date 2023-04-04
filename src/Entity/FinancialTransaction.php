<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\Entity;

use DateTime;
use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\ExtendedDataInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
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

class FinancialTransaction implements FinancialTransactionInterface
{
    private ?CreditInterface $credit = null;

    private ?ExtendedDataInterface $extendedData = null;

    private ?ExtendedDataInterface $extendedDataOriginal = null;

    private $id;

    private ?PaymentInterface $payment = null;

    private float $processedAmount;

    private $reasonCode;

    private $referenceNumber;

    private float $requestedAmount;

    private string $responseCode;

    private int $state;

    private DateTime $createdAt;

    private DateTime $updatedAt;

    private $trackingId;

    private $transactionType;

    public function __construct()
    {
        $this->state = self::STATE_NEW;
        $this->createdAt = new DateTime();
        $this->processedAmount = 0.0;
        $this->requestedAmount = 0.0;
    }

    public function getCredit(): ?CreditInterface
    {
        return $this->credit;
    }

    public function getExtendedData(): ?ExtendedDataInterface
    {
        if (null !== $this->extendedData) {
            return $this->extendedData;
        }

        if (null !== $this->payment) {
            return $this->payment->getPaymentInstruction()->getExtendedData();
        } elseif (null !== $this->credit) {
            return $this->credit->getPaymentInstruction()->getExtendedData();
        }

        return null;
    }

    public function getId()
    {
        return $this->id;
    }

    public function getPayment(): ?PaymentInterface
    {
        return $this->payment;
    }

    public function getProcessedAmount(): float
    {
        return $this->processedAmount;
    }

    public function getReasonCode()
    {
        return $this->reasonCode;
    }

    public function getReferenceNumber()
    {
        return $this->referenceNumber;
    }

    public function getRequestedAmount(): float
    {
        return $this->requestedAmount;
    }

    public function getResponseCode(): string
    {
        return $this->responseCode;
    }

    public function getState(): int
    {
        return $this->state;
    }

    public function getCreatedAt(): DateTime
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): DateTime
    {
        return $this->updatedAt;
    }

    public function getTrackingId()
    {
        return $this->trackingId;
    }

    public function getTransactionType()
    {
        return $this->transactionType;
    }

    public function onPostLoad(): void
    {
        if (null !== $this->extendedData) {
            $this->extendedDataOriginal = clone $this->extendedData;
        }
    }

    public function onPrePersist(): void
    {
        $this->updatedAt = new DateTime();

        if (null !== $this->extendedDataOriginal
            && null !== $this->extendedData
            && false === $this->extendedData->equals($this->extendedDataOriginal)) {
            $this->extendedData = clone $this->extendedData;
        }
    }

    public function setCredit(CreditInterface $credit): void
    {
        $this->credit = $credit;
    }

    public function setExtendedData(ExtendedDataInterface $data): void
    {
        $this->extendedData = $data;
    }

    public function setPayment(PaymentInterface $payment): void
    {
        $this->payment = $payment;
    }

    public function setProcessedAmount($amount): void
    {
        $this->processedAmount = $amount;
    }

    public function setReasonCode($code): void
    {
        $this->reasonCode = $code;
    }

    public function setReferenceNumber($referenceNumber): void
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function setRequestedAmount($amount): void
    {
        $this->requestedAmount = $amount;
    }

    public function setResponseCode(string $code): void
    {
        $this->responseCode = $code;
    }

    public function setState($state): void
    {
        $this->state = $state;
    }

    public function setTrackingId($id): void
    {
        $this->trackingId = $id;
    }

    public function setTransactionType($type): void
    {
        $this->transactionType = $type;
    }
}
