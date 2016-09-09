<?php

namespace JMS\Payment\CoreBundle\PluginController;

use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\FinancialTransactionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use JMS\Payment\CoreBundle\Plugin\Exception\ActionRequiredException as PluginActionRequiredException;
use JMS\Payment\CoreBundle\Plugin\Exception\BlockedException as PluginBlockedException;
use JMS\Payment\CoreBundle\Plugin\Exception\FinancialException as PluginFinancialException;
use JMS\Payment\CoreBundle\Plugin\Exception\FunctionNotSupportedException as PluginFunctionNotSupportedException;
use JMS\Payment\CoreBundle\Plugin\Exception\InvalidPaymentInstructionException as PluginInvalidPaymentInstructionException;
use JMS\Payment\CoreBundle\Plugin\Exception\TimeoutException as PluginTimeoutException;
use JMS\Payment\CoreBundle\Plugin\PluginInterface;
use JMS\Payment\CoreBundle\Plugin\QueryablePluginInterface;
use JMS\Payment\CoreBundle\PluginController\Event\Events;
use JMS\Payment\CoreBundle\PluginController\Event\PaymentInstructionStateChangeEvent;
use JMS\Payment\CoreBundle\PluginController\Event\PaymentStateChangeEvent;
use JMS\Payment\CoreBundle\PluginController\Exception\Exception;
use JMS\Payment\CoreBundle\PluginController\Exception\InvalidCreditException;
use JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentException;
use JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentInstructionException;
use JMS\Payment\CoreBundle\PluginController\Exception\PluginNotFoundException;
use JMS\Payment\CoreBundle\Util\Number;
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

abstract class PluginController implements PluginControllerInterface
{
    protected $options;

    private $plugins;
    private $dispatcher;

    public function __construct(array $options = array(), EventDispatcherInterface $dispatcher = null)
    {
        $this->options = $options;

        $this->dispatcher = $dispatcher;
        $this->plugins = array();
    }

    public function addPlugin(PluginInterface $plugin)
    {
        $this->plugins[] = $plugin;
    }

