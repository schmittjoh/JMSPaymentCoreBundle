<?php

namespace JMS\Payment\CoreBundle\Plugin\Exception;

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
 * This exception is thrown whenever a financial transaction cannot be processed.
 *
 * This exception must only be thrown when the situation is temporary, and there
 * is a chance that the same transaction can be performed successfully against
 * the PaymentInstruction at a later time.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class BlockedException extends Exception
{
}
