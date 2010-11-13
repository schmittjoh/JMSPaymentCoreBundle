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
    function getOrderId();
    function getRmaId();
    function getPaymentSystemName();
    function getState();
    function getCreatedAt();
    function getUpdatedAt();
}