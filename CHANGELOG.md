# Change Log
All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/) and this project adheres to [Semantic Versioning](http://semver.org/).

## [1.3.0] - 2017-01-22
### Changed
- `JMS\Payment\CoreBundle\Model\ExtendedDataInterface` has changed. If any of your classes implement this interface, you need to update them accordingly:
    - Added missing `mayBePersisted` method
    - Added missing `$persist` parameter to `set` method
- `JMS\Payment\CoreBundle\EntityExtendedDataType::convertToDatabaseValue` now throws an exception when attempting to convert an object which does not implement `JMS\Payment\CoreBundle\Model\ExtendedDataInterface`.
- Encryption is now optional and disabled by default, unless the `secret` or `encryption.secret` configuration options are set.
- `defuse_php_encryption` is now the default encryption provider, unless when using the `secret` configuration option, in which case the default is set to `mcrypt`.
- The EntityManager is no longer closed when an Exception is thrown (#145)

### Deprecated
- The service `payment.encryption_service` has been deprecated and is now an alias to `payment.encryption.mcrypt`. Parameters specified for `payment.encryption_service` are automatically set for `payment.encryption.mcrypt` so no changes are required in service configuration until `payment.encryption_service` is removed in 2.0.
- The `secret` configuration option has been deprecated in favor of `encryption.secret` and will be removed in 2.0. Please note that if you start using `encryption.secret` you also need to set `encryption.provider` to `mcrypt` since mcrypt is not the default when using the `encryption.*` options.
- `JMS\Payment\CoreBundle\Cryptography\MCryptEncryptionService` has been deprecated and will be removed in 2.0 (`mcrypt` has been deprecated in PHP 7.1 and is removed in PHP 7.2). Refer to http://jmspaymentcorebundle.readthedocs.io/en/stable/guides/mcrypt.html for instructions on how to migrate away from `mcrypt`.

### Added
- Added ``method_options`` and ``choice_options`` to form. See the [documentation](http://jmspaymentcorebundle.readthedocs.io/en/stable/payment_form.html#choice-options) for more information.
- Added a guides section to the documentation
- Added support for custom encryption providers.
- Added support for data encryption with [defuse/php-encryption](https://github.com/defuse/php-encryption).
- Added console command to generate an encryption key for use with `defuse_php_encryption`.
- Added ability to configure which encryption provider should be used. Current available options are `mcrypt` (not recommended since it will be removed in PHP 7.2) and `defuse_php_encryption`.

### Removed
- Removed support for PHP 5.3. If you're still using PHP 5.3, please consider upgrading since it reached End Of Life in August 2014. Otherwise, use `1.2.*`.

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
