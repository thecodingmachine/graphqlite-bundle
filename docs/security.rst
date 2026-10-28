Security
========

The bundle connects GraphQLite to the `Symfony Security`_ component. Install
it if your application does not use it yet:

.. code-block:: terminal

    $ composer require symfony/security-bundle

GraphQLite does not authenticate users itself: your Symfony firewall does,
exactly like for the rest of your application (login form, ``json_login``,
API tokens, JWT, etc.). Make sure the ``/graphql`` path is covered by a
firewall, otherwise there is never a logged-in user in your queries.

Restricting Access to Queries and Fields
----------------------------------------

Add these attributes to any query, mutation or field:

``#[Logged]``
    Only logged-in users can call it.

``#[Right('ROLE_ADMIN')]``
    Only users granted this role (or permission) can call it. The check is
    done with Symfony's ``isGranted()``, so role hierarchy and `voters`_ apply.

``#[Security("is_granted('ROLE_ADMIN') or is_granted('EDIT', product)")]``
    For anything more complex, an expression evaluated with the Symfony
    ExpressionLanguage (``composer require symfony/expression-language``).
    It can use ``is_granted()``, ``is_logged()``, the current ``user`` and the
    method arguments, so your voters receive the actual object.

For instance, deleting a product is reserved to administrators:

.. fixture: tests/DocsFixtures/GraphQL/Controller/AdminProductController.php

.. code-block:: php

    // src/GraphQL/Controller/AdminProductController.php
    namespace App\GraphQL\Controller;

    use App\Repository\ProductRepository;
    use TheCodingMachine\GraphQLite\Annotations\Logged;
    use TheCodingMachine\GraphQLite\Annotations\Mutation;
    use TheCodingMachine\GraphQLite\Annotations\Right;

    final class AdminProductController
    {
        public function __construct(
            private readonly ProductRepository $productRepository,
        ) {
        }

        #[Mutation]
        #[Logged]
        #[Right('ROLE_ADMIN')]
        public function deleteProduct(int $id): bool
        {
            return $this->productRepository->remove($id);
        }
    }

An anonymous user gets an error, with the HTTP status code 401:

.. code-block:: json

    {
        "errors": [
            {
                "message": "You need to be logged to access this field",
                "locations": [{"line": 1, "column": 12}],
                "path": ["deleteProduct"]
            }
        ]
    }

A logged-in user without ``ROLE_ADMIN`` gets a 403 error with the message
"You do not have sufficient rights to access this field".

.. tip::

    Instead of an error, you can return a default value with
    ``#[FailWith(null)]``, or remove the field from the schema for users that
    are not allowed to see it with ``#[HideIfUnauthorized]``.

To get the current user as an argument of your method, use ``#[InjectUser]``.
All these attributes are described in the GraphQLite documentation:
`authentication and authorization`_ and `fine grained security`_.

The "login", "logout" and "me" Operations
-----------------------------------------

The bundle can add three operations to your schema, so that a JavaScript
application can log in through GraphQL itself:

* a ``login(userName: String!, password: String!)`` mutation;
* a ``logout`` mutation;
* a ``me`` query that returns the current user (or ``null``).

The ``me`` query is enabled automatically as soon as the security bundle is
installed. The ``login`` and ``logout`` mutations need a bit more:

* a firewall named ``main`` (see ``firewall_name`` below) and a password
  hasher;
* sessions enabled (``framework.session``): the logged-in user is stored in
  the session, as with a classic login form.

A minimal configuration looks like this:

.. code-block:: yaml

    # config/packages/security.yaml
    security:
        password_hashers:
            Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface: 'auto'
        providers:
            app_user_provider:
                entity:
                    class: App\Entity\User
                    property: email
        firewalls:
            main:
                lazy: true
                provider: app_user_provider

The ``login`` mutation loads the user with the user provider of the firewall,
checks the password and stores the authenticated user in the session:

.. code-block:: text

    mutation {
      login(userName: "foo", password: "bar") {
        userName
        roles
      }
    }

.. code-block:: json

    {
        "data": {
            "login": {"userName": "foo", "roles": ["ROLE_USER"]}
        }
    }

The following requests (with the session cookie) are authenticated. The ``me``
query returns the current user, or ``null`` for an anonymous visitor:

.. code-block:: text

    {
      me {
        userName
        roles
      }
    }

And ``logout`` removes the user from the session:

.. code-block:: text

    mutation {
      logout
    }

Adding Fields to the User
~~~~~~~~~~~~~~~~~~~~~~~~~

The bundle maps Symfony's ``UserInterface`` to a GraphQL type with two fields:
``userName`` (from ``getUserIdentifier()``) and ``roles``. To expose more
fields, add ``#[Type]`` to your user class:

.. code-block:: php

    // src/Entity/User.php
    namespace App\Entity;

    use Symfony\Component\Security\Core\User\UserInterface;
    use TheCodingMachine\GraphQLite\Annotations\Field;
    use TheCodingMachine\GraphQLite\Annotations\Type;

    #[Type]
    class User implements UserInterface
    {
        // ...

        #[Field]
        public function getEmail(): string
        {
            return $this->email;
        }
    }

The ``me`` query returns a ``UserInterface``, so use an `inline fragment`_ to
read the extra fields:

.. code-block:: text

    {
      me {
        userName
        ... on User {
          email
        }
      }
    }

Configuring the Operations
~~~~~~~~~~~~~~~~~~~~~~~~~~

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    graphqlite:
        security:
            # "auto" (default): enabled when the requirements above are met
            # "on": always enabled; an exception explains what is missing
            # "off": disabled
            enable_login: auto
            enable_me: auto
            # the firewall used by "login" to find the user provider
            firewall_name: main

Set ``enable_login`` to ``on`` while setting things up: instead of silently
skipping the mutations, the bundle tells you which requirement is missing.
Set it to ``off`` if your users log in another way (a JWT, a login form...).

Protecting the API Itself
-------------------------

GraphQL lets clients write their own queries, so a client can also write a
query that is very expensive to run. Three options help:

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    graphqlite:
        security:
            # hide the schema from clients (__schema and __type queries)
            introspection: false
            # reject queries that request too many fields at once
            maximum_query_complexity: 314
            # reject queries that nest too many levels deep
            maximum_query_depth: 42

A common setup is to disable introspection in production only:

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    when@prod:
        graphqlite:
            security:
                introspection: false

Read more about complexity in the GraphQLite `operation complexity`_
documentation.

.. _`Symfony Security`: https://symfony.com/doc/current/security.html
.. _`voters`: https://symfony.com/doc/current/security/voters.html
.. _`authentication and authorization`: https://graphqlite.thecodingmachine.io/docs/authentication-authorization
.. _`fine grained security`: https://graphqlite.thecodingmachine.io/docs/fine-grained-security
.. _`inline fragment`: https://graphql.org/learn/queries/#inline-fragments
.. _`operation complexity`: https://graphqlite.thecodingmachine.io/docs/operation-complexity
