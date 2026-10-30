Installation
============

Run this command to install the bundle:

.. code-block:: terminal

    $ composer require thecodingmachine/graphqlite-bundle

Applications that use `Symfony Flex`_ are ready to go: Flex enables the bundle
and creates two configuration files for you. Jump to
:ref:`installation-namespaces`.

Applications without Symfony Flex
---------------------------------

Enable the bundle by adding it to the list of registered bundles:

.. code-block:: php

    // config/bundles.php
    return [
        // ...
        TheCodingMachine\GraphQLite\Bundle\GraphQLiteBundle::class => ['all' => true],
    ];

Then create the two files that Flex would have created.

The route file exposes the ``/graphql`` endpoint:

.. code-block:: yaml

    # config/routes/graphqlite.yaml
    graphqlite_bundle:
        resource: '@GraphQLiteBundle/Resources/config/routes.php'

The configuration file tells the bundle where your code lives (see the next
section for its content):

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    graphqlite:
        namespace:
            controllers: App\GraphQL\Controller\
            types:
                - App\Entity\
                - App\GraphQL\Input\

.. _installation-namespaces:

Configuring the Namespaces
--------------------------

GraphQLite does not scan your whole application. It only looks at the
namespaces listed in ``config/packages/graphqlite.yaml``:

``controllers``
    The classes that declare queries and mutations (methods with
    ``#[Query]`` or ``#[Mutation]``). They are called *controllers*, like
    Symfony controllers, because they are the entry points of your API.

``types``
    The classes that describe GraphQL types: classes with ``#[Type]``,
    ``#[ExtendType]``, ``#[Input]`` or ``#[Factory]``. These can be your
    entities, DTOs, or dedicated classes that describe the GraphQL type of
    another class.

Both options accept a single namespace or a list of namespaces. The Flex
recipe uses ``App\GraphqlController\`` for controllers and the whole ``App\``
namespace for types. That works, but scanning everything is slower, so we
recommend listing only the namespaces that contain GraphQL classes:

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    graphqlite:
        namespace:
            controllers: App\GraphQL\Controller\
            types:
                - App\Entity\
                - App\GraphQL\Input\

This is the configuration used in the :doc:`getting-started` tutorial.

.. caution::

    Do not put GraphQL controllers in the ``App\Controller\`` namespace.
    Symfony tags the classes of that namespace with
    ``controller.service_arguments`` and then tries to autowire every argument
    of their public methods. This breaks as soon as a query or mutation takes
    an input type as argument. See :doc:`troubleshooting`.

Controllers Are Services
------------------------

The bundle gets controllers (and services injected in your fields) from the
service container. With the default ``config/services.yaml`` of a Symfony
application, every class in ``src/`` is already a service with autowiring
enabled, so you have nothing to do: a controller can ask for any service in its
constructor.

Entities annotated with ``#[Type]`` do not need to be services: they are the
objects returned by your queries.

Checking the Installation
-------------------------

The ``/graphql`` route must be registered:

.. code-block:: terminal

    $ php bin/console debug:router graphqliteRoute

And the bundle can print the GraphQL schema it built from your code:

.. code-block:: terminal

    $ php bin/console graphqlite:dump-schema

If your queries or types are missing from the output, check the namespaces in
``config/packages/graphqlite.yaml``. Then continue with the
:doc:`getting-started` tutorial.

.. _`Symfony Flex`: https://symfony.com/doc/current/setup/flex.html
