<?php

namespace JMS\Payment\CoreBundle\Plugin;

use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;

/**
 * Successful transaction plugin.
 *
 * This plugin can be used for unit testing to simulate a successful
 * transaction without actually performing any interaction with a payment
 * backend system.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SuccessfulTransactionPlugin extends AbstractPlugin
{
    public function approve(FinancialTransactionInterface $transaction, $retry = false)
    {
        $this->process($transaction);
    }

    public function approveAndDeposit(FinancialTransactionInterface $transaction, $retry = false)
    {
        $this->process($transaction);
    }

    public function deposit(FinancialTransactionInterface $transaction, $retry = false)
    {
        $this->process($transaction);
    }

    public function credit(FinancialTransactionInterface $transaction, $retry = false)
    {
        $this->process($transaction);
    }

    public function reverseApproval(FinancialTransactionInterface $transaction, $retry = false)
    {
        $this->process($transaction);
    }

    public function reverseDeposit(FinancialTransactionInterface $transaction, $retry = false)
    {
        $this->process($transaction);
    }

    public function reverseCredit(FinancialTransactionInterface $transaction, $retry = false)
    {
        $this->process($transaction);
    }

    public function processes($paymentSystemName)
    {
        return 'success' === $paymentSystemName;
    }

    public function isIndependentCreditSupported()
    {
        return true;
    }

    private function process(FinancialTransactionInterface $transaction)
    {
        $transaction->setProcessedAmount($transaction->getRequestedAmount());
        $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_SUCCESS);
    }
}
