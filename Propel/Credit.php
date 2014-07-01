<?php

namespace JMS\Payment\CoreBundle\Propel;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Propel\om\BaseCredit;

/**
 * Credit entity
 */
class Credit extends BaseCredit implements CreditInterface
{
    public function __construct(PaymentInstructionInterface $paymentInstruction = null, $amount = 0.0)
    {
        parent::__construct();

        $this->setPaymentInstruction($paymentInstruction);
        $this->setTargetAmount($amount);
    }

    /**
     * @return FinancialTransactionInterface
     */
    public function getCreditTransaction()
    {
        foreach ($this->getFinancialTransactions() as $transaction) {
            if (FinancialTransactionInterface::TRANSACTION_TYPE_CREDIT === $transaction->getTransactionType()) {
                return $transaction;
            }
        }

        return null;
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
     * @return array
     */
    public function getReverseCreditTransactions()
    {
        $objects = new \PropelObjectCollection();

        foreach($this->getFinancialTransactions() as $transaction) {
            if ($transaction->getTransactionType() === FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_CREDIT) {
                $objects->append($transaction);
            }
        }

        return $objects;
    }

    /**
     * @return boolean
     */
    public function hasPendingTransaction()
    {
        return null !== $this->getPendingTransaction();
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
    public function isIndependent()
    {
        return null === $this->payment;
    }

    public function setState($state)
    {
        switch ($state) {
            case CreditInterface::STATE_CANCELED :
                parent::setState('canceled');
                break;
            case CreditInterface::STATE_CREDITED :
                parent::setState('credited');
                break;
            case CreditInterface::STATE_CREDITING :
                parent::setState('crediting');
                break;
            case CreditInterface::STATE_FAILED :
                parent::setState('failed');
                break;
            case CreditInterface::STATE_NEW :
                parent::setState('new');
                break;
            default:
                parent::setState($state);
                break;
        }
    }

    public function getState()
    {
        return constant('JMS\Payment\CoreBundle\Model\CreditInterface::STATE_'.strtoupper(parent::getState()));
    }
}
