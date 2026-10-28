Troubleshooting
===============

My Query or Type Does Not Appear in the Schema
----------------------------------------------

* Check that the class is in one of the namespaces configured in
  ``config/packages/graphqlite.yaml``: ``controllers`` for queries and
  mutations, ``types`` for types, input types and factories;
* check that the controller is a service (it is, if it lives in ``src/`` with
  the default ``config/services.yaml``);
* dump the schema to see what GraphQLite found:
  ``php bin/console graphqlite:dump-schema``;
* in ``prod``, the schema is cached until you clear the cache (see
  :doc:`cache-and-performance`).

Error "Cannot Autowire Service" for an Input Type
-------------------------------------------------

The full message looks like *Cannot autowire service "App\\...": argument
"$..." of method "..." is type-hinted "...", you should configure its value
explicitly*. Symfony is trying to create an input type as a service. This
happens when GraphQL controllers are in the namespace of Symfony controllers
(``App\Controller\`` by default): Symfony tags these classes with
``controller.service_arguments`` and tries to autowire every argument of
their public methods.

Move your GraphQL controllers to another namespace (for instance
``App\GraphQL\Controller\``) and update ``namespace.controllers`` in
``config/packages/graphqlite.yaml``.

The "login" Mutation or the "me" Query Is Missing
-------------------------------------------------

In ``auto`` mode, these operations are silently skipped when one of their
requirements is missing: the security bundle for ``me``; the security bundle,
a firewall, a password hasher and sessions for ``login``. Set
``enable_login: on`` (or ``enable_me: on``) temporarily: the bundle then
throws an exception that tells you what is missing. See :doc:`security`.

The Logged-in User Is Always Null
---------------------------------

The ``/graphql`` route must be covered by a firewall that authenticates the
user (session, token...). Check the ``firewalls`` section of
``config/packages/security.yaml``, and that your client sends the session
cookie or the token with each request.

I Get an Error Page Instead of a GraphQL Error
----------------------------------------------

This is the expected behavior for exceptions that are not meant for the
client: they bubble up to Symfony, so they are logged and never leak to API
clients. Throw a ``GraphQLException`` (or any exception implementing
``ClientAware``) for errors the client should see. You can change this
behavior with the ``debug`` options (see :doc:`configuration`).

Maximum Function Nesting Level Reached
--------------------------------------

The webonyx/graphql-php library uses a deep call stack. This error from
Xdebug does not mean that your code has an infinite loop. Increase the limit
in your Xdebug configuration:

.. code-block:: ini

    xdebug.max_nesting_level=500
