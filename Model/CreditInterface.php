<?php

namespace Bundle\JMS\Payment\CorePaymentBundle\Model;

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
    function getPayment();
    function getPaymentInstruction();
    function getPendingTransaction();
    function getReverseCreditTransactions();
    function getReversingAmount();
    function getState();
    function getTargetAmount();
    function hasPendingTransaction();
    function isAttentionRequired();
    function isIndependent();
    function setCreditedAmount($amount);
    function setCreditingAmount($amount);
    function setAttentionRequired($boolean);
    function setPayment(PaymentInterface $payment);
    function setReversingAmount($amount);
    function setState($state);
}