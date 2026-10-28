Configuration Reference
=======================

All the options live under the ``graphqlite`` key. This is the full list, with
the default values:

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    graphqlite:
        namespace:
            # Required. Namespace(s) of the classes with #[Query] and #[Mutation].
            # A string or a list of strings.
            controllers: App\GraphQL\Controller\
            # Required. Namespace(s) of the classes with #[Type], #[ExtendType],
            # #[Input] and #[Factory]. A string or a list of strings.
            types:
                - App\Entity\
                - App\GraphQL\Input\

        schema:
            # Check the controller and type files for changes on every request,
            # so that an edit shows up without clearing the cache.
            auto_reload: '%kernel.debug%'

        security:
            # Add the "login" and "logout" mutations: "auto", "on" or "off".
            enable_login: auto
            # Add the "me" query: "auto", "on" or "off".
            enable_me: auto
            # The firewall used by the "login" mutation.
            firewall_name: main
            # Allow clients to read the schema (__schema and __type queries).
            introspection: true
            # Reject queries above this complexity (no limit by default).
            maximum_query_complexity: ~
            # Reject queries nested deeper than this (no limit by default).
            maximum_query_depth: ~

        debug:
            # Add the exception message to the error sent to the client.
            INCLUDE_DEBUG_MESSAGE: false
            # Add the stack trace to the error sent to the client.
            INCLUDE_TRACE: false
            # Let every exception bubble up to Symfony.
            RETHROW_INTERNAL_EXCEPTIONS: false
            # Let exceptions that are not "client aware" bubble up to Symfony.
            RETHROW_UNSAFE_EXCEPTIONS: true

You can also print this reference with the Symfony console:

.. code-block:: terminal

    $ php bin/console config:dump-reference graphqlite

``namespace``
-------------

See :ref:`installation-namespaces`.

``schema``
----------

``auto_reload``
    GraphQLite builds the schema by reading the attributes of your classes,
    and caches the result. When ``auto_reload`` is ``true``, it checks whether
    the files changed on every request, which is convenient during
    development but costs some time. When ``false``, the schema is cached until
    you clear the cache. It follows ``kernel.debug`` by default, so it is
    enabled in the ``dev`` environment and disabled in ``prod``. Set it
    explicitly when an environment does not fit that rule, such as a staging
    server running with debug enabled that should still serve a cached
    schema. See :doc:`cache-and-performance`.

``security``
------------

See :doc:`security`.

``debug``
---------

These flags control how errors are reported. They are passed to the
`webonyx/graphql-php`_ library that runs the queries.

By default:

* exceptions that implement GraphQLite's ``ClientAware`` interface (like
  ``GraphQLException``) are turned into an error in the GraphQL response, with
  their message. They are meant for the client: "Product not found",
  "Invalid price", etc.;
* all other exceptions are not caught: they bubble up to Symfony, which logs
  them and shows its usual error page (or the exception page in ``dev``). This
  way, an unexpected error never leaks details to API clients, and you still
  get it in your logs.

Read the GraphQLite `error handling`_ documentation to learn how to send
errors to clients.

During development, you may prefer to see the error in the GraphQL response
instead:

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    when@dev:
        graphqlite:
            debug:
                INCLUDE_DEBUG_MESSAGE: true
                INCLUDE_TRACE: true
                RETHROW_UNSAFE_EXCEPTIONS: false

.. _`webonyx/graphql-php`: https://webonyx.github.io/graphql-php/error-handling/
.. _`error handling`: https://graphqlite.thecodingmachine.io/docs/error-handling
