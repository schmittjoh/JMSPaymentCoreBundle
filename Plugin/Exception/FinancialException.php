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
 * This exception is thrown whenever a transaction against the backend system
 * fails due to a financial error.
 *
 * Example: Invalid credit card information is found while performing an approve
 *             transaction.
 *
 * The plugin should set all error codes to ease debugging.
 * For further information, please see PluginException.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class FinancialException extends Exception
{
}
