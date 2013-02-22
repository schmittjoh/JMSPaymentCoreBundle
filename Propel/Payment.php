<?php

namespace JMS\Payment\CoreBundle\Propel;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Propel\om\BasePayment;

class Payment extends BasePayment implements PaymentInterface
{
    public function __construct(PaymentInstruction $paymentInstruction = null, $amount = 0.0)
    {
        parent::__construct();

        $this->setTargetAmount($amount);
        $this->setPaymentInstruction($paymentInstruction);
    }

    /**
     * @return FinancialTransactionInterface
     */
    public function getApproveTransaction()
    {
        foreach ($this->getFinancialTransactions() as $transaction) {
            $type = $transaction->getTransactionType();

            if (FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE === $type
                || FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT === $type) {
                return $transaction;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    public function getDepositTransactions()
    {
        $objects = new \PropelObjectCollection();

        foreach ($this->getFinancialTransactions() as $transaction) {
            if ($transaction->getTransactionType() === FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT) {
                $objects->append($transaction);
            }
        }

        return $objects;
    }

    /**
     * @return FinancialTransactionInterface
     */
    public function getPendingTransaction()
    {
        foreach ($this->getFinancialTransactions() as $transaction) {
            if (FinancialTransactionInterface::STATE_PENDING === $transaction->getState()) {
                return $transaction;
            }
        }

        return null;
    }

    /**
     * @return boolean
     */
    public function hasPendingTransaction()
    {
        return null !== $this->getPendingTransaction();
    }

    /**
     * @return array
     */
    public function getReverseApprovalTransactions()
    {
        $objects = new \PropelObjectCollection();

        foreach ($this->getFinancialTransactions() as $transaction) {
            if ($transaction->getTransactionType() === FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_APPROVAL) {
                $objects->append($transaction);
            }
        }

        return $objects;
    }

    /**
     * @return array
     */
    public function getReverseDepositTransactions()
    {
        $objects = new \PropelObjectCollection();

        foreach ($this->getFinancialTransactions() as $transaction) {
            if ($transaction->getTransactionType() === FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_DEPOSIT) {
                $objects->append($transaction);
            }
        }

        return $objects;
    }

    /**
     * @return boolean
     */
    public function isAttentionRequired()
    {
        return $this->getAttentionRequired();
    }

    /**
     * @return boolean
     */
    public function isExpired()
    {
        if (null !== $this->getExpirationDate()) {
            $this->setExpired($this->getExpirationDate()->getTimestamp() < time());
        }

        return $this->getExpired();
    }

    public function setState($state)
    {
        switch ($state) {
            case PaymentInterface::STATE_APPROVED :
                parent::setState(PaymentPeer::STATE_APPROVED);
                break;
            case PaymentInterface::STATE_APPROVING :
                parent::setState(PaymentPeer::STATE_APPROVING);
                break;
            case PaymentInterface::STATE_CANCELED :
                parent::setState(PaymentPeer::STATE_CANCELED);
                break;
            case PaymentInterface::STATE_DEPOSITED :
                parent::setState(PaymentPeer::STATE_DEPOSITED);
                break;
            case PaymentInterface::STATE_DEPOSITING :
                parent::setState(PaymentPeer::STATE_DEPOSITING);
                break;
            case PaymentInterface::STATE_EXPIRED :
                parent::setState(PaymentPeer::STATE_EXPIRED);
                break;
            case PaymentInterface::STATE_FAILED :
                parent::setState(PaymentPeer::STATE_FAILED);
                break;
            case PaymentInterface::STATE_NEW :
                parent::setState(PaymentPeer::STATE_NEW);
                break;
            default:
                parent::setState($state);
                break;
        }
    }

    public function addTransaction($transaction)
    {
        $this->addFinancialTransaction($transaction);
    }

    public function getState()
    {
        if (null === parent::getState()) {
            return null;
        }

        return constant('JMS\Payment\CoreBundle\Model\PaymentInterface::STATE_'.strtoupper(parent::getState()));
    }
}
