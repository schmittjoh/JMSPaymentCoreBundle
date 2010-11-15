<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\Util\Number;
use Bundle\PaymentBundle\Entity\PaymentInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\Entity\FinancialTransactionInterface;
use Bundle\PaymentBundle\PluginController\Exception\Exception;
use Bundle\PaymentBundle\PluginController\Exception\PluginNotFoundException;
use Bundle\PaymentBundle\PluginController\Exception\InvalidPaymentException;
use Bundle\PaymentBundle\PluginController\Exception\InvalidPaymentInstructionException;
use Bundle\PaymentBundle\Plugin\PluginInterface;
use Bundle\PaymentBundle\Plugin\QueryablePluginInterface;
use Bundle\PaymentBundle\Plugin\Exception\Exception as PluginException;
use Bundle\PaymentBundle\Plugin\Exception\TimeoutException as PluginTimeoutException;
use Bundle\PaymentBundle\Plugin\Exception\InvalidPaymentInstructionException as PluginInvalidPaymentInstructionException;
use Bundle\PaymentBundle\Plugin\Exception\FunctionNotSupportedException as PluginFunctionNotSupportedException;

abstract class PluginController implements PluginControllerInterface
{
    protected $options;
    protected $plugins;
    
    public function __construct(array $options = array())
    {
        $this->options = $options;
        $this->plugins = array();
    }
    
    public function addPlugin(PluginInterface $plugin)
    {
        $this->plugins[] = $plugin;
    }
    
    // FIXME: Add transaction management / locking
    public function approve($paymentId, $amount)
    {
        $payment = $this->getPayment($paymentId);
        $instruction = $payment->getPaymentInstruction();
        
        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('The PaymentInstruction\'s state must be STATE_VALID.');
        }
        
        $paymentState = $payment->getState();
        if (PaymentInterface::STATE_NEW === $paymentState) {
            if (Number::compare($payment->getTargetAmount(), $amount) < 0) {
                throw new Exception('The Payment\'s target amount is less than the requested amount.');
            }
            
            if ($instruction->hasPendingTransaction()) {
                throw new InvalidPaymentInstructionException('The PaymentInstruction can only ever have one pending transaction.');
            }

            $retry = false;
            
            $transaction = $this->createFinancialTransaction($payment);
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE);
            $transaction->setRequestedAmount($amount);

            $payment->setState(PaymentInterface::STATE_APPROVING);
            $payment->setApprovingAmount($amount);
            $instruction->setApprovingAmount($instruction->getApprovingAmount() + $amount);
        }
        else if (PaymentInterface::STATE_APPROVING === $paymentState) {
            if (Number::compare($payment->getTargetAmount(), $amount) !== 0) {
                throw new Exception('The Payment\'s target amount must equal the requested amount in a retry transaction.');
            }
            
            $transaction = $payment->getApproveTransaction();
            $retry = true;
        }
        else {
            throw new InvalidPaymentException('The Payment\'s state must be STATE_NEW, or STATE_APPROVING.');
        }
        
        $plugin = $this->findPlugin($instruction->getPaymentSystemName());
        
        try {
            $plugin->approve($transaction, $retry);
            
            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $payment->setState(PaymentInterface::STATE_APPROVED);
                $payment->setApprovingAmount(0.0);
                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                $instruction->setApprovedAmount($instruction->getApprovedAmount() + $transaction->getProcessedAmount());
                
                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            }
            else {
                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setApprovingAmount(0.0);
                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                
                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        }
        catch (PluginTimeoutException $timeout) {
            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, PluginInterface::REASON_CODE_TIMEOUT);
            $result->setPluginException($timeout);
            $result->setRecoverable();
            
            return $result;
        }
        // FIXME: These should be catched, and rethrown by the controller which actually implements Transaction Management
        //        such as EntityPluginController
