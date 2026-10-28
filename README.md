[![Latest Stable Version](https://poser.pugx.org/thecodingmachine/graphqlite-bundle/v/stable)](https://packagist.org/packages/thecodingmachine/graphqlite-bundle)
[![License](https://poser.pugx.org/thecodingmachine/graphqlite-bundle/license)](https://packagist.org/packages/thecodingmachine/graphqlite-bundle)
[![Build Status](https://github.com/thecodingmachine/graphqlite-bundle/actions/workflows/test.yaml/badge.svg)](https://github.com/thecodingmachine/graphqlite-bundle/actions/workflows/test.yaml/badge.svg)

# GraphQLite bundle

Symfony bundle for [GraphQLite](https://graphqlite.thecodingmachine.io): build a GraphQL API by adding PHP attributes
(`#[Query]`, `#[Mutation]`, `#[Type]`, `#[Field]`...) to your classes. The bundle exposes the `/graphql` endpoint and
integrates GraphQLite with Symfony services, Security, Validator, file uploads and cache.

**Documentation: [docs/index.rst](docs/index.rst)**

## Requirements

- PHP 8.1+
- Symfony 6.4, 7.x or 8.x
- GraphQLite ^8

## Quick start

1. Install the bundle (Symfony Flex enables it and creates its configuration):

   ```bash
   composer require thecodingmachine/graphqlite-bundle
   ```

2. Tell GraphQLite where your GraphQL classes live:

   ```yaml
   # config/packages/graphqlite.yaml
   graphqlite:
       namespace:
           controllers: App\GraphQL\Controller\
           types:
               - App\Entity\
               - App\GraphQL\Input\
   ```

3. Write your first query and call `/graphql`:

   ```php
   // src/GraphQL/Controller/HelloController.php
   namespace App\GraphQL\Controller;

   use TheCodingMachine\GraphQLite\Annotations\Query;

   class HelloController
   {
       #[Query]
       public function hello(string $name = 'world'): string
       {
           return sprintf('Hello %s', $name);
       }
   }
   ```

Continue with the [getting started tutorial](docs/getting-started.rst).

## Development

- Tests: `vendor/bin/simple-phpunit`
- Static analysis: `composer phpstan`
- The code examples of `docs/` are tested: they must stay identical to the classes of `tests/DocsFixtures/`
  (see `tests/DocsExamplesTest.php`).
