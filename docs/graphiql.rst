Exploring the API with GraphiQL
===============================

`GraphiQL`_ is an in-browser IDE for GraphQL: it reads your schema and offers
auto-completion, documentation and a query editor. The bundle does not ship
it, but works out of the box with `OverblogGraphiQLBundle`_:

.. code-block:: terminal

    $ composer require --dev overblog/graphiql-bundle

The bundle needs Twig (``composer require twig``) and a route. With Symfony
Flex, the recipe creates the route for you. Otherwise, add it to the ``dev``
environment:

.. code-block:: yaml

    # config/routes/graphiql.yaml
    when@dev:
        overblog_graphiql:
            resource: '@OverblogGraphiQLBundle/Resources/config/routing.xml'

Open ``/graphiql`` in your browser. GraphQLiteBundle tells GraphiQL to send
the queries to its ``/graphql`` endpoint: there is nothing else to configure.

.. tip::

    GraphiQL needs introspection to read the schema. If you disabled
    ``introspection`` (see :doc:`security`), enable it in the ``dev``
    environment.

You can also use any standalone GraphQL client (Altair, Insomnia, Postman,
etc.): point it to ``https://your-app.example.com/graphql``.

.. _`GraphiQL`: https://github.com/graphql/graphiql
.. _`OverblogGraphiQLBundle`: https://github.com/overblog/GraphiQLBundle
