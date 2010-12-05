<?php

namespace Bundle\PaymentBundle\Plugin\Exception;

/**
 * This exception will be thrown when the plugin determines the 
 * PaymentInstruction to be invalid. 
 * 
 * The payment plugin controller will consequentially set the transaction's
 * state to FAILED, and the instruction's state to INVALID.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class InvalidPaymentInstructionException extends FinancialException
{
}