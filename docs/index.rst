GraphQLiteBundle
================

GraphQLiteBundle integrates `GraphQLite`_ into Symfony applications.

GraphQLite builds a GraphQL API from your PHP code: you add attributes such as
``#[Query]``, ``#[Mutation]``, ``#[Type]`` and ``#[Field]`` to your classes, and
GraphQLite turns them into a GraphQL schema. There is no schema file to write
and keep in sync.

The bundle takes care of the Symfony side:

* it finds your GraphQL controllers and types in the namespaces you configure
  and gets them from the service container (so autowiring just works);
* it exposes the ``/graphql`` endpoint;
* it connects GraphQLite to Symfony Security (``#[Logged]``, ``#[Right]``,
  ``#[Security]``) and can add ready-made ``login``/``logout`` mutations and a
  ``me`` query;
* it validates arguments with the Symfony Validator;
* it gives your code access to the Symfony ``Request`` and handles file uploads;
* it caches the schema and provides a command to dump it.

Requirements
------------

* PHP 8.1 or higher;
* Symfony 6.4, 7.x or 8.x;
* GraphQLite 8.

Documentation
-------------

New to the bundle? Start with :doc:`installation` and follow the
:doc:`getting-started` tutorial: you will have a working API in a few minutes.

.. toctree::
    :maxdepth: 1

    installation
    getting-started
    configuration
    security
    request-context
    file-uploads
    validation
    graphiql
    extending
    cache-and-performance
    commands
    troubleshooting

This documentation covers what is specific to Symfony. Everything about
writing queries, mutations, types, input types, pagination, error handling,
etc. is described in the `GraphQLite documentation`_, and works the same way
with this bundle.

.. _`GraphQLite`: https://graphqlite.thecodingmachine.io
.. _`GraphQLite documentation`: https://graphqlite.thecodingmachine.io/docs/
