<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\Plugin\QueryablePluginInterface;
use Bundle\PaymentBundle\Entity\FinancialTransaction;
use Bundle\PaymentBundle\Entity\Payment;
use Bundle\PaymentBundle\Entity\PaymentInstruction;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\Entity\PaymentInterface;
use Bundle\PaymentBundle\PluginController\Exception\Exception;
use Bundle\PaymentBundle\PluginController\Exception\PaymentNotFoundException;
use Bundle\PaymentBundle\PluginController\Exception\PaymentInstructionNotFoundException;
use Bundle\PaymentBundle\Plugin\Exception\FunctionNotSupportedException as PluginFunctionNotSupportedException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;

// FIXME: implement remaining methods
class EntityPluginController extends PluginController
{
    protected $entityManager;
    
    public function __construct(EntityManager $entityManager, $options = array())
    {
        parent::__construct($options);
        
        $this->entityManager = $entityManager;
    }
    
    /**
     * {@inheritDoc}
     */
    public function approve($paymentId, $amount) 
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $payment = $this->getPayment($paymentId);
            
            $result = $this->doApprove($payment, $amount);
            
            $this->entityManager->persist($payment);
            $this->entityManager->persist($result->getFinancialTransaction());
            $this->entityManager->persist($result->getPaymentInstruction());
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $result;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }
    
    public function closePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        parent::closePaymentInstruction($instruction);
        
        $this->entityManager->persist($instruction);
        $this->entityManager->flush();
    }
    
    public function createDependentCredit($paymentId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $payment = $this->getPayment($paymentId);
            
            $credit = $this->doCreateDependentCredit($payment, $amount);
            
            $this->entityManager->persist($payment->getPaymentInstruction());
            $this->entityManager->persist($payment);
            $this->entityManager->persist($credit);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $credit;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }

    public function createIndependentCredit($paymentInstructionId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $instruction = $this->getPaymentInstruction($paymentInstructionId, false);

            $credit = $this->doCreateIndependentCredit($instruction, $amount);
            
            $this->entityManager->persist($instruction);
            $this->entityManager->persist($credit);
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $credit;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }
    
    public function createPayment($instructionId, $amount)
    {
        $payment = parent::createPayment($instructionId, $amount);
        
        $this->entityManager->persist($payment);
        $this->entityManager->flush();
        
        return $payment;
    }
    
    public function credit($creditId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $credit = $this->getCredit($creditId);
            
            $result = $this->doCredit($credit, $amount);
            
            $this->entityManager->persist($credit);
            $this->entityManager->persist($result->getFinancialTransaction());
            $this->entityManager->persist($result->getPaymentInstruction());
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $result;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function deposit($paymentId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $payment = $this->getPayment($paymentId);
            
            $result = $this->doDeposit($payment, $amount);
            
            $this->entityManager->persist($payment);
            $this->entityManager->persist($result->getFinancialTransaction());
            $this->entityManager->persist($result->getPaymentInstruction());
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $result;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }
    
    /**
     * {@inheritDoc}
     */
    public function getCredit($id)
    {
        // FIXME: also retrieve the associated PaymentInstruction
        $credit = $this->creditRepository->findOneBy(array('id' => $id));
        
        if (null === $credit) {
            throw new CreditNotFoundException(sprintf('The credit with ID "%s" was not found.', $id));
        }
        
        $plugin = $this->findPlugin($credit->getPaymentInstruction()->getPaymentSystemName());
        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updateCredit($credit);
                
                $this->entityManager->persist($credit);
                $this->entityManager->flush();
            }
            catch (PluginFunctionNotSupportedException $notSupported) {}
        }
        
        return $credit;
    }
    
    /**
     * {@inheritDoc}
     */
    public function getPayment($id)
    {
        // FIXME: also retrieve the related PaymentInstruction
        $payment = $this->paymentRepository->findOneBy(array('id' => $id));
        
        if (null === $payment) {
            throw new PaymentNotFoundException(sprintf('The payment with ID "%d" was not found.', $id));
        }
        
        $plugin = $this->findPlugin($payment->getPaymentInstruction()->getPaymentSystemName());
        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updatePayment($payment);
                
                $this->entityManager->persist($payment);
                $this->entityManager->flush();
            }
            catch (PluginFunctionNotSupportedException $notSupported) {}
        }
        
        return $payment;
    }
    
    /**
     * {@inheritDoc}
     */
    public function reverseApproval($paymentId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $payment = $this->getPayment($paymentId);
            
            $result = $this->doReverseApproval($payment, $amount);
            
            $this->entityManager->persist($payment);
            $this->entityManager->persist($result->getFinancialTransaction());
            $this->entityManager->persist($result->getPaymentInstruction());
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $result;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }
    
    public function reverseCredit($creditId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $credit = $this->getCredit($creditId);
            
            $result = $this->doReverseCredit($credit, $amount);
            
            $this->entityManager->persist($credit);
            $this->entityManager->persist($result->getFinancialTransaction());
            $this->entityManager->persist($result->getPaymentInstruction());
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $result;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }
    
    public function reverseDeposit($paymentId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $payment = $this->getPayment($paymentId);
            
            $result = $this->doReverseDeposit($payment, $amount);
            
            $this->entityManager->persist($payment);
            $this->entityManager->persist($result->getFinancialTransaction());
            $this->entityManager->persist($result->getPaymentInstruction());
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            
            return $result;
        }
        catch (\Exception $failure) {
            $this->entityManager->getConnection()->rollback();
            $this->entityManager->close();
            
            throw $failure;
        }
    }
    
    protected function buildCredit(PaymentInstructionInterface $paymentInstruction, $amount)
    {
        $class =& $this->options['credit_class'];
        $credit = new $class($paymentInstruction, $amount);
        
        return $credit;
    }
    
    protected function createFinancialTransaction(PaymentInterface $payment)
    {
        if (!$payment instanceof Payment) {
            throw new Exception('This controller only supports Doctrine2 entities as Payment objects.');
        }
        
        $class =& $this->options['financial_transaction_class'];
        $transaction = new $class();
        $payment->addTransaction($transaction);
        
        return $transaction;
    }
    
    protected function doCreatePayment(PaymentInstructionInterface $instruction, $amount)
    {
        if (!$instruction instanceof PaymentInstruction) {
            throw new Exception('This controller only supports Doctrine2 entities as PaymentInstruction objects.');
        }
        
        $class =& $this->options['payment_class'];
        $payment = new $class($instruction);
        $payment->setTargetAmount($amount);
        
        return $payment;
    }
    
    protected function doGetPaymentInstruction($id)
    {
        $paymentInstruction = $this->paymentInstructionRepository->findOneBy(array('id' => $id));
        
        if (null === $paymentInstruction) {
            throw new PaymentInstructionNotFoundException(sprintf('The payment instruction with ID "%d" was not found.', $id));
        }
        
        return $paymentInstruction;
    }
}