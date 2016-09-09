<?php

namespace JMS\Payment\CoreBundle\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

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
 * Validates a PAN using the LUHN Algorithm.
 *
 * An example card number that passes this check is 4242 4242 4242 4242.
 *
 * @see http://en.wikipedia.org/wiki/Luhn_algorithm
 *
 * @author Tim Nagel <t.nagel@infinite.net.au>
 * @author Greg Knapp http://gregk.me/2011/php-implementation-of-bank-card-luhn-algorithm/
 */
class LuhnValidator extends ConstraintValidator
{
    /**
     * Validates a creditcard number with the Luhn algorithm.
     *
     * @param mixed      $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_numeric($value)) {
            $this->context->addViolation($constraint->message);
        }

        $length = strlen($value);
        $oddLength = $length % 2;
        for ($sum = 0, $i = $length - 1; $i >= 0; --$i) {
            $digit = (int) $value[$i];
            $sum += (($i % 2) === $oddLength) ? array_sum(str_split($digit * 2)) : $digit;
        }

        if (($sum % 10) !== 0) {
            $this->context->addViolation($constraint->message);
        }
    }
}
