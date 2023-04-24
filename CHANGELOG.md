# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.5.0] - 2023-04-24
- Updating DefaultCookieJar to handle path and secure flags
- Updating Readme with example of how to use the DefaultCookieJar
- Updating composer dependencies

## [0.4.3] - 2023-03-20

### Changed
- add reference in documentation to [SST-documentation](https://github.com/SymplifyConversion/sst-documentation/)
  repository regarding cookie usage and setup

## [0.4.2] - 2022-11-29

### Changed
- changed is_numeric to is_int and is_float because of differences in behavior between PHP 7 and PHP 8

## [0.4.1] - 2022-10-04

### Changed
- changed location from where test data are fetched. 
It now uses the [SST-documentation](https://github.com/SymplifyConversion/sst-documentation/) repository
- changed location of the documentation for Audience and Server-Side Testing.
It now uses the [SST-documentations doc folder](https://github.com/SymplifyConversion/sst-documentation/tree/main/docs)

### Removed
- removed all test data from this repository. 
It is now located in [SST-documentations data folder](https://github.com/SymplifyConversion/sst-documentation/tree/main/test)
- removed all documentation in `docs` from this repository.
It is now located in [SST-documentations docs folder](https://github.com/SymplifyConversion/sst-documentation/tree/main/docs)

## [0.4.0] - 2022-09-28
### Added
- handling of custom audiences
- added test cases for custom audience
- preview cookie handling and variation choices
- bumping to 0.4.0 to keep up with the sst-sdk-node package

## [0.2.0] - 2022-06-23
### Added
- handle optin cookie
- cookies: make domain overridable, set expires
- persist allocations in cookie, we want it stable even if config changes
- add data driven SDK compatibility test suite
### Changed
- Bump guzzle dependency in example code (CVE-2022-31090, CVE-2022-31091)
- move cookie handling out from visitor module, needed for allocations as well
- total weight is always 100, allows for projects without full allocation
- don't allocate if project is inactive
- Bump guzzle dependency in example code (CVE-2022-31042, CVE-2022-31043)

## [0.1.1] - 2022-05-31
### Changed
- Bump guzzle dependency in example code (CVE-2022-29248)
- Align cookies with js-sdk
- Update package authors with contact info

## [0.1.0] - 2022-04-21
### Added
- A first version of the SDK
  - can generate visitor IDs and store in a cookie
  - can allocate project variations for visitors 
  - allocation is based on the djb2 hash function
  - use PSR-3 for logging
  - use PSR-17,PSR-18 for configuration download

[Unreleased]: https://github.com/SymplifyConversion/sst-sdk-php/compare/v0.4.3...HEAD
[0.4.3]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.4.3
[0.4.2]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.4.2
[0.4.1]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.4.1
[0.4.0]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.4.0
[0.2.0]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.2.0
[0.1.1]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.1.1
[0.1.0]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.1.0
