Console Commands
================

Dumping the Schema
------------------

The ``graphqlite:dump-schema`` command prints the GraphQL schema built from
your code, in the GraphQL Schema Definition Language (SDL):

.. code-block:: terminal

    $ php bin/console graphqlite:dump-schema

Use the ``--output`` (or ``-o``) option to write it to a file:

.. code-block:: terminal

    $ php bin/console graphqlite:dump-schema --output=schema.graphql

Types and fields are sorted by name, so the output is stable. A few use cases:

* check that a class is picked up by GraphQLite after changing the
  ``namespace`` configuration;
* commit the schema file and review its changes in pull requests, to spot
  breaking changes of your API;
* generate typed code for your frontend (TypeScript types, query hooks...)
  with tools such as `GraphQL Code Generator`_.

.. _`GraphQL Code Generator`: https://the-guild.dev/graphql/codegen
