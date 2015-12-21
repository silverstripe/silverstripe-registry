# Registry module

[![Build Status](https://secure.travis-ci.org/silverstripe-labs/silverstripe-registry.png)](http://travis-ci.org/silverstripe-labs/silverstripe-registry)

## Requirements

 * SilverStripe 3.1 or newer
 * MySQL 5.1+ or SQL Server 2008+ database

## Installation

Copy the registry directory into your SilverStripe project, then append dev/build?flush=all
to the website URL in your browser. e.g. http://mysite.com/dev/build?flush=all

## Instructions

See [developer documentation](docs/en/index.md) for more setup details.

[User documentation](docs/en/userguide/index.md)

## Known issues

PostgreSQL databases might have problems with searches, as queries done using `LIKE` are case sensitive.

