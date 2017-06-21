# eZ Platform Legacy Bridge

[![Build Status](https://img.shields.io/travis/ezsystems/LegacyBridge.svg?style=flat-square)](https://travis-ci.org/ezsystems/LegacyBridge)
[![Downloads](https://img.shields.io/packagist/dt/ezsystems/legacy-bridge.svg?style=flat-square)](https://packagist.org/packages/ezsystems/legacy-bridge)
[![Latest version](https://img.shields.io/packagist/v/ezsystems/legacy-bridge.svg?style=flat-square)](https://packagist.org/packages/ezsystems/legacy-bridge)
[![License](https://img.shields.io/packagist/l/ezsystems/legacy-bridge.svg?style=flat-square)](LICENSE)

This package integrates eZ Publish Legacy into eZ Platform. It is an extract of the Legacy backwards compatibility 
features that were part of eZ Publish 5.x.

It is meant to be installed as an addition to eZ Platform, starting from version 15.01.

See [INSTALL.md](INSTALL.md) for the installation procedure.

### Roadmap

Legacy Bridge itself is stable and has been in heavy use by most community and eZ customers that have used eZ Publish 5.x series *(Community and Enterprise)*. However, there are a few things on the roadmap, in order of priority:

- A script to reliably automate installation on eZ Platform and eZ Studio to greatly simplify setup to a couple of simple instructions
- PHP 7 working *(we can't guarantee all extensions will work, but aim is to make sure `ezpublish-legacy` and `LegacyBridge` boots, and work by muting deprecation notices, as already done by eZ in eZ Publish Enterprise v5.4.7 and higher)*


### Reporting issues

As Legacy Bridge is co-maintained by the community and eZ Engineering *(as active community members)*, and not professionally supported by eZ Systems. Issues found should be reported directly here on Github. There is no SLA on fixes, this is all on voluntary basis, and we welcome you in contributing to issues in any form you you are cable of.

