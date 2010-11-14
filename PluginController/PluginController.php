<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\Plugin\PluginInterface;
use Bundle\PaymentBundle\Util\Number;
use Bundle\PaymentBundle\Entity\PaymentInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\Entity\FinancialTransactionInterface;
use Bundle\PaymentBundle\PluginController\Exception\Exception;
use Bundle\PaymentBundle\PluginController\Exception\PluginNotFoundException;
use Bundle\PaymentBundle\Plugin\PluginInterface;
use Bundle\PaymentBundle\Plugin\Exception\Exception as PluginException;
use Bundle\PaymentBundle\Plugin\Exception\TimeoutException as PluginTimeoutException;

abstract class PluginController implements PluginControllerInterface
{
    protected $options;
    protected $plugins;
    
    public function __construct(array $options = array())
    {
        $this->options = $options;
        $this->plugins[] = array();
    }
    
    public function addPlugin(PluginInterface $plugin)
    {
        $this->plugins[] = $plugin;
    }
    
    public function getPaymentInstruction($instructionId, $maskSensitiveData = true)
    {
        $paymentInstruction = $this->doGetPaymentInstruction();
        
        if (true === $maskSensitiveData) {
            // FIXME: mask sensitive data    
        }
        
        return $paymentInstruction;
    }

    abstract protected function doGetPaymentInstruction($instructionId);
    
    // FIXME: Add transaction management / locking
    public function approve($paymentId, $amount)
    {
        $payment = $this->getPayment($paymentId);
        $instruction = $payment->getPaymentInstruction();
        
        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new Exception('The PaymentInstruction\'s state must be STATE_VALID.');
        }
        
        if (PaymentInterface::STATE_NEW === $payment->getState()) {
            if (Number::compare($payment->getTargetAmount(), $amount) < 0) {
                throw new Exception('The Payment\'s target amount is less than the requested amount.');
            }
            
            if ($instruction->hasPendingTransaction()) {
                throw new Exception('The PaymentInstruction can only ever have one pending transaction.');
            }

            $transaction = $this->createFinancialTransaction();
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE);
            $transaction->setPayment($payment);
            $transaction->setRequestedAmount($amount);
            $retry = false;

            $payment->setState(PaymentInterface::STATE_APPROVING);
            $payment->setApprovingAmount($amount);
            $instruction->setApprovingAmount($instruction->getApprovingAmount() + $amount);
        }
        else if (PaymentInterface::STATE_APPROVING === $payment->getState()) {
            if (Number::compare($payment->getTargetAmount(), $amount) !== 0) {
                throw new Exception('The Payment\'s target amount must equal the requested amount in a retry transaction.');
            }
            
            $transaction = $payment->getApproveTransaction();
            $retry = true;
        }
        else {
            throw new Exception('The Payment\'s state must be STATE_NEW, or STATE_APPROVING.');
        }
        
        $plugin = $this->findPlugin($instruction->getPaymentSystemName());
        
        try {
            $plugin->approve($transaction, $retry);
            
            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $payment->setState(PaymentInterface::STATE_APPROVED);
                $payment->setApprovingAmount(0.0);
                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                $instruction->setApprovedAmount($instruction->getApprovedAmount() + $transaction->getProcessedAmount());
                
                $result = $this->createResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            }
            else {
                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setApprovingAmount(0.0);
                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                
                $result = $this->createResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        }
        catch (PluginTimeoutException $timeout) {
            $result = $this->createResult($transaction, Result::STATUS_PENDING, PluginInterface::REASON_CODE_TIMEOUT);
            $result->setPluginException($timeout);
            $result->setRecoverable();
        }
        catch (PluginException $failed) {
            $result = $this->createResult($transaction, Result::STATUS_UNKNOWN, $transaction->getReasonCode());
            $result->setPluginException($failed);
            $result->setPaymentRequiresAttention();
        }
        
        return $result;
    }
    
    protected function createFinancialTransaction()
    {
        $class = &$this->options['financial_transaction_class'];
        
        return new $class;
    }
    
    protected function createResult(FinancialTransactionInterface $transaction, $status, $reasonCode)
    {
        $class = &$this->options['result_class'];
        
        return new $class($transaction, $status, $reasonCode);
    }
    
    protected function findPlugin($paymentSystemName)
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->processes($paymentSystemName)) {
                return $plugin;
            }
        }
        
        throw new PluginNotFoundException(sprintf('There is no plugin that processes payments for "%s".', $paymentSystemName));
    }
}