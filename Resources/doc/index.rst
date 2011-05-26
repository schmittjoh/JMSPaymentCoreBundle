Payment\CoreBundle
==================
This bundle provides a unified view of all payment protocols being implemented 
by plugins by means of a simple facade. The payment plugin controller (PPC) can
be used to access multiple payment backends through a simple and unified API.

Additionally, the bundle provides the following facilities to plugin implementations:

  * Persistence of Financial Entities (PaymentInstruction, Payment, Credit,
    FinancialTransaction, ExtendedData)
  * Transaction Management
  * Encryption of Sensitive Data
  * Retry Logic

Usage
=====
See the DemoController for a sample usage:
https://github.com/schmittjoh/PaymentCoreBundle/blob/master/Controller/DemoController.php

Plugins
=======
Each plugin is a stateless service which provides access to a specific payment 
back end, payment processor, or payment service provider.

All plugins must implement either PluginInterface, or QueryablePluginInterface.