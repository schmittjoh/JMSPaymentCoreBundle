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

interface FinancialTransactionInterface
{
    const STATE_CANCELED = 1;
    const STATE_FAILED = 2;
    const STATE_NEW = 3;
    const STATE_PENDING = 4;
    const STATE_SUCCESS = 5;

    const TRANSACTION_TYPE_APPROVE = 1;
    const TRANSACTION_TYPE_APPROVE_AND_DEPOSIT = 2;
    const TRANSACTION_TYPE_CREDIT = 3;
    const TRANSACTION_TYPE_DEPOSIT = 4;
    const TRANSACTION_TYPE_REVERSE_APPROVAL = 5;
    const TRANSACTION_TYPE_REVERSE_CREDIT = 6;
    const TRANSACTION_TYPE_REVERSE_DEPOSIT = 7;

    /**
     * @return \JMS\Payment\CoreBundle\Model\CreditInterface|null
     */
    public function getCredit();

    /**
     * @return \JMS\Payment\CoreBundle\Model\ExtendedDataInterface|null
     */
    public function getExtendedData();

    public function getId();

    /**
     * @return \JMS\Payment\CoreBundle\Model\PaymentInterface|null
     */
    public function getPayment();

    public function getProcessedAmount();
    public function getReasonCode();
    public function getReferenceNumber();
    public function getRequestedAmount();
    public function getResponseCode();
    public function getState();
    public function getTrackingId();
    public function getTransactionType();
    public function setCredit(CreditInterface $credit);
    public function setExtendedData(ExtendedDataInterface $data);
    public function setPayment(PaymentInterface $payment);
    public function setProcessedAmount($amount);
    public function setReasonCode($code);
    public function setReferenceNumber($referenceNumber);
    public function setRequestedAmount($amount);
    public function setResponseCode($code);
    public function setState($state);
    public function setTrackingId($id);
    public function setTransactionType($type);
}
