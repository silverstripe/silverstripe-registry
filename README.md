# Registry module

[![CI](https://github.com/silverstripe/silverstripe-registry/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-registry/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Requirements

 * Silverstripe ^4.0

**Note:** For a Silverstripe 3.x compatible version, please use [the 1.x release line](https://github.com/silverstripe/silverstripe-registry/tree/1.0).

## Installation

Install with Composer:

```
composer require silverstripe/registry
```

When the module is installed, run a `dev/build` in your browser, or from the command line via `vendor/bin/sake dev/build`.

## Instructions

See [developer documentation](docs/en/index.md) for more setup details.

[User documentation](docs/en/userguide/index.md)

## Known issues

PostgreSQL databases might have problems with searches, as queries done using `LIKE` are case sensitive.
