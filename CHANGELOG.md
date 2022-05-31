# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.1] - 2022-05-31
### Changed
- Bump guzzle dependency in example code (CVE-2022-29248)
- Update package authors with contact info

## [0.1.0] - 2022-04-21
### Added
- A first version of the SDK
  - can generate visitor IDs and store in a cookie
  - can allocate project variations for visitors 
  - allocation is based on the djb2 hash function
  - use PSR-3 for logging
  - use PSR-17,PSR-18 for configuration download

[Unreleased]: https://github.com/SymplifyConversion/sst-sdk-php/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.1.1
[0.1.0]: https://github.com/SymplifyConversion/sst-sdk-php/releases/tag/v0.1.0
