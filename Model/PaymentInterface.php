<?php

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

interface PaymentInterface
{
    const STATE_APPROVED = 1;
    const STATE_APPROVING = 2;
    const STATE_CANCELED = 3;
    const STATE_EXPIRED = 4;
    const STATE_FAILED = 5;
    const STATE_NEW = 6;
    const STATE_DEPOSITING = 7;
    const STATE_DEPOSITED = 8;

    public function getApprovedAmount();

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface|null
     */
    public function getApproveTransaction();

    public function getApprovingAmount();
    public function getCreditedAmount();
    public function getCreditingAmount();
    public function getDepositedAmount();
    public function getDepositingAmount();

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface[]
     */
    public function getDepositTransactions();

    public function getExpirationDate();
    public function getId();

    /**
     * @return \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface
     */
    public function getPaymentInstruction();

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface|null
     */
    public function getPendingTransaction();

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface[]
     */
    public function getReverseApprovalTransactions();

    /**
     * @return \JMS\Payment\CoreBundle\Model\FinancialTransactionInterface[]
     */
    public function getReverseDepositTransactions();

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
    public function setExpirationDate(\DateTime $date);
    public function setExpired($boolean);
    public function setReversingApprovedAmount($amount);
    public function setReversingCreditedAmount($amount);
    public function setReversingDepositedAmount($amount);
    public function setState($state);
}
