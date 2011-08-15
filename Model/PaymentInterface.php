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

    function getApprovedAmount();
    function getApproveTransaction();
    function getApprovingAmount();
    function getCreditedAmount();
    function getCreditingAmount();
    function getDepositedAmount();
    function getDepositingAmount();
    function getDepositTransactions();
    function getExpirationDate();
    function getId();
    function getPaymentInstruction();
    function getPendingTransaction();
    function getReverseApprovalTransactions();
    function getReverseDepositTransactions();
    function getReversingApprovedAmount();
    function getReversingCreditedAmount();
    function getReversingDepositedAmount();
    function getState();
    function getTargetAmount();
    function hasPendingTransaction();
    function isAttentionRequired();
    function isExpired();
    function setApprovedAmount($amount);
    function setApprovingAmount($amount);
    function setAttentionRequired($boolean);
    function setCreditedAmount($amount);
    function setCreditingAmount($amount);
    function setDepositedAmount($amount);
    function setDepositingAmount($amount);
    function setExpirationDate(\DateTime $date);
    function setExpired($boolean);
    function setReversingApprovedAmount($amount);
    function setReversingCreditedAmount($amount);
    function setReversingDepositedAmount($amount);
    function setState($state);
}