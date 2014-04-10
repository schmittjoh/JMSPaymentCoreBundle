<?php

namespace JMS\Payment\CoreBundle\PluginController\Event;

use JMS\Payment\CoreBundle\Model\PaymentInterface;
use Symfony\Component\EventDispatcher\Event;

class PaymentStateChangeEvent extends Event
{
    private $payment;
    private $oldState;

    public function __construct(PaymentInterface $payment, $oldState)
    {
        $this->payment = $payment;
        $this->oldState = $oldState;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\PaymentInterface
     */
    public function getPayment()
    {
        return $this->payment;
    }

    /**
     * @return \JMS\Payment\CoreBundle\Model\PaymentInstructionInterface
     */
    public function getPaymentInstruction()
    {
        return $this->payment->getPaymentInstruction();
    }

    public function getOldState()
    {
        return $this->oldState;
    }

    public function getNewState()
    {
        return $this->payment->getState();
    }
}
