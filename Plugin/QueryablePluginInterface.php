<?php

namespace JMS\Payment\CoreBundle\Plugin;

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
 * This interface can be implemented in addition to PluginInterface
 * if the plugin supports real-time queries.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
interface QueryablePluginInterface extends PluginInterface
{
    /**
     * This method gets the available balance for the specified
     * PaymentInstruction account.
     *
     * @param PaymentInstructionInterface $paymentInstruction
     *
     * @return float|null Returns the amount that may be consumed by the payment, or null of it cannot be determined
     */
    public function getAvailableBalance(PaymentInstructionInterface $paymentInstruction);

    /**
     * This method updates the given Credit object with data from the
     * payment backend system.
     *
     * @param CreditInterface $credit
     */
    public function updateCredit(CreditInterface $credit);

    /**
     * This method updates the given Payment object with data from the
     * payment backend system.
     *
     * @param PaymentInterface $payment
     */
    public function updatePayment(PaymentInterface $payment);
}
