<?php

declare(strict_types=1);

namespace JMS\Payment\CoreBundle\PluginController\Event;

use JMS\Payment\CoreBundle\Model\PaymentInstructionInterface;
use JMS\Payment\CoreBundle\Model\PaymentInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PaymentStateChangeEvent extends Event
{
    private PaymentInterface $payment;

    private int $oldState;

    public function __construct(PaymentInterface $payment, int $oldState)
    {
        $this->payment = $payment;
        $this->oldState = $oldState;
    }

    public function getPayment(): PaymentInterface
    {
        return $this->payment;
    }

    public function getPaymentInstruction(): PaymentInstructionInterface
    {
        return $this->payment->getPaymentInstruction();
    }

    public function getOldState(): int
    {
        return $this->oldState;
    }

    public function getNewState()
    {
        return $this->payment->getState();
    }
}
