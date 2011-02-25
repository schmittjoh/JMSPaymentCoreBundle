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

interface PaymentInstructionInterface
{
    const STATE_CLOSED = 1;
    const STATE_INVALID = 2;
    const STATE_NEW = 3;
    const STATE_VALID = 4;

//    function getAccount();
    function getAmount();
    function getApprovedAmount();
    function getApprovingAmount();
    function getCreditedAmount();
    function getCreditingAmount();
    function getCredits();
    function getCurrency();
    function getDepositedAmount();
    function getDepositingAmount();
    function getExtendedData();
    function getId();
    function getPayments();
    function getPaymentSystemName();
    function getPendingTransaction();
    function getReversingApprovedAmount();
    function getReversingCreditedAmount();
    function getReversingDepositedAmount();
    function getState();
    function hasPendingTransaction();

    /**
     * This method sets the account identifier associated with this PaymentInstruction
     *
     * Usually, this is a number, but an alphanumeric string is also permissible.
     *
     * The implementation should populate this with the value of "account" in the
     * ExtendedData value object. If this key is not set, it is the responsibility
     * of the plugin to set an account identifier.
     *
     * @param string $account
     * @return void
     */
//    function setAccount($account);
    function setApprovedAmount($amount);
    function setApprovingAmount($amount);
    function setCreditedAmount($amount);
    function setCreditingAmount($amount);
    function setDepositedAmount($amount);
    function setDepositingAmount($amount);
    function setReversingApprovedAmount($amount);
    function setReversingCreditedAmount($amount);
    function setReversingDepositedAmount($amount);
    function setState($state);
}