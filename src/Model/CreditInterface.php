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

interface CreditInterface
{
    public const STATE_CANCELED = 1;
    public const STATE_CREDITED = 2;
    public const STATE_CREDITING = 3;
    public const STATE_FAILED = 4;
    public const STATE_NEW = 5;

    public function getCreditedAmount();

    public function getCreditingAmount();

    public function getCreditTransaction();

    public function getId();

    public function getPayment(): PaymentInterface;

    public function getPaymentInstruction(): PaymentInstructionInterface;

    public function getPendingTransaction(): ?FinancialTransactionInterface;

    public function getReverseCreditTransactions(): array;

    public function getReversingAmount();

    public function getState();

    public function getTargetAmount();

    public function hasPendingTransaction();

    public function isAttentionRequired();

    public function isIndependent();

    public function setCreditedAmount($amount);

    public function setCreditingAmount($amount);

    public function setAttentionRequired($boolean);

    public function setPayment(PaymentInterface $payment);

    public function setReversingAmount($amount);

    public function setState($state);

    public function setReversingCreditedAmount(float $amount): void;

    public function getReversingCreditedAmount(): float;

    public function addTransaction(FinancialTransactionInterface $transaction): void;
}
