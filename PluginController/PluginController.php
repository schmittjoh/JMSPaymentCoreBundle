<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\Plugin\PluginInterface;
use Bundle\PaymentBundle\Util\Number;
use Bundle\PaymentBundle\Entity\PaymentInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\Entity\FinancialTransactionInterface;
use Bundle\PaymentBundle\PluginController\Exception\Exception;

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

            $class = &$this->options['financial_transaction_class'];
            $transaction = new $class;
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE);
            $retry = false;
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
        
        $payment->setApprovingAmount($amount);
        $plugin = $this->findPlugin($instruction->getPaymentSystemName());
        
    }
    
    protected function findPlugin($name)
    {
        
    }
}