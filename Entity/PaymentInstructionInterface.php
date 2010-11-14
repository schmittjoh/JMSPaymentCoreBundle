<?php

namespace Bundle\PaymentBundle\Entity;

interface PaymentInstructionInterface
{
    const STATE_CLOSED = 1;
    const STATE_INVALID = 2;
    const STATE_NEW = 3;
    const STATE_VALID = 4;
    
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
    function getState();
    function getCreatedAt();
    function getUpdatedAt();
    function hasPendingTransaction();
    function setApprovedAmount($amount);
    function setApprovingAmount($amount);
    function setCreditedAmount($amount);
    function setCreditingAmount($amount);
    function setDepositedAmount($amount);
    function setDepositingAmount($amount);
}