    /**
     * {@inheritdoc}
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $plugin = $this->getPlugin($instruction->getPaymentSystemName());

        try {
            $plugin->checkPaymentInstruction($instruction);

            return $this->onSuccessfulPaymentInstructionValidation($instruction);
        } catch (PluginFunctionNotSupportedException $notSupported) {
            return $this->onSuccessfulPaymentInstructionValidation($instruction);
        } catch (PluginInvalidPaymentInstructionException $invalidInstruction) {
            return $this->onUnsuccessfulPaymentInstructionValidation($instruction, $invalidInstruction);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function closePaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $oldState = $instruction->getState();

        $instruction->setState(PaymentInstructionInterface::STATE_CLOSED);

        $this->dispatchPaymentInstructionStateChange($instruction, $oldState);
    }

    /**
     * {@inheritdoc}
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

        return $this->doCreatePayment($instruction, $amount);
    }

    /**
     * {@inheritdoc}
     */
    public function createPaymentInstruction(PaymentInstructionInterface $paymentInstruction)
    {
        if (PaymentInstructionInterface::STATE_NEW === $paymentInstruction->getState()) {
            $result = $this->validatePaymentInstruction($paymentInstruction);

            if (Result::STATUS_SUCCESS !== $result->getStatus()) {
                throw new InvalidPaymentInstructionException('The PaymentInstruction could not be validated.');
            }
        } elseif (PaymentInstructionInterface::STATE_VALID !== $paymentInstruction->getState()) {
            throw new InvalidPaymentInstructionException('The PaymentInstruction\'s state must be VALID, or NEW.');
        }

        $this->doCreatePaymentInstruction($paymentInstruction);
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
     * {@inheritdoc}
     */
    public function getRemainingValueOnPaymentInstruction(PaymentInstructionInterface $instruction)
    {
        $plugin = $this->getPlugin($instruction->getPaymentSystemName());

        if (!$plugin instanceof QueryablePluginInterface) {
            return null;
        }

        return $plugin->getAvailableBalance($instruction);
    }

    /**
     * {@inheritdoc}
     */
    public function validatePaymentInstruction(PaymentInstructionInterface $paymentInstruction)
    {
        $plugin = $this->getPlugin($paymentInstruction->getPaymentSystemName());

        try {
            $plugin->validatePaymentInstruction($paymentInstruction);

            return $this->onSuccessfulPaymentInstructionValidation($paymentInstruction);
        } catch (PluginFunctionNotSupportedException $notSupported) {
            return $this->checkPaymentInstruction($paymentInstruction);
        } catch (PluginInvalidPaymentInstructionException $invalid) {
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

    abstract protected function buildCredit(PaymentInstructionInterface $paymentInstruction, $amount);

    abstract protected function buildFinancialTransaction();

    protected function doApprove(PaymentInterface $payment, $amount)
    {
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

            $transaction = $this->buildFinancialTransaction();
            $transaction->setPayment($payment);
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE);
            $transaction->setRequestedAmount($amount);
            $payment->addTransaction($transaction);

            $payment->setState(PaymentInterface::STATE_APPROVING);
            $payment->setApprovingAmount($amount);
            $instruction->setApprovingAmount($instruction->getApprovingAmount() + $amount);

            $this->dispatchPaymentStateChange($payment, PaymentInterface::STATE_NEW);
        } elseif (PaymentInterface::STATE_APPROVING === $paymentState) {
            if (Number::compare($payment->getTargetAmount(), $amount) !== 0) {
                throw new Exception('The Payment\'s target amount must equal the requested amount in a retry transaction.');
            }

            $transaction = $payment->getApproveTransaction();
            $retry = true;
        } else {
            throw new InvalidPaymentException('The Payment\'s state must be STATE_NEW, or STATE_APPROVING.');
        }

        $plugin = $this->getPlugin($instruction->getPaymentSystemName());
        $oldState = $payment->getState();

        try {
            $plugin->approve($transaction, $retry);

            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $payment->setState(PaymentInterface::STATE_APPROVED);
                $payment->setApprovingAmount(0.0);
                $payment->setApprovedAmount($transaction->getProcessedAmount());
                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                $instruction->setApprovedAmount($instruction->getApprovedAmount() + $transaction->getProcessedAmount());
                $transaction->setState(FinancialTransactionInterface::STATE_SUCCESS);

                $this->dispatchPaymentStateChange($payment, $oldState);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setApprovingAmount(0.0);
                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                $this->dispatchPaymentStateChange($payment, $oldState);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        } catch (PluginFinancialException $ex) {
            $payment->setState(PaymentInterface::STATE_FAILED);
            $payment->setApprovingAmount(0.0);
            $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

            $this->dispatchPaymentStateChange($payment, $oldState);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            $result->setPluginException($ex);

            return $result;
        } catch (PluginBlockedException $blocked) {
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

            if ($blocked instanceof PluginTimeoutException) {
                $reasonCode = PluginInterface::REASON_CODE_TIMEOUT;
            } elseif ($blocked instanceof PluginActionRequiredException) {
                $reasonCode = PluginInterface::REASON_CODE_ACTION_REQUIRED;
            } elseif (null === $reasonCode = $transaction->getReasonCode()) {
                $reasonCode = PluginInterface::REASON_CODE_BLOCKED;
            }
            $transaction->setReasonCode($reasonCode);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, $reasonCode);
            $result->setPluginException($blocked);
            $result->setRecoverable();

            return $result;
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function doApproveAndDeposit(PaymentInterface $payment, $amount)
    {
        $instruction = $payment->getPaymentInstruction();

        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('PaymentInstruction\'s state must be VALID.');
        }

        $paymentState = $payment->getState();
        if (PaymentInterface::STATE_NEW === $paymentState) {
            if ($instruction->hasPendingTransaction()) {
                throw new InvalidPaymentInstructionException('PaymentInstruction can only ever have one pending transaction.');
            }

            if (1 === Number::compare($amount, $payment->getTargetAmount())) {
                throw new \InvalidArgumentException('$amount must not be greater than Payment\'s target amount.');
            }

            $transaction = $this->buildFinancialTransaction();
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_APPROVE_AND_DEPOSIT);
            $transaction->setPayment($payment);
            $transaction->setRequestedAmount($amount);
            $payment->addTransaction($transaction);

            $payment->setApprovingAmount($amount);
            $payment->setDepositingAmount($amount);
            $payment->setState(PaymentInterface::STATE_APPROVING);

            $instruction->setApprovingAmount($instruction->getApprovingAmount() + $amount);
            $instruction->setDepositingAmount($instruction->getDepositingAmount() + $amount);

            $this->dispatchPaymentStateChange($payment, $paymentState);

            $retry = false;
        } elseif (PaymentInterface::STATE_APPROVING === $paymentState) {
            if (0 !== Number::compare($amount, $payment->getApprovingAmount())) {
                throw new \InvalidArgumentException('$amount must be equal to Payment\'s approving amount.');
            }

            if (0 !== Number::compare($amount, $payment->getDepositingAmount())) {
                throw new \InvalidArgumentException('$amount must be equal to Payment\'s depositing amount.');
            }

            $transaction = $payment->getApproveTransaction();

            $retry = true;
        } else {
            throw new InvalidPaymentException('Payment\'s state must be NEW, or APPROVING.');
        }

        $plugin = $this->getPlugin($instruction->getPaymentSystemName());
        $oldState = $payment->getState();

        try {
            $plugin->approveAndDeposit($transaction, $retry);

            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $transaction->setState(FinancialTransactionInterface::STATE_SUCCESS);
                $processedAmount = $transaction->getProcessedAmount();

                $payment->setState(PaymentInterface::STATE_DEPOSITED);
                $payment->setApprovingAmount(0.0);
                $payment->setDepositingAmount(0.0);
                $payment->setApprovedAmount($processedAmount);
                $payment->setDepositedAmount($processedAmount);

                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);
                $instruction->setApprovedAmount($instruction->getApprovedAmount() + $processedAmount);
                $instruction->setDepositedAmount($instruction->getDepositedAmount() + $processedAmount);

                $this->dispatchPaymentStateChange($payment, $oldState);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setApprovingAmount(0.0);
                $payment->setDepositingAmount(0.0);

                $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
                $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);

