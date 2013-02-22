<?php

namespace JMS\Payment\CoreBundle\Propel;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Propel\om\BaseFinancialTransaction;

/**
 * Financial transaction entity
 */
class FinancialTransaction extends BaseFinancialTransaction implements FinancialTransactionInterface
{
    public function getExtendedData(PropelPDO $con = null, $doQuery = true)
    {
        if (null !== ($data = parent::getExtendedData($con, $doQuery))) {
            return $data;
        }

        if (null !== $this->getPayment()) {
            return $this->getPayment()->getPaymentInstruction()->getExtendedData();
        } elseif (null !== $this->getCredit()) {
            return $this->getCredit()->getPaymentInstruction()->getExtendedData();
        }

        return null;
    }

    public function setTransactionType($transactionType)
    {
        switch ($transactionType) {
            case FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE :
                parent::setTransactionType(FinancialTransactionPeer::TRANSACTION_TYPE_APPROVE);
                break;
            case FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT :
                parent::setTransactionType(FinancialTransactionPeer::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT);
                break;
            case FinancialTransactionInterface::TRANSACTION_TYPE_CREDIT :
                parent::setTransactionType(FinancialTransactionPeer::TRANSACTION_TYPE_CREDIT);
                break;
            case FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT :
                parent::setTransactionType(FinancialTransactionPeer::TRANSACTION_TYPE_DEPOSIT);
                break;
            case FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_APPROVAL :
                parent::setTransactionType(FinancialTransactionPeer::TRANSACTION_TYPE_REVERSE_APPROVAL);
                break;
            case FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_CREDIT :
                parent::setTransactionType(FinancialTransactionPeer::TRANSACTION_TYPE_REVERSE_CREDIT);
                break;
            case FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_DEPOSIT :
                parent::setTransactionType(FinancialTransactionPeer::TRANSACTION_TYPE_REVERSE_DEPOSIT);
                break;
            default:
                parent::setTransactionType($transactionType);
                break;
        }
    }

    public function getTransactionType()
    {
        if (null === parent::getTransactionType()) {
            return null;
        }

        $constantName = strtoupper(parent::getTransactionType());
        $constantName = str_replace('-', '_', $constantName);

        return constant('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface::TRANSACTION_TYPE_' . $constantName);
    }

    public function setState($state)
    {
        switch ($state) {
            case FinancialTransactionInterface::STATE_CANCELED :
                parent::setState(FinancialTransactionPeer::STATE_CANCELED);
                break;
            case FinancialTransactionInterface::STATE_FAILED :
                parent::setState(FinancialTransactionPeer::STATE_FAILED);
                break;
            case FinancialTransactionInterface::STATE_NEW :
                parent::setState(FinancialTransactionPeer::STATE_NEW);
                break;
            case FinancialTransactionInterface::STATE_PENDING :
                parent::setState(FinancialTransactionPeer::STATE_PENDING);
                break;
            case FinancialTransactionInterface::STATE_SUCCESS :
                parent::setState(FinancialTransactionPeer::STATE_SUCCESS);
                break;
            default:
                parent::setState($state);
                break;
        }
    }

    public function getState()
    {
        return constant('JMS\Payment\CoreBundle\Model\FinancialTransactionInterface::STATE_'.strtoupper(parent::getState()));
    }
}
