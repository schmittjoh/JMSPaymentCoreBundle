<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\Model;

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

use DateTime;
use Doctrine\Common\Collections\Collection;
use JMS\Payment\CoreBundle\Entity\FinancialTransaction;

interface PaymentInterface
{
    public const STATE_APPROVED = 1;
    public const STATE_APPROVING = 2;
    public const STATE_CANCELED = 3;
    public const STATE_EXPIRED = 4;
    public const STATE_FAILED = 5;
    public const STATE_NEW = 6;
    public const STATE_DEPOSITING = 7;
    public const STATE_DEPOSITED = 8;

    public function getApprovedAmount();

    public function addTransaction(FinancialTransaction $transaction): void;

    public function getApproveTransaction(): ?FinancialTransactionInterface;

    public function getApprovingAmount();

    public function getCreditedAmount();

    public function getCreditingAmount();

    public function getDepositedAmount();

    public function getDepositingAmount();

    public function getDepositTransactions(): Collection;

    public function getExpirationDate();

    public function getId();

    public function getPaymentInstruction(): PaymentInstructionInterface;

    public function getPendingTransaction(): ?FinancialTransactionInterface;

    public function getReverseApprovalTransactions(): Collection;

    public function getReverseDepositTransactions(): Collection;

    public function getReversingApprovedAmount();

    public function getReversingCreditedAmount();

    public function getReversingDepositedAmount();

    public function getState();

    public function getTargetAmount();

    public function hasPendingTransaction();

    public function isAttentionRequired();

    public function isExpired();

    public function setApprovedAmount($amount);

    public function setApprovingAmount($amount);

    public function setAttentionRequired($boolean);

    public function setCreditedAmount($amount);

    public function setCreditingAmount($amount);

    public function setDepositedAmount($amount);

    public function setDepositingAmount($amount);

    public function setExpirationDate(DateTime $date);

    public function setExpired($boolean);

    public function setReversingApprovedAmount($amount);

    public function setReversingCreditedAmount($amount);

    public function setReversingDepositedAmount($amount);

    public function setState($state);
}
