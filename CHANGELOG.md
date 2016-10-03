# Change Log
All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.2.0] - 2016-10-03
### Added
- Added support for Symfony 3.0. Note that Symfony 3.0 introduces BC breaks. This means that you'll probably need to do more than simply updating to version `1.2.0` of this bundle for your code to keep working under Symfony 3.0. Please see Symfony's [Upgrade Guide](https://github.com/symfony/symfony/blob/master/UPGRADE-3.0.md) for information on what you need to change.

- [Docs] More clear and detailed setup instructions
- [Docs] Much more detailed guide on how to accept payments
- [Docs] Update examples for Symfony 3

### Removed
- Removed support for Symfony `<2.3`. In fact, It's not even guaranteed that versions earlier than `2.3` were ever supported. If you still want to try your chances, use `1.1.*`.

## [1.1.1] - 2016-08-21
### Fixes
- Fixed issue that caused boot to fail when a database connection was not available (#156)
- Fixed issue related to doctrine/common not being required on PHP 5.3
- Remove unneeded composer dependencies
- Require specific versions of composer dependencies

### Added
- Added support for new payment backends (through third-party bundles):
    - Stripe
    - Webpay
    - YandexKassa

## [1.1.0] - 2014-01-21
### Added
- ChoosePaymentForm: Amount can now be a callable
- Result: Exceptions thrown in plugins are now available even if the transaction is successful
- Added support for new payment backends (through third-party bundles):
    - Paymill
    - Ayden
    - Mollie
    - MultiSafepay
    - Robokassa
    - Be2bill

## [1.0.0] - 2013-08-14
Initial Release
