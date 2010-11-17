<?php

namespace Bundle\PaymentBundle\Entity;

interface CreditInterface
{
    const STATE_CANCELED = 1;
    const STATE_CREDITED = 2;
    const STATE_CREDITING = 3;
    const STATE_FAILED = 4;
    const STATE_NEW = 5;
    
    function getCreditedAmount();
    function getCreditingAmount();
    function getCreditTransaction();
    function getId();
    function getPaymentInstruction();
    function getPendingTransaction();
    function getReverseCreditTransactions();
    function getReversingAmount();
    function getState();
    function getTargetAmount();
    function hasPendingTransaction();
    function setCreditedAmount($amount);
    function setCreditingAmount($amount);
    function setReversingAmount($amount);
}