                $this->dispatchPaymentStateChange($payment, $oldState);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        } catch (PluginFinancialException $ex) {
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

            $payment->setState(PaymentInterface::STATE_FAILED);
            $payment->setApprovingAmount(0.0);
            $payment->setDepositingAmount(0.0);

            $instruction->setApprovingAmount($instruction->getApprovingAmount() - $amount);
            $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);

            $this->dispatchPaymentStateChange($payment, $oldState);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            $result->setPluginException($ex);

            return $result;
        } catch (PluginBlockedException $blocked) {
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

            if ($blocked instanceof PluginTimeoutException) {
                $reasonCode = PluginInterface::REASON_CODE_TIMEOUT;
            } elseif ($blocked instanceof PluginActionRequiredException) {
                $reasonCode = PluginInterface::REASON_CODE_ACTION_REQUIRED;
            } elseif (null === $reasonCode = $transaction->getReasonCode()) {
                $reasonCode = PluginInterface::REASON_CODE_BLOCKED;
            }
            $transaction->setReasonCode($reasonCode);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, $reasonCode);
            $result->setPluginException($blocked);
            $result->setRecoverable();

            return $result;
        }
    }

    protected function doCreateDependentCredit(PaymentInterface $payment, $amount)
    {
        $instruction = $payment->getPaymentInstruction();

        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('PaymentInstruction\'s state must be VALID.');
        }

        $paymentState = $payment->getState();
        if (PaymentInterface::STATE_APPROVED !== $paymentState && PaymentInterface::STATE_EXPIRED !== $paymentState) {
            throw new InvalidPaymentException('Payment\'s state must be APPROVED, or EXPIRED.');
        }

        $credit = $this->buildCredit($instruction, $amount);
        $credit->setPayment($payment);

        return $credit;
    }

    protected function doCreateIndependentCredit(PaymentInstructionInterface $instruction, $amount)
    {
        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('PaymentInstruction\'s state must be VALID.');
        }

        return $this->buildCredit($instruction, $amount);
    }

    abstract protected function doCreatePayment(PaymentInstructionInterface $instruction, $amount);

    abstract protected function doCreatePaymentInstruction(PaymentInstructionInterface $instruction);

    protected function doCredit(CreditInterface $credit, $amount)
    {
        $instruction = $credit->getPaymentInstruction();

        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('PaymentInstruction must be in STATE_VALID.');
        }

        $creditState = $credit->getState();
        if (CreditInterface::STATE_NEW === $creditState) {
            if (1 === Number::compare($amount, $max = $instruction->getDepositedAmount() - $instruction->getReversingDepositedAmount() - $instruction->getCreditingAmount() - $instruction->getCreditedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $max));
            }

            if (1 === Number::compare($amount, $credit->getTargetAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Credit restriction).', $credit->getTargetAmount()));
            }

            if (false === $credit->isIndependent()) {
                $payment = $credit->getPayment();
                $paymentState = $payment->getState();
                if (PaymentInterface::STATE_APPROVED !== $paymentState && PaymentInterface::STATE_EXPIRED !== $paymentState) {
                    throw new InvalidPaymentException('Payment\'s state must be APPROVED, or EXPIRED.');
                }

                if (1 === Number::compare($amount, $max = $payment->getDepositedAmount() - $payment->getReversingDepositedAmount() - $payment->getCreditingAmount() - $payment->getCreditedAmount())) {
                    throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Payment restriction).', $max));
                }
            }

            $transaction = $this->buildFinancialTransaction();
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_CREDIT);
            $transaction->setRequestedAmount($amount);
            $credit->addTransaction($transaction);

            $credit->setCreditingAmount($amount);
            $instruction->setCreditingAmount($instruction->getCreditingAmount() + $amount);

            if (false === $credit->isIndependent()) {
                $payment->setCreditingAmount($payment->getCreditingAmount() + $amount);
            }

            $retry = false;
        } elseif (CreditInterface::STATE_CREDITING === $creditState) {
            if (1 === Number::compare($amount, $instruction->getCreditingAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $instruction->getCreditingAmount()));
            }
            if (0 !== Number::compare($amount, $credit->getCreditingAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount must be equal to %.2f (Credit restriction).', $credit->getCreditingAmount()));
            }

            if (false === $credit->isIndependent()) {
                $payment = $credit->getPayment();
                $paymentState = $payment->getState();
                if (PaymentInterface::STATE_APPROVED !== $paymentState && PaymentInterface::STATE_EXPIRED !== $paymentState) {
                    throw new InvalidPaymentException('Payment\'s state must be APPROVED, or EXPIRED.');
                }

                if (1 === Number::compare($amount, $payment->getCreditingAmount())) {
                    throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Payment restriction).', $payment->getCreditingAmount()));
                }
            }

            $transaction = $credit->getCreditTransaction();

            $retry = true;
        } else {
            throw new InvalidCreditException('Credit\'s state must be NEW, or CREDITING.');
        }

        $plugin = $this->getPlugin($instruction->getPaymentSystemName());

        try {
            $plugin->credit($transaction, $retry);
            $processedAmount = $transaction->getProcessedAmount();

            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $transaction->setState(FinancialTransactionInterface::STATE_SUCCESS);
                $credit->setState(CreditInterface::STATE_CREDITED);

                $credit->setCreditingAmount(0.0);
                $credit->setCreditedAmount($processedAmount);
                $instruction->setCreditingAmount($instruction->getCreditingAmount() - $amount);
                $instruction->setCreditedAmount($instruction->getCreditedAmount() + $processedAmount);

                if (false === $credit->isIndependent()) {
                    $payment->setCreditingAmount($payment->getCreditingAmount() - $amount);
                    $payment->setCreditedAmount($payment->getCreditedAmount() + $processedAmount);
                }

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);
                $credit->setState(CreditInterface::STATE_FAILED);

                $credit->setCreditingAmount(0.0);
                $instruction->setCreditingAmount($instruction->getCreditingAmount() - $amount);

                if (false === $credit->isIndependent()) {
                    $payment->setCreditingAmount($payment->getCreditingAmount() - $amount);
                }

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        } catch (PluginFinancialException $ex) {
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);
            $credit->setState(CreditInterface::STATE_FAILED);

            $credit->setCreditingAmount(0.0);
            $instruction->setCreditingAmount($instruction->getCreditingAmount() - $amount);

            if (false === $credit->isIndependent()) {
                $payment->setCreditingAmount($payment->getCreditingAmount() - $amount);
            }

            return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
        } catch (PluginBlockedException $blocked) {
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

            if ($blocked instanceof PluginTimeoutException) {
                $reasonCode = PluginInterface::REASON_CODE_TIMEOUT;
            } elseif ($blocked instanceof PluginActionRequiredException) {
                $reasonCode = PluginInterface::REASON_CODE_ACTION_REQUIRED;
            } elseif (null === $reasonCode = $transaction->getReasonCode()) {
                $reasonCode = PluginInterface::REASON_CODE_BLOCKED;
            }
            $transaction->setReasonCode($reasonCode);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, $reasonCode);
            $result->setPluginException($blocked);
            $result->setRecoverable();

            return $result;
        }
    }

    protected function doDeposit(PaymentInterface $payment, $amount)
    {
        $instruction = $payment->getPaymentInstruction();

        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('The PaymentInstruction must be in STATE_VALID.');
        }

        $paymentState = $payment->getState();
        if (PaymentInterface::STATE_APPROVED === $paymentState) {
            if ($instruction->hasPendingTransaction()) {
                throw new InvalidPaymentInstructionException('The PaymentInstruction can only have one pending transaction at a time.');
            }

            if (Number::compare($amount, $payment->getApprovedAmount()) === 1) {
                throw new Exception('The amount cannot be greater than the approved amount of the Payment.');
            }

            $retry = false;

            $transaction = $this->buildFinancialTransaction();
            $transaction->setPayment($payment);
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT);
            $transaction->setRequestedAmount($amount);

            $payment->setState(PaymentInterface::STATE_DEPOSITING);
            $payment->setDepositingAmount($amount);
            $instruction->setDepositingAmount($instruction->getDepositingAmount() + $amount);

            $this->dispatchPaymentStateChange($payment, $paymentState);
        } elseif (PaymentInterface::STATE_DEPOSITING === $paymentState) {
            $transaction = $instruction->getPendingTransaction();
            if (null === $transaction) {
                if (Number::compare($amount, $payment->getApprovedAmount() - $payment->getDepositedAmount()) === 1) {
                    throw new Exception('The amount cannot be greater than the approved amount minus the already deposited amount.');
                }

                $retry = false;

                $transaction = $this->buildFinancialTransaction();
                $transaction->setPayment($payment);
                $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_DEPOSIT);
                $transaction->setRequestedAmount($amount);

                $payment->setDepositingAmount($amount);
                $instruction->setDepositingAmount($instruction->getDepositingAmount() + $amount);
            } else {
                if ($transaction->getPayment()->getId() !== $payment->getId()) {
                    throw new InvalidPaymentInstructionException('The PaymentInstruction has a pending transaction on another Payment.');
                }

                if (Number::compare($transaction->getRequestedAmount(), $amount) !== 0) {
                    throw new Exception('The requested amount must be equal to the transaction\'s amount when retrying.');
                }

                $retry = true;
            }
        } else {
            throw new InvalidPaymentException('The Payment must be in STATE_APPROVED, or STATE_DEPOSITING.');
        }

        $plugin = $this->getPlugin($instruction->getPaymentSystemName());
        $oldState = $payment->getState();

        try {
            $plugin->deposit($transaction, $retry);

            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $payment->setDepositingAmount(0.0);
                $payment->setDepositedAmount($depositedAmount = $payment->getDepositedAmount() + $transaction->getProcessedAmount());

                $changePaymentState = Number::compare($depositedAmount, $payment->getApprovedAmount()) >= 0;
                if ($changePaymentState) {
                    $payment->setState(PaymentInterface::STATE_DEPOSITED);
                }

                $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);
                $instruction->setDepositedAmount($instruction->getDepositedAmount() + $transaction->getProcessedAmount());

                if ($changePaymentState) {
                    $this->dispatchPaymentStateChange($payment, $oldState);
                }

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $payment->setState(PaymentInterface::STATE_FAILED);
                $payment->setDepositingAmount(0.0);
                $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);

                $this->dispatchPaymentStateChange($payment, $oldState);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        } catch (PluginFinancialException $ex) {
            $payment->setState(PaymentInterface::STATE_FAILED);
            $payment->setDepositingAmount(0.0);
            $instruction->setDepositingAmount($instruction->getDepositingAmount() - $amount);

            $this->dispatchPaymentStateChange($payment, $oldState);

            return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
        } catch (PluginBlockedException $blocked) {
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

            if ($blocked instanceof PluginTimeoutException) {
                $reasonCode = PluginInterface::REASON_CODE_TIMEOUT;
            } elseif ($blocked instanceof PluginActionRequiredException) {
                $reasonCode = PluginInterface::REASON_CODE_ACTION_REQUIRED;
            } elseif (null === $reasonCode = $transaction->getReasonCode()) {
                $reasonCode = PluginInterface::REASON_CODE_BLOCKED;
            }
            $transaction->setReasonCode($reasonCode);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, $reasonCode);
            $result->setPluginException($blocked);
            $result->setRecoverable();

            return $result;
        }
    }

    abstract protected function doGetPaymentInstruction($instructionId);

    protected function doReverseApproval(PaymentInterface $payment, $amount)
    {
        $instruction = $payment->getPaymentInstruction();
        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('PaymentInstruction must be in STATE_VALID.');
        }

        if (PaymentInterface::STATE_APPROVED !== $payment->getState()) {
            throw new InvalidPaymentException('Payment must be in STATE_APPROVED.');
        }

        $transaction = $instruction->getPendingTransaction();
        if (null === $transaction) {
            if (1 === Number::compare($amount, $max = $instruction->getApprovedAmount() - $instruction->getReversingApprovedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $max));
            }

            if (1 === Number::compare($amount, $payment->getApprovedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Payment restriction).', $payment->getApprovedAmount()));
            }

            $transaction = $this->buildFinancialTransaction();
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_APPROVAL);
            $transaction->setRequestedAmount($amount);
            $payment->addTransaction($transaction);

            $payment->setReversingApprovedAmount($amount);
            $instruction->setReversingApprovedAmount($instruction->getReversingApprovedAmount() + $amount);

            $retry = false;
        } else {
            if (FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_APPROVAL !== $transaction->getState()) {
                throw new InvalidPaymentInstructionException('PaymentInstruction has another pending transaction.');
            }

            if ($payment->getId() !== $transaction->getPayment()->getId()) {
                throw new \RuntimeException('Pending transaction belongs to another Payment.');
            }

            if (1 === Number::compare($amount, $instruction->getReversingApprovedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $instruction->getReversingApprovedAmount()));
            }

            if (0 !== Number::compare($amount, $payment->getReversingApprovedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount must be equal to %.2f (Payment restriction).', $payment->getReversingApprovedAmount()));
            }

            $retry = true;
        }

        $plugin = $this->getPlugin($instruction->getPaymentSystemName());

        try {
            $plugin->reverseApproval($transaction, $retry);
            $processedAmount = $transaction->getProcessedAmount();

            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $transaction->setState(FinancialTransactionInterface::STATE_SUCCESS);

                $payment->setReversingApprovedAmount(0.0);
                $instruction->setReversingApprovedAmount($instruction->getReversingApprovedAmount() - $amount);

                $payment->setApprovedAmount($payment->getApprovedAmount() - $processedAmount);
                $instruction->setApprovedAmount($instruction->getApprovedAmount() - $processedAmount);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                $payment->setReversingApprovedAmount(0.0);
                $instruction->setReversingApprovedAmount($instruction->getReversingApprovedAmount() - $amount);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        } catch (PluginFinancialException $ex) {
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

            $payment->setReversingApprovedAmount(0.0);
            $instruction->setReversingApprovedAmount($instruction->getReversingApprovedAmount() - $amount);

            return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
        } catch (PluginBlockedException $blocked) {
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

            if ($blocked instanceof PluginTimeoutException) {
                $reasonCode = PluginInterface::REASON_CODE_TIMEOUT;
            } elseif ($blocked instanceof PluginActionRequiredException) {
                $reasonCode = PluginInterface::REASON_CODE_ACTION_REQUIRED;
            } elseif (null === $reasonCode = $transaction->getReasonCode()) {
                $reasonCode = PluginInterface::REASON_CODE_BLOCKED;
            }
            $transaction->setReasonCode($reasonCode);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, $reasonCode);
            $result->setPluginException($blocked);
            $result->setRecoverable();

            return $result;
        }
    }

    protected function doReverseCredit(CreditInterface $credit, $amount)
    {
        $instruction = $credit->getPaymentInstruction();

        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('PaymentInstruction must be in STATE_VALID.');
        }

        if (CreditInterface::STATE_CREDITED !== $credit->getState()) {
            throw new InvalidCreditException('Credit must be in STATE_CREDITED.');
        }

        if (false === $credit->isIndependent()) {
            $payment = $credit->getPayment();
            if (PaymentInterface::STATE_APPROVED !== $payment->getState() && PaymentInterface::STATE_EXPIRED !== $payment->getState()) {
                throw new InvalidPaymentException('Payment must be in STATE_APPROVED, or STATE_EXPIRED.');
            }
        }

        $transaction = $instruction->getPendingTransaction();
        if (null === $transaction) {
            if (1 === Number::compare($amount, $max = $instruction->getCreditedAmount() - $instruction->getReversingCreditedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $max));
            }

            if (1 === Number::compare($amount, $credit->getCreditedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Credit restriction).', $credit->getCreditedAmount()));
            }

            if (false === $credit->isIndependent() && 1 === Number::compare($amount, $max = $payment->getCreditedAmount() - $payment->getReversingCreditedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Payment restriction).', $max));
            }

            $transaction = $this->buildFinancialTransaction();
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_CREDIT);
            $transaction->setRequestedAmount($amount);
            $credit->addTransaction($transaction);

            $credit->setReversingCreditedAmount($amount);
            $instruction->setReversingCreditedAmount($instruction->getReversingCreditedAmount() + $amount);

            if (false === $credit->isIndependent()) {
                $payment->setReversingCreditedAmount($payment->getReversingCreditedAmount() + $amount);
            }

            $retry = false;
        } else {
            if (FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_CREDIT !== $transaction->getTransactionType()) {
                throw new InvalidPaymentInstructionException('Pending transaction is not of TYPE_REVERSE_CREDIT.');
            }

            if ($credit->getId() !== $transaction->getCredit()->getId()) {
                throw new InvalidCreditException('Pending transaction belongs to another Credit.');
            }

            if (1 === Number::compare($amount, $instruction->getReversingCreditedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $instruction->getReversingCreditedAmount()));
            }

            if (0 !== Number::compare($amount, $credit->getReversingCreditedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount must be equal to %.2f (Credit restriction).', $credit->getReversingCreditedAmount()));
            }

            if (false === $credit->isIndependent() && 1 === Number::compare($amount, $payment->getReversingCreditedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Payment restriction).', $payment->getReversingCreditedAmount()));
            }

            $retry = true;
        }

        $plugin = $this->getPlugin($instruction->getPaymentSystemName());

        try {
            $plugin->reverseCredit($transaction, $amount);
            $processedAmount = $transaction->getProcessedAmount();

            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $transaction->setState(FinancialTransactionInterface::STATE_SUCCESS);

                $credit->setReversingCreditedAmount(0.0);
                $instruction->setReversingCreditedAmount($instruction->getReversingCreditedAmount() - $amount);
                $credit->setCreditedAmount($credit->getCreditedAmount() - $processedAmount);
                $instruction->setCreditedAmount($instruction->getCreditedAmount() - $processedAmount);

                if (false === $credit->isIndependent()) {
                    $payment->setReversingCreditedAmount($payment->getReversingCreditedAmount() - $amount);
                    $payment->setCreditedAmount($payment->getCreditedAmount() - $processedAmount);
                }

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                $credit->setReversingCreditedAmount(0.0);
                $instruction->setReversingCreditedAmount($instruction->getReversingCreditedAmount() - $amount);

                if (false === $credit->isIndependent()) {
                    $payment->setReversingCreditedAmount($payment->getReversingCreditedAmount() - $amount);
                }

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        } catch (PluginFinancialException $ex) {
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

            $credit->setReversingCreditedAmount(0.0);
            $instruction->setReversingCreditedAmount($instruction->getReversingCreditedAmount() - $amount);

            if (false === $credit->isIndependent()) {
                $payment->setReversingCreditedAmount($payment->getReversingCreditedAmount() - $amount);
            }

            return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
        } catch (PluginBlockedException $blocked) {
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

            if ($blocked instanceof PluginTimeoutException) {
                $reasonCode = PluginInterface::REASON_CODE_TIMEOUT;
            } elseif ($blocked instanceof PluginActionRequiredException) {
                $reasonCode = PluginInterface::REASON_CODE_ACTION_REQUIRED;
            } elseif (null === $reasonCode = $transaction->getReasonCode()) {
                $reasonCode = PluginInterface::REASON_CODE_BLOCKED;
            }
            $transaction->setReasonCode($reasonCode);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, $reasonCode);
            $result->setPluginException($blocked);
            $result->setRecoverable();

            return $result;
        }
    }

    protected function doReverseDeposit(PaymentInterface $payment, $amount)
    {
        $instruction = $payment->getPaymentInstruction();
        if (PaymentInstructionInterface::STATE_VALID !== $instruction->getState()) {
            throw new InvalidPaymentInstructionException('PaymentInstruction must be in STATE_VALID.');
        }

        if (PaymentInterface::STATE_APPROVED !== $payment->getState()) {
            throw new InvalidPaymentException('Payment must be in STATE_APPROVED.');
        }

        $transaction = $instruction->getPendingTransaction();
        if ($transaction === null) {
            if (1 === Number::compare($amount, $max = $instruction->getDepositedAmount() - $instruction->getReversingDepositedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $max));
            }

            if (1 === Number::compare($amount, $payment->getDepositedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (Payment restriction).', $payment->getDepositedAmount()));
            }

            $transaction = $this->buildFinancialTransaction();
            $transaction->setTransactionType(FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_DEPOSIT);
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);
            $transaction->setRequestedAmount($amount);

            $payment->setReversingDepositedAmount($amount);
            $instruction->setReversingDepositedAmount($instruction->getReversingDepositedAmount() + $amount);

            $retry = false;
        } else {
            if (FinancialTransactionInterface::TRANSACTION_TYPE_REVERSE_DEPOSIT !== $transaction->getTransactionType()) {
                throw new InvalidPaymentInstructionException('There is another pending transaction on this payment.');
            }

            if ($payment->getId() !== $transaction->getPayment()->getId()) {
                throw new InvalidPaymentException('The pending transaction belongs to another Payment.');
            }

            if (1 === Number::compare($amount, $instruction->getReversingDepositedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount cannot be greater than %.2f (PaymentInstruction restriction).', $instruction->getReversingDepositedAmount()));
            }

            if (0 !== Number::compare($amount, $payment->getReversingDepositedAmount())) {
                throw new \InvalidArgumentException(sprintf('$amount must be equal to %.2f (Payment restriction).', $payment->getReversingDepositedAmount()));
            }

            $retry = true;
        }

        $plugin = $this->getPlugin($instruction->getPaymentSystemName());

        try {
            $plugin->reverseDeposit($transaction, $retry);
            $processedAmount = $transaction->getProcessedAmount();

            $payment->setReversingDepositedAmount(0.0);
            $instruction->setReversingDepositedAmount($instruction->getReversingDepositedAmount() - $amount);

            if (PluginInterface::RESPONSE_CODE_SUCCESS === $transaction->getResponseCode()) {
                $transaction->setState(FinancialTransactionInterface::STATE_SUCCESS);

                $payment->setDepositedAmount($payment->getDepositedAmount() - $processedAmount);
                $instruction->setDepositedAmount($instruction->getDepositedAmount() - $processedAmount);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
            } else {
                $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

                return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
            }
        } catch (PluginFinancialException $ex) {
            $transaction->setState(FinancialTransactionInterface::STATE_FAILED);

            return $this->buildFinancialTransactionResult($transaction, Result::STATUS_FAILED, $transaction->getReasonCode());
        } catch (PluginBlockedException $blocked) {
            $transaction->setState(FinancialTransactionInterface::STATE_PENDING);

            if ($blocked instanceof PluginTimeoutException) {
                $reasonCode = PluginInterface::REASON_CODE_TIMEOUT;
            } elseif ($blocked instanceof PluginActionRequiredException) {
                $reasonCode = PluginInterface::REASON_CODE_ACTION_REQUIRED;
            } elseif (null === $reasonCode = $transaction->getReasonCode()) {
                $reasonCode = PluginInterface::REASON_CODE_BLOCKED;
            }
            $transaction->setReasonCode($reasonCode);
            $transaction->setResponseCode(PluginInterface::RESPONSE_CODE_PENDING);

            $result = $this->buildFinancialTransactionResult($transaction, Result::STATUS_PENDING, $reasonCode);
            $result->setPluginException($blocked);
            $result->setRecoverable();

            return $result;
        }
    }

    protected function getPlugin($paymentSystemName)
    {
        foreach ($this->plugins as $plugin) {
            if ($plugin->processes($paymentSystemName)) {
                return $plugin;
            }
        }

        throw new PluginNotFoundException(sprintf('There is no plugin that processes payments for "%s".', $paymentSystemName));
    }

    protected function onSuccessfulPaymentInstructionValidation(PaymentInstructionInterface $instruction)
    {
        $oldState = $instruction->getState();

        $instruction->setState(PaymentInstructionInterface::STATE_VALID);

        $this->dispatchPaymentInstructionStateChange($instruction, $oldState);

        return $this->buildPaymentInstructionResult($instruction, Result::STATUS_SUCCESS, PluginInterface::REASON_CODE_SUCCESS);
    }

    protected function onUnsuccessfulPaymentInstructionValidation(PaymentInstructionInterface $instruction, PluginInvalidPaymentInstructionException $invalid)
    {
        $oldState = $instruction->getState();

        $instruction->setState(PaymentInstructionInterface::STATE_INVALID);

        $this->dispatchPaymentInstructionStateChange($instruction, $oldState);

        $result = $this->buildPaymentInstructionResult($instruction, Result::STATUS_FAILED, PluginInterface::REASON_CODE_INVALID);
        $result->setPluginException($invalid);

        return $result;
    }

    private function dispatchPaymentInstructionStateChange(PaymentInstructionInterface $instruction, $oldState)
    {
        if (null === $this->dispatcher) {
            return;
        }

        $event = new PaymentInstructionStateChangeEvent($instruction, $oldState);
        $this->dispatcher->dispatch(Events::PAYMENT_INSTRUCTION_STATE_CHANGE, $event);
    }

    private function dispatchPaymentStateChange(PaymentInterface $payment, $oldState)
    {
        if (null === $this->dispatcher) {
            return;
        }

        $event = new PaymentStateChangeEvent($payment, $oldState);
        $this->dispatcher->dispatch(Events::PAYMENT_STATE_CHANGE, $event);
    }
}
