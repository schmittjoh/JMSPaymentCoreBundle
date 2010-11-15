<?php

namespace Bundle\PaymentBundle\PluginController;
        
use Bundle\PaymentBundle\Entity\ExtendedDataInterface;
use Bundle\PaymentBundle\Entity\CreditInterface;
use Bundle\PaymentBundle\Entity\PaymentInstructionInterface;
use Bundle\PaymentBundle\Entity\PaymentInterface;

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
     * On PluginTimeoutException (including child classes), the implementation will:
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
     * @throws Bundle\PaymentBundle\PluginController\Exception\InvalidPaymentInstructionException if the PaymentInstruction is not in the desired state
     * @param integer $paymentId
     * @param float $amount
     * @return Result
     */
    function approve($paymentId, $amount);
    function approveAndDeposit($paymentId, $amount);
    
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
     * @param PaymentInstructionInterface $paymentInstruction
     * @return Result
     */
    function checkPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    
    /**
     * This method will set the PaymentInstruction's state to CLOSED.
     * 
     * Any pending transaction will be allowed to finish; however, new transactions
     * must not be created anymore.
     * 
     * @param PaymentInstructionInterface $paymentInstruction
     * @return void
     */
    function closePaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function createDependentCredit($paymentId, $amount);
    function createIndependentCredit($paymentInstructionId, $amount);
    
    /**
     * This method will create a Payment object for the PaymentInstruction which
     * can be used to perform transactions (approve & deposit).
     * 
     * @param integer $paymentInstructionId
     * @param float $amount
     * @return PaymentInstruction
     */
    function createPayment($paymentInstructionId, $amount);
    function createPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function credit($creditId, $amount);
    
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
     * - set Payment's state to DEPOSITED if deposited amount is greater or equal to approved amount
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
     * @param integer $paymentId
     * @param float $amount
     * @return Result
     */
    function deposit($paymentId, $amount);
    function editCredit(CreditInterface $credit, $processAmount, $reasonCode, $responseCode, $referenceNumber, ExtendedDataInterface $data);
    function editPayment(PaymentInterface $payment, $processAmount, $reasonCode, $responseCode, $referenceNumber, ExtendedDataInterface $data);
    function editPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function getCredit($creditId);
    function getPayment($paymentId);
    function getPaymentInstruction($paymentInstructionId, $maskSensitiveData = true);
    
    /**
     * This method obtains the available amount in an account directly from a
     * payment back-end system.
     * 
     * This method may only be invoked if the processing plugin supports 
     * real-time queries.
     * 
     * @param PaymentInstructionInterface $paymentInstruction
     * @return float|null Returns the amount that may be consumed, or null if the amount could not be retrieved
     */
    function getRemainingValueOnPaymentInstruction(PaymentInstructionInterface $paymentInstruction);
    function reverseApproval($paymentId, $amount);
    function reverseCredit($creditId, $amount);
    function reverseDeposit($paymentId, $amount);
    
    /**
     * This method validates the correctness of any account associated with a
     * PaymentInstruction.
     * 
     * It is meant to provide a more thorough validation of the PaymentInstrunction
     * than checkPaymentInstruction() does. Therefore, it might connect to a payment
     * back-end system to actually verify the existence of accounts.
     * 
     * If this method is not implemented checkPaymentInstruction() will be used
     * instead. If this is also not implemented, the PaymentInstruction will be
     * considered valid.
     * 
     * @param PaymentInstructionInterface $paymentInstruction
     * @return Result
     */
    function validatePaymentInstruction(PaymentInstructionInterface $paymentInstruction);
}