<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\Entity\FinancialTransaction;
use Bundle\PaymentBundle\Entity\Payment;
use Bundle\PaymentBundle\Entity\PaymentInstruction;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\Entity\PaymentInterface;
use Bundle\PaymentBundle\PluginController\Exception\Exception;
use Bundle\PaymentBundle\PluginController\Exception\PaymentNotFoundException;
use Bundle\PaymentBundle\PluginController\Exception\PaymentInstructionNotFoundException;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;

// FIXME: implement remaining methods
class EntityPluginController extends PluginController
{
    protected $creditClass;
    protected $entityManager;
    protected $paymentClass;
    protected $paymentInstructionClass;
    
    public function __construct(EntityManager $entityManager, $paymentInstructionClass, $paymentClass, $creditClass, $options = array())
    {
        parent::__construct($options);
        
        $this->creditClass = $creditClass;
        $this->entityManager = $entityManager;
        $this->paymentClass = $paymentClass;
        $this->paymentInstructionClass = $paymentInstructionClass;
    }
    
    public function approve($paymentId, $amount) 
    {
        $this->entityManager->getConnection()->beginTransaction();
        
        try {
            $payment = $this->getPayment($paymentId, LockMode::PESSIMISTIC_WRITE);
            
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
    
    public function getCredit($id)
    {
        $credit = $this->creditRepository->findOneBy(array('id' => $id));
        
        if (null === $credit) {
            throw new CreditNotFoundException(sprintf('The credit with ID "%s" was not found.', $id));
        }
        
        return $credit;
    }
    
    public function getPayment($id, $lockMode = LockMode::NONE)
    {
        $payments = $this->entityManager->createQuery('SELECT p FROM '.$paymentClass.' p JOIN p.PaymentInstruction i JOIN p.Transactions t WHERE u.id = :id')
                      ->setLockMode($lockMode)
                      ->setParameter('id', $id)
                      ->getResult()
        ;
        
                    
        $payment = $this->paymentRepository->findOneBy(array('id' => $id));
        
        if (null === $payment) {
            throw new PaymentNotFoundException(sprintf('The payment with ID "%d" was not found.', $id));
        }
        
        return $payment;
    }
    
    protected function createFinancialTransaction(PaymentInterface $payment)
    {
        if (!$payment instanceof Payment) {
            throw new Exception('This controller only supports Doctrine2 entities as Payment objects.');
        }
        
        $transaction = new FinancialTransaction();
        $payment->addTransaction($transaction);
        
        return $transaction;
    }
    
    protected function doCreatePayment(PaymentInstructionInterface $instruction)
    {
        if (!$instruction instanceof PaymentInstruction) {
            throw new Exception('This controller only supports Doctrine2 entities as PaymentInstruction objects.');
        }
        
        $payment = new Payment();
        $instruction->addPayment($payment);
        
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