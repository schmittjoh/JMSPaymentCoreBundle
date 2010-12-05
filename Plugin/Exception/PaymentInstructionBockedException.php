<?php

namespace Bundle\PaymentBundle\Plugin\Exception;

/**
 * This exception is thrown when a financial transaction cannot be processed
 * against a PaymentInstruction.
 * 
 * This is a temporary situation. The financial transaction can be retried 
 * against the same PaymentInstruction later on. For example, the credit card 
 * associated with a payment instruction might have been put in hold.
 * 
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class PaymentInstructionBlockedException extends FinancialException
{
}