<?php

namespace JMS\Payment\CoreBundle\PluginController;

use JMS\Payment\CoreBundle\Propel\Payment;
use JMS\Payment\CoreBundle\Propel\PaymentInstruction;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\QueryablePluginInterface;
use JMS\Payment\CoreBundle\PluginController\PluginController;
use JMS\Payment\CoreBundle\PluginController\Exception\Exception;
use JMS\Payment\CoreBundle\PluginController\Exception\PaymentNotFoundException;
use JMS\Payment\CoreBundle\PluginController\Exception\PaymentInstructionNotFoundException;
use JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException as PluginFunctionNotSupportedException;

/**
 * A concrete plugin controller implementation using the Propel ORM.
 */
class PropelPluginController extends PluginController
{
    /**
     * {@inheritDoc}
     */
    public function approve($paymentId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doApprove($payment, $amount);

            $payment->save();
            $result->getFinancialTransaction()->save();
            $result->getPaymentInstruction()->save();

            $this->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function approveAndDeposit($paymentId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doApproveAndDeposit($payment, $amount);

            $payment->save();
            $result->getFinancialTransaction()->save();
            $result->getPaymentInstruction()->save();

            $this->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    public function closePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        parent::closePaymentInstruction($instruction);

        $instruction->save();
    }

    public function createDependentCredit($paymentId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $payment = $this->getPayment($paymentId);

            $credit = $this->doCreateDependentCredit($payment, $amount);

            $payment->getPaymentInstruction()->save();
            $payment->save();
            $credit->save();

            $this->getConnection()->commit();

            return $credit;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    public function createIndependentCredit($paymentInstructionId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $instruction = $this->getPaymentInstruction($paymentInstructionId, false);

            $credit = $this->doCreateIndependentCredit($instruction, $amount);

            $instruction->save();
            $credit->save();

            $this->getConnection()->commit();

            return $credit;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    public function createPayment($instructionId, $amount)
    {
        $payment = parent::createPayment($instructionId, $amount);

        $payment->save();

        return $payment;
    }

    public function credit($creditId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $credit = $this->getCredit($creditId);

            $result = $this->doCredit($credit, $amount);

            $credit->save();
            $result->getFinancialTransaction()->save();
            $result->getPaymentInstruction()->save();

            $this->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function deposit($paymentId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doDeposit($payment, $amount);

            $payment->save();
            $result->getFinancialTransaction()->save();
            $result->getPaymentInstruction()->save();

            $this->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getCredit($id)
    {
        $classname = $this->options['credit_class'] . 'Query';
        $credit = $classname::create()->findPk($id);

        if (null === $credit) {
            throw new CreditNotFoundException(sprintf('The credit with ID "%s" was not found.', $id));
        }

        $plugin = $this->getPlugin($credit->getPaymentInstruction()->getPaymentSystemName());

        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updateCredit($credit);
                $credit->save();

                } catch (PluginFunctionNotSupportedException $notSupported) {}
        }

        return $credit;
    }

    /**
     * {@inheritDoc}
     */
    public function getPayment($id)
    {
        $classname = $this->options['payment_class'].'Query';
        $payment = $classname::create()->findPk($id);

        if (null === $payment) {
            throw new PaymentNotFoundException(sprintf('The payment with ID "%d" was not found.', $id));
        }

        $plugin = $this->getPlugin($payment->getPaymentInstruction()->getPaymentSystemName());

        if ($plugin instanceof QueryablePluginInterface) {
            try {
                $plugin->updatePayment($payment);
                $payment->save();

            } catch (PluginFunctionNotSupportedException $notSupported) {}
        }

        return $payment;
    }

    /**
     * {@inheritDoc}
     */
    public function reverseApproval($paymentId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doReverseApproval($payment, $amount);

            $payment->save();
            $result->getFinancialTransaction()->save();
            $result->getPaymentInstruction()->save();

            $this->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    public function reverseCredit($creditId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $credit = $this->getCredit($creditId);

            $result = $this->doReverseCredit($credit, $amount);

            $credit->save();
            $result->getFinancialTransaction()->save();
            $result->getPaymentInstruction()->save();

            $this->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    public function reverseDeposit($paymentId, $amount)
    {
        $this->getConnection()->beginTransaction();

        try {
            $payment = $this->getPayment($paymentId);

            $result = $this->doReverseDeposit($payment, $amount);

            $payment->save();
            $result->getFinancialTransaction()->save();
            $result->getPaymentInstruction()->save();

            $this->getConnection()->commit();

            return $result;
        } catch (\Exception $failure) {
            $this->getConnection()->rollback();

            throw $failure;
        }
    }

    protected function buildCredit(PaymentInstructionInterface $paymentInstruction, $amount)
    {
        $class =& $this->options['credit_class'];
        $credit->setPaymentInstruction($paymentInstruction);
        $credit->setTargetAmount($amount);

        return $credit;
    }

    protected function buildFinancialTransaction()
    {
        $class =& $this->options['financial_transaction_class'];

        return new $class();
    }

    protected function createFinancialTransaction(PaymentInterface $payment)
    {
        if (!$payment instanceof Payment) {
            throw new Exception('This controller only supports Doctrine2 entities as Payment objects.');
        }

        $class =& $this->options['financial_transaction_class'];
        $transaction = new $class();
        $payment->addFinancialTransaction($transaction);

        return $transaction;
    }

    protected function doCreatePayment(PaymentInstructionInterface $instruction, $amount)
    {
        if (!$instruction instanceof PaymentInstruction) {
            throw new Exception('This controller only supports Propel model object as PaymentInstruction objects.');
        }

        $class =& $this->options['payment_class'];

        $payment = new $class();
        $payment->setPaymentInstruction($instruction);
        $payment->setTargetAmount($amount);

        return $payment;
    }

    protected function doCreatePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $instruction->save();
    }

    protected function doGetPaymentInstruction($id)
    {
        $classname = $this->options['payment_instruction_class'] . 'Query';
        $paymentInstruction = $classname::create()->findPk($id);

        if (null === $paymentInstruction) {
            throw new PaymentInstructionNotFoundException(sprintf('The payment instruction with ID "%d" was not found.', $id));
        }

        return $paymentInstruction;
    }

    /**
     * @return PDO A database connection
     */
    protected function getConnection()
    {
        return \Propel::getConnection(\JMS\Payment\CoreBundle\Propel\PaymentPeer::DATABASE_NAME);
    }
}
