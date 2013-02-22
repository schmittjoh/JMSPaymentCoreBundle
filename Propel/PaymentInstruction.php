<?php

namespace JMS\Payment\CoreBundle\Propel;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Propel\om\BasePaymentInstruction;

class PaymentInstruction extends BasePaymentInstruction implements PaymentInstructionInterface
{
    public function __construct($amount = null, $currency = null, $paymentSystemName = null, ExtendedData $data = null)
    {
        parent::__construct();

        if (null !== $data) {
            $this->setExtendedData($data);
        }

        if (null !== $amount) {
            $this->setAmount($amount);
        }

        if (null !== $currency) {
            $this->setCurrency($currency);
        }

        if (null !== $paymentSystemName) {
            $this->setPaymentSystemName($paymentSystemName);
        }
    }

    /**
     * This method adds a Credit container to this PaymentInstruction.
     *
     * @param Credit $credit
     */
    public function addCredit(Credit $credit)
    {
        if ($credit->getPaymentInstruction() !== $this) {
            throw new \InvalidArgumentException('This credit container belongs to another instruction.');
        }

        parent::addCredit($credit);
    }

    /**
     * This method adds a Payment container to this PaymentInstruction.
     *
     * @param Payment $payment
     */
    public function addPayment(Payment $payment)
    {
        if ($payment->getPaymentInstruction() !== $this) {
            throw new \InvalidArgumentException('This payment container belongs to another instruction.');
        }

        parent::addPayment($payment);
    }

    /**
     * @return JMS\Payment\CoreBundle\Propel\FinancialTransaction
     */
    public function getPendingTransaction()
    {
        foreach ($this->getPayments() as $payment) {
            if (null !== $transaction = $payment->getPendingTransaction()) {
                return $transaction;
            }
        }

        foreach ($this->getCredits() as $credit) {
            if (null !== $transaction = $credit->getPendingTransaction()) {
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

    public function setState($state)
    {
        switch ($state) {
            case PaymentInstructionInterface::STATE_CLOSED :
                parent::setState(PaymentInstructionPeer::STATE_CLOSED);
                break;
            case PaymentInstructionInterface::STATE_INVALID :
                parent::setState(PaymentInstructionPeer::STATE_INVALID);
                break;
            case PaymentInstructionInterface::STATE_NEW :
                parent::setState(PaymentInstructionPeer::STATE_NEW);
                break;
            case PaymentInstructionInterface::STATE_VALID :
                parent::setState(PaymentInstructionPeer::STATE_VALID);
                break;
            default:
                parent::setState($state);
                break;
        }
    }

    public function getState()
    {
        if (null === parent::getState()) {
            return null;
        }

        return constant('JMS\Payment\CoreBundle\Model\PaymentInstructionInterface::STATE_'.strtoupper(parent::getState()));
    }
}
