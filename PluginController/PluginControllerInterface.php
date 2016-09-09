<?php

namespace JMS\Payment\CoreBundle\PluginController;

use JMS\Payment\CoreBundle\Model\CreditInterface;
use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;

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
 * This interface is implement by all payment plugin controllers.
 *
 * When you have a need to implement your own plugin controller, it is typically
 * better to extend the PluginController base class.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface PluginControllerInterface
{
    /**
     * This method executes an approve transaction against a Payment.
     *
     * The implementation ensures that:
     * - PaymentInstruction's state is VALID
     * - Payment's state is either NEW, or APPROVING
     * - Payment's target amount is sufficient
     * - PaymentInstruction has no pending transaction if Payment is NEW
     *
     * If Payment's state is NEW, the implementation will:
     * - change Payment's state to APPROVING
     * - set approving amount in Payment
     * - increase approving amount in PaymentInstruction
     * - delegate the transaction to an appropriate plugin implementation
     *
     * If Payment's state is APPROVING, the implementation will:
     * - delegate the existing approve transaction to an appropriate plugin implementation
     *
     * On successful response, the implementation will:
     * - reset the approving amount in Payment to zero
     * - decrease the approving amount in PaymentInstruction by the requested amount
     * - increase the approved amount in PaymentInstruction by the processed amount
     * - change Payment's state to APPROVED
     *
     * On unsuccessful response, the implementation will:
     * - reset the approving amount in Payment to zero
     * - change Payment's state to FAILED
     * - decrease the approving amount in PaymentInstruction by the requested amount
     *
     * On TimeoutException (including child classes), the implementation will:
     * - keep approving amounts in Payment, and PaymentInstruction unchanged
     * - keep Payment's state unchanged
     * - set reasonCode to PluginInterface::REASON_CODE_TIMEOUT
     *
     * // FIXME: How do we process cases where a user interaction is required, e.g. PayPal?
     *           This probably requires an additional exception which is processed similar to a PluginTimeoutException
     *
     * On any exception not mentioned above, the implementation will:
     * - rollback the transaction
     * - not persist any changes in the database
     *
     * @throws JMS\Payment\CoreBundle\PluginController\Exception\InvalidPaymentInstructionException if the PaymentInstruction is not in the desired state
     *
     * @param int   $paymentId
     * @param float $amount
     *
     * @return Result
     */
    public function approve($paymentId, $amount);

    /**
     * This method executes an approveAndDeposit transaction against a payment
     * (aka "sale" transaction or "authorization with capture").
     *
     * The implementation will ensure that:
     * - PaymentInstruction's state is VALID
     * - Payment's state is NEW, or APPROVING
     * - PaymentInstruction only ever has one pending transaction
     *
     * In addition, if the Payment is NEW, the implementation will ensure:
     * - Payment's target amount is greater or equal to the requested amount
     *
     * In addition, if the Payment is APPROVING, the implementation will ensure:
     * - Payment's approving amount is equal to the requested amount
     * - Payment's depositing amount is equal to the requested amount
     *
     * For NEW payments, the implementation will:
     * - set Payment's state to APPROVING
     * - set Payment's approving amount to requested amount
     * - set Payment's depositing amount to requested amount
     * - increase PaymentInstruction's approving amount by requested amount
     * - increase PaymentInstruction's depositing amount by requested amount
     * - delegate the new transaction to an appropriate plugin implementation
     *
     * For APPROVING payments, the implementation will:
     * - delegate the pending transaction to an appropriate plugin implementation
     *
     * On successful response, the implementation will:
     * - set Payment's state to APPROVED
     * - set Payment's approving amount to zero
     * - set Payment's depositing amount to zero
     * - decrease PaymentInstruction's approving amount by requested amount
     * - decrease PaymentInstruction's depositing amount by requested amount
     * - set Payment's approved amount to processed amount
     * - set Payment's deposited amount to processed amount
     * - increase PaymentInstruction's approved amount by processed amount
     * - increase PaymentInstruction's deposited amount by processed amount
     * - set reason code to PluginInterface::REASON_CODE_SUCCESS
     *
     * On unsuccessful response, the implementation will:
     * - set Payment's state to FAILED
     * - set Payment's approving amount to zero
     * - set Payment's depositing amount to zero
     * - decrease PaymentInstruction's approving amount by requested amount
     * - decrease PaymentInstruction's depositing amount by requested amount
     *
     * On TimeoutException (including child classes), the implementation will:
     * - keep Payment's state unchanged
     * - keep Payment's approving/depositing amounts unchanged
     * - keep PaymentInstruction's approving/depositing amounts unchanged
     * - set reason code to PluginInterface::REASON_CODE_TIMEOUT
     *
     * @param int   $paymentId
     * @param float $amount
     *
     * @return Result
     */
    public function approveAndDeposit($paymentId, $amount);

    /**
     * This method calls checkPaymentInstruction on the plugin processing the
     * requested payment system. It allows to verify the PaymentInstruction
     * prior to creating it.
     *
     * The PaymentInstruction will be marked VALID, or INVALID depending on the
     * outcome.
     *
     * Any exception except InvalidPaymentInstructionException will cause the
     * plugin controller to rollback the transaction.
     *
     * @see validatePaymentInstruction()
     *
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @return Result
     */
    public function checkPaymentInstruction(PaymentInstructionInterface $paymentInstruction);

    /**
     * This method will set the PaymentInstruction's state to CLOSED.
     *
     * Any pending transaction will be allowed to finish; however, new transactions
     * must not be created anymore.
     *
     * The implementation will also remove the complete set of name-value pairs from
     * the ExtendedData container associated with the given PaymentInstruction.
     *
     * @param PaymentInstructionInterface $paymentInstruction
     */
    public function closePaymentInstruction(PaymentInstructionInterface $paymentInstruction);

    /**
     * This method will create a dependent credit object linked to the PaymentInstruction
     * associated with the given payment.
     *
     * The credit object can be used to execute credit transactions against an
     * appropriate payment plugin.
     *
     * The implementation will ensure that:
     * - PaymentInstruction's state is VALID
     * - Payment's state is APPROVED, or EXPIRED
     *
     * @param int   $paymentId
     * @param float $amount
     *
     * @return CreditInterface
     */
    public function createDependentCredit($paymentId, $amount);

    /**
     * This method will create an independent credit object.
     *
     * The implementation will ensure that the PaymentInstruction's state
     * is VALID.
     *
     * @param int   $paymentInstructionId
     * @param float $amount
     *
     * @return CreditInterface
     */
    public function createIndependentCredit($paymentInstructionId, $amount);

    /**
     * This method will create a Payment object for the PaymentInstruction which
     * can be used to perform transactions (approve & deposit).
     *
     * @param int   $paymentInstructionId
     * @param float $amount
     *
     * @return PaymentInterface
     */
    public function createPayment($paymentInstructionId, $amount);

    /**
     * This method creates a PaymentInstruction.
     *
     * If the instruction is not yet VALID, the implementation will call
     * validatePaymentInstruction first.
     *
     * @param PaymentInstructionInterface $paymentInstruction
     */
    public function createPaymentInstruction(PaymentInstructionInterface $paymentInstruction);

    /**
     * This method executes a credit transaction against a Credit.
     *
     * The implementation will ensure that:
     * - PaymentInstruction's state is VALID
     * - Credit's state is NEW (retry: false), or CREDITING (retry: true)
     * - Assuming retry = false: requested amount <= PaymentInstruction.depositedAmount
     *                                             - PaymentInstruction.reversingDepositedAmount
     *                                             - PaymentInstruction.creditingAmount
     *                                             - PaymentInstruction.creditedAmount
     * - Assuming retry = true: requested amount <= PaymentInstruction.creditingAmount
     * - Assuming retry = false: requested amount <= Credit.targetAmount
     * - Assuming retry = true: requested amount == Credit.creditingAmount
     *
     * For a dependent credit, the implementation will further ensure that:
     * - Payment's state is either APPROVED, or EXPIRED
     * - Assuming retry = false: requested amount <= Payment.depositedAmount - Payment.reversingDepositedAmount - Payment.creditingAmount - Payment.creditedAmount
     * - Assuming retry = true: requested amount <= Payment.creditingAmount
     *
     * The implementation will:
     * - increase PaymentInstruction's crediting amount by requested amount
     * - increase Payment's crediting amount by requested amount
     * - set Credit's crediting amount to requested amount
     * - set Credit's state to CREDITING
     *
     * On successful response, the implementation will:
     * - set transaction's state to SUCCESS
     * - set Credit's state to CREDITED
     * - increase PaymentInstruction's credited amount by processed amount
     * - increase Payment's credited amount by processed amount
     * - set Credit's credited amount to processed amount
     * - decrease PaymentInstruction's crediting amount by requested amount
     * - decrease Payment's crediting amount by requested amount
     * - set Credit's crediting amount to zero
     *
     * On unsuccessful response, the implementation will:
     * - set transaction's state to FAILED
     * - set Credit's state to FAILED
     * - set Credit's crediting amount to zero
     * - decrease Payment's crediting amount by requested amount
     * - decrease PaymentInstruction's crediting amount by requested amount
     *
     * On PluginTimeoutException, the implementation will:
     * - set transaction's state to PENDING
     * - keep amounts in Payment, Credit, and PaymentInstruction unchanged
     *
     * All changes to Payment objects are only applicable to dependent Credit objects.
     *
     * @param int   $creditId
     * @param float $amount
     *
     * @return Result
     */
    public function credit($creditId, $amount);

    // FIXME: I guess it's not strictly necessary to provide a common interface for deletion, postponing this
//    function deletePaymentInstruction($paymentInstructionId);

    /**
     * This method executes a deposit transaction against a Payment.
     *
     * The implementation will ensure that:
     * - PaymentInstruction's state is VALID
     * - Payment's state is APPROVED, or DEPOSITING
     *
     * If Payment's state is APPROVED, the implementation will:
     * - ensure that PaymentInstruction has no pending transaction, or throw InvalidPaymentInstructionException
     * - ensure that the Payment's amount is sufficient: requested amount <= approved amount
     * - change Payment's state to DEPOSITING
     * - set depositing amount in Payment to requested amount
     * - increase depositing amount in PaymentInstruction by requested amount
     * - delegate the transaction to an appropriate plugin implementation
     *
     * If Payment's state is DEPOSITING and there is a pending transaction, the implementation will:
     * - ensure that the pending transaction does not belong to another Payment, or throw InvalidPaymentInstructionException
     * - ensure that the Transaction's amount equals the requested amount
     * - delete the pending transaction to an appropriate plugin implementation
     *
     * If Payment's state is DEPOSITING and there is NO pending transaction, the implementation will:
     * - verify that the Payment's approved amount is sufficient: requested amount <= approved amount - deposited amount
     * - create a new depositing transaction
     * - set depositing amount in Payment to requested amount
     * - increase depositing amount in PaymentInstruction by requested amount
     * - delegate the transaction to an appropriate plugin implementation
     *
     * On successful transaction, the implementation will:
     * - reset depositing amount in Payment to zero
     * - decrease depositing amount in PaymentInstruction by requested amount
     * - increase deposited amount in Payment by processed amount
     * - increase deposited amount in PaymentInstruction by processed amount
     *
     * On unsuccessful transaction, the implementation will:
     * - reset depositing amount in Payment to zero
     * - decrease depositing amount in PaymentInstruction by requested amount
     * - change Payment's state to FAILED
     *
     * On PluginTimeoutException (including child classes), the implementation will:
     * - keep depositing amounts in Payment and PaymentInstruction unchanged
     * - set reasonCode to PluginInterface::REASON_CODE_TIMEOUT
     *
     * // FIXME: Add additional exception when user interaction is required (see approve())
     *
     * On any exception not mentioned above, the implementation will:
     * - rollback the transaction
     * - not persist any changes
     *
     * @param int   $paymentId
     * @param float $amount
     *
     * @return Result
     */
    public function deposit($paymentId, $amount);

    /**
     * This method retrieves a Credit object.
     *
     * This method should also retrieve the associated PaymentInstruction;
     * other associated objects should not be retrieved.
     *
     * If the responsible plugin implements the QueryablePluginInterface,
     * the credit will be synchronized with the external data, and the updated
     * Credit will be returned.
     *
     * @param int $creditId
     *
     * @return CreditInterface
     */
    public function getCredit($creditId);

    /**
     * This method retrieves a Payment object.
     *
     * This method should also retrieve the associated PaymentInstruction;
     * other associated objects should not be retrieved.
     *
     * If the responsible plugin implements the QueryablePluginInterface, the
     * payment will be synchronized with the external data, and the updated
     * Payment will be returned.
     *
     * @param int $paymentId
     *
     * @return PaymentInterface
     */
    public function getPayment($paymentId);

    /**
     * This method retrieves the PaymentInstruction with given identifier.
     *
     * Associated Payment, Credit, and ExtendedData should also be retrieved.
     *
     * @param int  $paymentInstructionId
     * @param bool $maskSensitiveData
     *
     * @return PaymentInstructionInterface
     */
    public function getPaymentInstruction($paymentInstructionId, $maskSensitiveData = true);

    /**
     * This method obtains the available amount in an account directly from a
     * payment back-end system.
     *
     * This method may only be invoked if the processing plugin supports
     * real-time queries.
     *
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @return float|null Returns the amount that may be consumed, or null if the amount could not be retrieved
     */
    public function getRemainingValueOnPaymentInstruction(PaymentInstructionInterface $paymentInstruction);

    /**
     * This method will execute a reverseApproval transaction against Payment.
     *
     * The implementation will ensure that:
     * - PaymentInstruction's state is VALID
     * - Payment's state is APPROVED
     *
     * For non-retry transactions, the implementation will ensure that:
     * - requested amount <= PaymentInstruction.approvedAmount - PaymentInstruction.reversingApprovedAmount
     * - requested amount <= Payment.approvedAmount
     * - Payment.depositedAmount == 0
     * - Payment.depositingAmount == 0
     *
     * For retry transactions, the implementation will ensure that:
     * - requested amount <= PaymentInstruction.reversingApprovedAmount
     * - requested amount == Payment.reversingApprovedAmount
     *
     * The implementation will:
     * - set Payment's reversing approved amount to requested amount
     * - increase PaymentInstruction's reversing approved amount by requested amount
     *
     * On successful response, the implementation will:
     * - set Transaction's state to SUCCESS
     * - set reason code to PluginInterface::REASON_CODE_SUCCESS
     * - set Payment's reversing approved amount to zero
     * - decrease PaymentInstruction's reversing approved amount by requested amount
     * - decrease Payment's approved amount by processed amount
     * - decrease PaymentInstruction's approved amount by processed amount
     *
     * On unsuccessful response, the implementation will:
     * - set Transaction's state to FAILED
     * - set Payment's reversing approved amount to zero
     * - decrease PaymentInstruction's reversing approved amount by requested amount
     *
     * On PluginTimeoutException, the implementation will:
     * - set Transaction's state to PENDING
     * - set reason code to PluginInterface::REASON_CODE_TIMEOUT
     * - keep all amounts in Payment, and PaymentInstruction unchanged
     *
     * @param int   $paymentId
     * @param float $amount
     *
     * @return Result
     */
    public function reverseApproval($paymentId, $amount);

    /**
     * This method executes a reverseCredit transaction against a Credit.
     *
     * The implementation will ensure that:
     * - PaymentInstruction is in STATE_VALID
     * - Credit is in STATE_CREDITED
     * - Ensure that if there is an open transaction, it is a reverseCredit transaction
     *   belonging to the same Credit container
     * - In non-retry transactions: requested amount <= PaymentInstruction.creditedAmount - PaymentInstruction.reversingCreditedAmount
     * - In non-retry transactions: requested amount <= Credit.creditedAmount
     * - In retry transactions: requested amount <= PaymentInstruction.reversingCreditedAmount
     * - In retry transactions: requested amount == Credit.reversingCreditedAmount
     *
     * For dependent credits, the implementation will further ensure that:
     * - Payment is in STATE_APPROVED, or STATE_EXPIRED
     * - In non-retry transactions: requested amount <= Payment.creditedAmount - Payment.reversingCreditedAmount
     * - In retry transactions: requested amount <= Payment.reversingCreditedAmount
     *
     * The implementation will:
     * - set Credit.reversingCreditedAmount to requested amount
     * - increase Payment.reversingCreditedAmount by requested amount
     * - increase PaymentInstruction.reversingCreditedAmount by requested amount
     *
     * On a successful response, the implementation will:
     * - set transaction's state to SUCCESS
     * - set reason code to PluginInterface::REASON_CODE_SUCCESS
     * - set Credit.reversingCreditedAmount to zero
     * - decrease Payment.reversingCreditedAmount by requested amount
     * - decrease PaymentInstruction.reversingCreditedAmount by requested amount
     * - decrease Payment.creditedAmount by processed amount
     * - decrease PaymentInstruction.creditedAmount by processed amount
     *
     * On a unsuccessful response, the implementation will:
     * - set transaction's state to FAILED
     * - set Credit.reversingCreditedAmount to zero
     * - decrease Payment.reversingCreditedAmount by requested amount
     * - decrease PaymentInstruction.reversingCreditedAmount by requested amount
     *
     * On a PluginTimeoutException, the implementation will:
     * - set transaction's state to PENDING
     * - keep all amounts in Payment, Credit, and PaymentInstruction unchanged
     * - set reason code to PluginInterface::REASON_CODE_TIMEOUT
     *
     * @param int   $creditId
     * @param float $amount
     *
     * @return Result
     */
    public function reverseCredit($creditId, $amount);

    /**
     * This method executes a reverseDeposit transaction against a Payment.
     *
     * The implementation will ensure that:
     * - PaymentInstruction is in STATE_VALID
     * - Payment is in STATE_APPROVED
     * - any pending transaction is belonging to this payment, and is a reverseDeposit transaction
     * - for non-retry transactions: requested amount <= PaymentInstruction.depositedAmount - PaymentInstruction.reversingDepositedAmount
     * - for non-retry transactions: requested amount <= Payment.depositedAmount
     * - for retry transactions: requested amount <= PaymentInstruction.reversingDepositedAmount
     * - for retry transactions: requested amount == Payment.reversingDepositedAmount
     *
     * The implementation will:
     * - set Payment.reversingDepositedAmount to requested amount
     * - increase PaymentInstruction.reversingDepositedAmount by requested amount
     * - delegate the transaction to an appropriate plugin
     *
     * On a successful response, the implementation will:
     * - set Payment.reversingDepositedAmount to zero
     * - decrease PaymentInstruction.reversingDepositedAmount by requested amount
     * - decrease Payment.depositedAmount by processed amount
     * - decrease PaymentInstruction.depositedAmount by processed amount
     * - set transaction's state to SUCCESS
     * - set reason code to PluginInterface::REASON_CODE_SUCCESS
     *
     * On an unsuccessful response, the implementation will:
     * - set Payment.reversingDepositedAmount to zero
     * - decrease PaymentInstruction.reversingDepositedAmount by requested amount
     * - set transaction's state to FAILED
     *
     * On PluginTimeoutException, the implementation will:
     * - set transaction's state to PENDING
     * - set reason code to PluginInterface::REASON_CODE_TIMEOUT
     * - keep all amounts in Payment, and PaymentInstruction unchanged
     *
     * @param int   $paymentId
     * @param float $amount
     *
     * @return Result
     */
    public function reverseDeposit($paymentId, $amount);

    /**
     * This method validates the correctness of any account associated with a
     * PaymentInstruction.
     *
     * It is meant to provide a more thorough validation of the PaymentInstruction
     * than checkPaymentInstruction() does. Therefore, it might connect to a payment
     * back-end system to actually verify the existence of accounts.
     *
     * If this method is not implemented checkPaymentInstruction() will be used
     * instead. If this is also not implemented, the PaymentInstruction will be
     * considered valid.
     *
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @return Result
     */
    public function validatePaymentInstruction(PaymentInstructionInterface $paymentInstruction);
}
