<?php

namespace Bundle\PaymentBundle\PluginController;

use Bundle\PaymentBundle\PluginController\Exception\PaymentNotFoundException;
use Bundle\PaymentBundle\PluginController\Exception\PaymentInstructionNotFoundException;

// FIXME: implement remaining methods
class PluginController implements PluginControllerInterface
{
    protected $entityManager;
    protected $paymentInstructionRepository;
    protected $paymentRepository;
    protected $creditRepository;
    
    public function __construct($entityManager, $paymentInstructionClass, $paymentClass, $creditClass)
    {
        $this->entityManager = $entityManager;
        $this->paymentInstructionRepository = $entityManager->getRepository($paymentInstructionClass);
        $this->paymentRepository = $entityManager->getRepository($paymentClass);
        $this->creditRepository = $entityManager->getRepository($creditClass);
    }
    
    public function getPayment($id)
    {
        $payment = $this->paymentRepository->findOneBy(array('id' => $id));
        
        if (null === $payment) {
            throw new PaymentNotFoundException(sprintf('The payment with ID "%d" was not found.', $id));
        }
        
        return $payment;
    }
    
    public function getPaymentInstruction($id, $maskSensitiveData = true)
    {
        $paymentInstruction = $this->paymentInstructionRepository->findOneBy(array('id' => $id));
        
        if (null === $paymentInstruction) {
            throw new PaymentInstructionNotFoundException(sprintf('The payment instruction with ID "%d" was not found.', $id));
        }
        
        // FIXME: mask sensitive data
        
        return $paymentInstruction;
    }
    
    public function getCredit($id)
    {
        $credit = $this->creditRepository->findOneBy(array('id' => $id));
        
        if (null === $credit) {
            throw new CreditNotFoundException(sprintf('The credit with ID "%s" was not found.', $id));
        }
        
        return $credit;
    }
}