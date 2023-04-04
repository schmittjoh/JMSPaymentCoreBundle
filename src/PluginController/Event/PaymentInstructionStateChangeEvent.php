<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\PluginController\Event;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentInstructionStateChangeEvent extends Event
{
    private PaymentInstructionInterface $paymentInstruction;

    private int $oldState;

    public function __construct(PaymentInstructionInterface $paymentInstruction, int $oldState)
    {
        $this->paymentInstruction = $paymentInstruction;
        $this->oldState = $oldState;
    }

    public function getPaymentInstruction(): PaymentInstructionInterface
    {
        return $this->paymentInstruction;
    }

    public function getOldState(): int
    {
        return $this->oldState;
    }

    public function getNewState()
    {
        return $this->paymentInstruction->getState();
    }
}
