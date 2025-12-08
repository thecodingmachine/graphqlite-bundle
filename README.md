[![Latest Stable Version](https://poser.pugx.org/thecodingmachine/graphqlite-bundle/v/stable)](https://packagist.org/packages/thecodingmachine/graphqlite-bundle)
[![License](https://poser.pugx.org/thecodingmachine/graphqlite-bundle/license)](https://packagist.org/packages/thecodingmachine/graphqlite-bundle)
[![Build Status](https://github.com/thecodingmachine/graphqlite-bundle/actions/workflows/test.yaml/badge.svg)](https://github.com/thecodingmachine/graphqlite-bundle/actions/workflows/test.yaml/badge.svg)

# GraphQLite bundle

Symfony bundle for the `thecodingmachine/graphqlite` package.
It discovers your annotated controllers and types, builds the schema, exposes the `/graphql` endpoint through a PSR-7
bridge (with optional upload handling), and keeps the Symfony request available as the GraphQL context.

Part of the bundle docs: https://graphqlite.thecodingmachine.io/docs/symfony-bundle

See [thecodingmachine/graphqlite](https://github.com/thecodingmachine/graphqlite).

## Requirements

- PHP 8.1+
- Supports:
  - Symfony 6.4/7.0/8.0
  - GraphQLite ^8

## Installation

```bash
composer require thecodingmachine/graphqlite-bundle
```

Ensure the bundle is enabled (Symfony Flex does this automatically via `config/bundles.php` after `composer require`).

### Configure routes

Import the bundle routes to expose `/graphql`:

```yaml
# config/routes/graphqlite.yaml
graphqlite_bundle:
  resource: '@GraphQLiteBundle/Resources/config/routes.php'
```

### Configure namespaces

Tell GraphQLite where to look for controllers and types:

```yaml
# config/packages/graphqlite.yaml
graphqlite:
  namespace:
    controllers: App\\GraphQL\\Controller
    types:
      - App\\GraphQL\\Type
      - App\\Entity
```

## Quickstart

Create a controller with GraphQLite attributes:

```php
<?php
// src/GraphQL/Controller/HelloController.php
namespace App\GraphQL\Controller;

use TheCodingMachine\GraphQLite\Annotations\Query;

final class HelloController
{
    #[Query]
    public function hello(string $name = 'world'): string
    {
        return sprintf('Hello %s', $name);
    }
}
```

## Features

- Auto-discovers controllers and types from configured namespaces and registers GraphQLite services, query providers,
  type mappers, and middleware through Symfony autoconfiguration
- Ships a `/graphql` route that converts Symfony requests to PSR-7 and keeps the Symfony request in the GraphQL context
- Passes the Symfony request as context to allow using them in queries/mutations
- Supports multipart uploads when `graphql-upload` is installed
- Integrates with Symfony Security for `#[Logged]`/`#[Right]` checks
- Expose `login`/`logout` mutations plus a `me` query (opt-out)
- Symfony Validator-based user input validation
- Lets you cap introspection, query depth, and query complexity via configuration
- Uses Symfony cache (APCu or PHP files) for schema caching
- Includes a `graphqlite:dump-schema` console command to export GraphQL SDL

## GraphiQL (playground)

The bundle wires Overblog’s GraphiQL bundle if it is installed. See https://github.com/overblog/GraphiQLBundle for
enabling the UI alongside the `/graphql` endpoint.

## Development

- Tests: `vendor/bin/phpunit`
- Static analysis: `composer phpstan`
