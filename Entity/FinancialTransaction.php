<?php

namespace JMS\Payment\CoreBundle\Entity;

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
    /**
     * @var \JMS\Payment\CoreBundle\Entity\Credit|null
     */
    private $credit;

    /**
     * @var \JMS\Payment\CoreBundle\Entity\ExtendedData|null
     */
    private $extendedData;

    /**
     * @var \JMS\Payment\CoreBundle\Entity\ExtendedData|null
     */
    private $extendedDataOriginal;

    private $id;

    /**
     * @var \JMS\Payment\CoreBundle\Entity\Payment|null
     */
    private $payment;

    private $processedAmount;
    private $reasonCode;
    private $referenceNumber;
    private $requestedAmount;
    private $responseCode;
    private $state;
    private $createdAt;
    private $updatedAt;
    private $trackingId;
    private $transactionType;

    public function __construct()
    {
        $this->state = self::STATE_NEW;
        $this->createdAt = new \DateTime();
        $this->processedAmount = 0.0;
        $this->requestedAmount = 0.0;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Entity\Credit|null
     */
    public function getCredit()
    {
        return $this->credit;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Entity\ExtendedData|null
     */
    public function getExtendedData()
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

    /**
     * @return \JMS\Payment\CoreBundle\Entity\Payment|null
     */
    public function getPayment()
    {
        return $this->payment;
    }

    public function getProcessedAmount()
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

    public function getRequestedAmount()
    {
        return $this->requestedAmount;
    }

    public function getResponseCode()
    {
        return $this->responseCode;
    }

    public function getState()
    {
        return $this->state;
    }

    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    public function getUpdatedAt()
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

    public function onPostLoad()
    {
        if (null !== $this->extendedData) {
            $this->extendedDataOriginal = clone $this->extendedData;
        }
    }

    public function onPrePersist()
    {
        $this->updatedAt = new \DateTime();

        if (null !== $this->extendedDataOriginal
                 && null !== $this->extendedData
                 && false === $this->extendedData->equals($this->extendedDataOriginal)) {
            $this->extendedData = clone $this->extendedData;
        }
    }

    public function setCredit(CreditInterface $credit)
    {
        $this->credit = $credit;
    }

    public function setExtendedData(ExtendedDataInterface $data)
    {
        $this->extendedData = $data;
    }

    public function setPayment(PaymentInterface $payment)
    {
        $this->payment = $payment;
    }

    public function setProcessedAmount($amount)
    {
        $this->processedAmount = $amount;
    }

    public function setReasonCode($code)
    {
        $this->reasonCode = $code;
    }

    public function setReferenceNumber($referenceNumber)
    {
        $this->referenceNumber = $referenceNumber;
    }

    public function setRequestedAmount($amount)
    {
        $this->requestedAmount = $amount;
    }

    public function setResponseCode($code)
    {
        $this->responseCode = $code;
    }

    public function setState($state)
    {
        $this->state = $state;
    }

    public function setTrackingId($id)
    {
        $this->trackingId = $id;
    }

    public function setTransactionType($type)
    {
        $this->transactionType = $type;
    }
}