//        catch (PluginException $failed) {
//            // FIXME: rollback the entire changes
//            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_UNKNOWN, $transaction->getReasonCode());
//            $result->setPluginException($failed);
//            $result->setPaymentRequiresAttention();
//        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $plugin = $this->findPlugin($instruction->getPaymentSystemName());
        
        try {
            $plugin->checkPaymentInstruction($instruction);
            
            return $this->onSuccessfulPaymentInstructionValidation($instruction);
        }
        catch (PluginFunctionNotSupportedException $notSupported) {
            return $this->onSuccessfulPaymentInstructionValidation($instruction);
        }
        catch (PluginInvalidPaymentInstructionException $invalidInstruction) {
            return $this->onUnsuccessfulPaymentInstructionValidation($instruction, $invalidInstruction);
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function closePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $instruction->setState(PaymentInstructionInterface::STATE_CLOSED);
    }
    
    /**
     * {@inheritDoc}
     */
    public function createPayment($instructionId, $amount)
    {
        $instruction = $this->getPaymentInstruction($instructionId, false);
        
        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('The PaymentInstruction must be in STATE_VALID.');
        }
        
        // FIXME: Is it practical to check this at all? There can be many payments, credits, etc.
        //        Verify that this is consistent with the checks related to transactions
//        if (Number::compare($amount, $instruction->getAmount()) === 1) {
//            throw new Exception('The Payment\'s target amount must not be greater than the PaymentInstruction\'s amount.');
//        }
        
        $payment = $this->doCreatePayment($instruction);
        $payment->setTargetAmount($amount);
        
        return $payment;
    }
    
    /**
     * {@inheritDoc}
     */
    public function deposit($paymentId, $amount)
    {
        $payment = $this->getPayment($paymentId);
        $instruction = $payment->getPaymentInstruction();
        
        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('The PaymentInstruction must be in STATE_VALID.');
        }
        
        $paymentState = $payment->getState();
        if (PaymentInstructionInterface::STATE_APPROVED === $paymentState) {
            if ($instruction->hasPendingTransaction()) {
                throw new InvalidPaymentInstructionException('The PaymentInstruction can only have one pending transaction at a time.');
            }
            
            if (Number::compare($amount, $payment->getApprovedAmount()) === 1) {
                throw new Exception('The amount cannot be greater than the approved amount of the Payment.');
            }
            
            $retry = false;
            
            $transaction = $this->createFinancialTransaction($payment);
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT);
            $transaction->setRequestedAmount($amount);
            
            $payment->setState(PaymentInterface::STATE_DEPOSITING);
            $payment->setDepositingAmount($amount);
            $instruction->setDepositingAmount($instruction->getDepositingAmount() + $amount);
        }
        else if (PaymentInstructionInterface::STATE_DEPOSITING === $paymentState) {
            $transaction = $instructin->getPendingTransaction();
            if (null === $transaction) {
                if (Number::compare($amount, $payment->getApprovedAmount() - $payment->getDepositedAmount()) === 1) {
                    throw new Exception('The amount cannot be greater than the approved amount minus the already deposited amount.');
                }
                
                $retry = false;
                
                $transaction = $this->createFinancialTransaction($payment);
                $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT);
                $transaction->setRequestedAmount($amount);
                
                $payment->setDepositingAmount($amount);
                $instruction->setDepositingAmount($instruction->getDepositingAmount() + $amount);
            }
            else {
                if ($transaction->getPayment()->getId() !== $payment->getId()) {
                    throw new InvalidPaymentInstructionException('The PaymentInstruction has a pending transaction on another Payment.');
                }
                
                if (Number::compare($transaction->getRequestedAmount(), $amount) !== 0) {
                    throw new Exception('The requested amount must be equal to the transaction\'s amount when retrying.');
                }
                
                $retry = true;
            }
        }
        else {
            throw new InvalidPaymentException('The Payment must be in STATE_APPROVED, or STATE_DEPOSITING.');
        }
        
        $plugin = $this->findPlugin($instruction->getPaymentSystemName());
        
        try {
            $plugin->deposit($transaction, $retry);
            
            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $payment->setDepositingAmount(0.0);
                $payment->setDepositedAmount($depositedAmount = $payment->getDepositedAmount() + $transaction->getProcessedAmount());
                
                if (Number::compare($depositedAmount, $payment->getApprovedAmount()) >= 0) {
                    $payment->setState(PaymentInterface::STATE_DEPOSITED);
                }
                
                $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);
                $instruction->setDepositedAmount($instruction->getDepositedAmount() + $transaction->getProcessedAmount());
                
                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            }
            else {
                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setDepositingAmount(0.0);
                $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);
                
                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        }
        catch (PluginTimeoutException $timeout) {
            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, PluginInterface::REASON_CODE_TIMEOUT);
            $result->setPluginException($timeout);
            $result->setRecoverable();
            
            return $result;
        }
    }
    
    public function getPaymentInstruction($instructionId, $maskSensitiveData = true)
    {
        $paymentInstruction = $this->doGetPaymentInstruction($instructionId);
        
        if (true === $maskSensitiveData) {
            // FIXME: mask sensitive data    
        }
        
        return $paymentInstruction;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getRemainingValueOnPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $plugin = $this->findPlugin($instruction->getPaymentSystemName());
        
        if (!$plugin instanceof QueryablePlugin) {
            return null;
        }
        
        return $plugin->getAvailableBalance($instruction);
    }
    
    /**
     * {@inheritDoc}
     */
    public function validatePaymentInstruction(PaymentInstructionInterface $paymentInstruction)
    {
        $plugin = $this->findPlugin($paymentInstruction->getPaymentSystemName());
        
        try {
            $plugin->validatePaymentInstruction($paymentInstruction);
            
            return $this->onSuccessfulPaymentInstructionValidation($paymentInstruction);
        }
        catch (PluginFunctionNotSupportedException $notSupported) {
            return $this->checkPaymentInstruction($paymentInstruction);
        }
        catch (PluginInvalidPaymentInstructionException $invalid) {
            return $this->onUnsuccessfulPaymentInstructionValidation($paymentInstruction, $invalid);
        }
    }
    
    protected function buildFinancialTransactionResult(FinancialTransactionInterface $transaction, $status, $reasonCode)
    {
        $class = &$this->options['result_class'];
        
        return new $class($transaction, $status, $reasonCode);
    }
    
    protected function buildPaymentInstructionResult(PaymentInstructionInterface $instruction, $status, $reasonCode)
    {
        $class = &$this->options['result_class'];
        
        return new $class($instruction, $status, $reasonCode);
    }
    
    abstract protected function createFinancialTransaction(PaymentInterface $payment);
    
    abstract protected function doCreatePayment($instruction);
    
    abstract protected function doGetPaymentInstruction($instructionId);
    
    protected function findPlugin($paymentSystemName)
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->processes($paymentSystemName)) {
                return $plugin;
            }
        }
        
        throw new PluginNotFoundException(sprintf('There is no plugin that processes payments for "%s".', $paymentSystemName));
    }
    
    protected function onSuccessfulPaymentInstructionValidation(PaymentInstruction $instruction)
    {
        $instruction->setState(PaymentInstructionInterface::STATE_VALID);
        
        return $this->buildPaymentInstructionResult($instruction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
    }
    
    protected function onUnsuccessfulPaymentInstructionValidation(PaymentInstruction $instruction, PluginInvalidPaymentInstructionException $invalid)
    {
        $instruction->setState(PaymentInstructionInterface::STATE_INVALID);
        
        $result = $this->buildPaymentInstructionResult($instruction, Result::STATUS_FAILED, PluginInterface::REASON_CODE_INVALID);
        $result->setPluginException($invalid);

        return $result;
    }
}