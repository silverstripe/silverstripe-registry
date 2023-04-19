# Registry module

[![CI](https://github.com/silverstripe/silverstripe-registry/actions/workflows/ci.yml/badge.svg)](https://github.com/silverstripe/silverstripe-registry/actions/workflows/ci.yml)
[![Silverstripe supported module](https://img.shields.io/badge/silverstripe-supported-0071C4.svg)](https://www.silverstripe.org/software/addons/silverstripe-commercially-supported-module-list/)

## Installation

```sh
composer require silverstripe/registry
```

## Instructions

See [developer documentation](docs/en/index.md) for more setup details.

[User documentation](docs/en/userguide/index.md)

## Known issues

PostgreSQL databases might have problems with searches, as queries done using `LIKE` are case sensitive.
