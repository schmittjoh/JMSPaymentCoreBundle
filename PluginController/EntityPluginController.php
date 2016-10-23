<?php

namespace JMS\Payment\CoreBundle\PluginController;

use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManager;
use JMS\Payment\CoreBundle\Entity\Payment;
use JMS\Payment\CoreBundle\Entity\PaymentInstruction;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException as PluginFunctionNotSupportedException;
use JMS\Payment\CoreBundle\Plugin\QueryablePluginInterface;
use JMS\Payment\CoreBundle\PluginController\Exception\Exception;
use JMS\Payment\CoreBundle\PluginController\Exception\PaymentInstructionNotFoundException;
use JMS\Payment\CoreBundle\PluginController\Exception\PaymentNotFoundException;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/*
 * Copyright 2010 Johannes M. Schmitt <schmittjoh@gmail.com>
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * A concrete plugin controller implementation using the Doctrine ORM.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class EntityPluginController extends PluginController
{
    protected $entityManager;

    public function __construct(EntityManager $entityManager, $options = array(), EventDispatcherInterface $dispatcher = null)
    {
        parent::__construct($options, $dispatcher);

        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function approveAndDeposit($paymentId, $amount)
    {
        $this->entityManager->getConnection()->beginTransaction();

        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doApproveAndDeposit($payment, $amount);

            $this->entityManager->persist($payment);
            $this->entityManager->persist($result->getFinancialTransaction());
            $this->entityManager->persist($result->getPaymentInstruction());
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->doRollback($failure);
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
        }
    }

    /**
     * {@inheritdoc}
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCredit($id)
    {
        // FIXME: also retrieve the associated PaymentInstruction
        $credit = $this->entityManager->getRepository($this->options['credit_class'])->find($id, LockMode::PESSIMISTIC_WRITE);

        if (null === $credit) {
            throw new CreditNotFoundException(sprintf('The credit with ID "%s" was not found.', $id));
        }

        $plugin = $this->getPlugin($credit->getPaymentInstruction()->getPaymentSystemName());
        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updateCredit($credit);

                $this->entityManager->persist($credit);
                $this->entityManager->flush();
            } catch (PluginFunctionNotSupportedException $notSupported) {
            }
        }

        return $credit;
    }

    /**
     * {@inheritdoc}
     */
    public function getPayment($id)
    {
        $payment = $this->entityManager->getRepository($this->options['payment_class'])->find($id, LockMode::PESSIMISTIC_WRITE);

        if (null === $payment) {
            throw new PaymentNotFoundException(sprintf('The payment with ID "%d" was not found.', $id));
        }

        $plugin = $this->getPlugin($payment->getPaymentInstruction()->getPaymentSystemName());
        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updatePayment($payment);

                $this->entityManager->persist($payment);
                $this->entityManager->flush();
            } catch (PluginFunctionNotSupportedException $notSupported) {
            }
        }

        return $payment;
    }

    /**
     * {@inheritdoc}
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
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
        } catch (\Exception $failure) {
            $this->doRollback($failure);
        }
    }

    protected function buildCredit(PaymentInstructionInterface $paymentInstruction, $amount)
    {
        $class =&$this->options['credit_class'];
        $credit = new $class($paymentInstruction, $amount);

        return $credit;
    }

    protected function buildFinancialTransaction()
    {
        $class =&$this->options['financial_transaction_class'];

        return new $class();
    }

    protected function createFinancialTransaction(PaymentInterface $payment)
    {
        if (!$payment instanceof Payment) {
            throw new Exception('This controller only supports Doctrine2 entities as Payment objects.');
        }

        $class =&$this->options['financial_transaction_class'];
        $transaction = new $class();
        $payment->addTransaction($transaction);

        return $transaction;
    }

    protected function doCreatePayment(PaymentInstructionInterface $instruction, $amount)
    {
        if (!$instruction instanceof PaymentInstruction) {
            throw new Exception('This controller only supports Doctrine2 entities as PaymentInstruction objects.');
        }

        $class =&$this->options['payment_class'];

        return new $class($instruction, $amount);
    }

    protected function doCreatePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $this->entityManager->persist($instruction);
        $this->entityManager->flush();
    }

    protected function doGetPaymentInstruction($id)
    {
        $paymentInstruction = $this->entityManager->getRepository($this->options['payment_instruction_class'])->findOneBy(array('id' => $id));

        if (null === $paymentInstruction) {
            throw new PaymentInstructionNotFoundException(sprintf('The payment instruction with ID "%d" was not found.', $id));
        }

        return $paymentInstruction;
    }

    protected function doRollback(\Exception $failure)
    {
        $this->entityManager->getConnection()->rollback();

        throw $failure;
    }
}
