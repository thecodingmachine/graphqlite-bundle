Extending GraphQLite
====================

GraphQLite has extension points to change how PHP types are mapped, add
custom GraphQL types, or run code around every field. With the bundle, you
plug them in by registering a service with the right tag.

Services Inside Your Fields
---------------------------

Before writing an extension, note that a field can already receive any
service with the ``#[Autowire]`` attribute of GraphQLite:

.. code-block:: php

    use Symfony\Contracts\Translation\TranslatorInterface;
    use TheCodingMachine\GraphQLite\Annotations\Autowire;
    use TheCodingMachine\GraphQLite\Annotations\Field;

    #[Field]
    public function getDescription(#[Autowire] TranslatorInterface $translator): string
    {
        return $translator->trans('product.description.'.$this->id);
    }

The service is resolved from its type, and the parameter does not appear in
the GraphQL schema. The bundle makes these services public automatically.
Type-hint an interface rather than a concrete class, so the implementation
can change. ``#[Autowire]`` can also fetch a service by its id, but GraphQLite
discourages it: it hides a dependency on the container. Read more in the
GraphQLite `autowiring`_ documentation.

Service Tags
------------

=====================================  =========================================================  ===============
Tag                                    The service must be...                                     Autoconfigured
=====================================  =========================================================  ===============
``graphql.output_type``                a webonyx ``ObjectType`` (custom output type)              yes
``graphql.root_type_mapper_factory``   a ``RootTypeMapperFactoryInterface``                       yes
``graphql.type_mapper``                a ``TypeMapperInterface``                                  no
``graphql.type_mapper_factory``        a ``TypeMapperFactoryInterface``                           no
``graphql.field_middleware``           a ``FieldMiddlewareInterface``                             no
``graphql.parameter_middleware``       a ``ParameterMiddlewareInterface``                         no
``graphql.queryprovider``              a ``QueryProviderInterface``                               no
``graphql.queryprovider_factory``      a ``QueryProviderFactoryInterface``                        no
=====================================  =========================================================  ===============

*Autoconfigured* tags are added for you when the service has
``autoconfigure: true`` (the default in ``config/services.yaml``). For the
others, add the tag yourself, either on the service:

.. code-block:: yaml

    # config/services.yaml
    services:
        App\GraphQL\Middleware\LogFieldMiddleware:
            tags: ['graphql.field_middleware']

or for every class implementing an interface:

.. code-block:: yaml

    # config/services.yaml
    services:
        _instanceof:
            TheCodingMachine\GraphQLite\Middlewares\FieldMiddlewareInterface:
                tags: ['graphql.field_middleware']

Custom Output Types
-------------------

When an attribute-based type is not enough, write the GraphQL type by hand
with webonyx/graphql-php:

.. code-block:: php

    // src/GraphQL/Type/BookType.php
    namespace App\GraphQL\Type;

    use GraphQL\Type\Definition\ObjectType;
    use GraphQL\Type\Definition\Type;

    final class BookType extends ObjectType
    {
        public function __construct()
        {
            parent::__construct([
                'name' => 'Book',
                'fields' => [
                    'title' => Type::nonNull(Type::string()),
                ],
            ]);
        }
    }

Being an ``ObjectType``, the service is tagged automatically. To tell
GraphQLite that this GraphQL type represents a PHP class (so a query returning
``App\Model\Book`` uses it), add the ``class`` attribute on the tag:

.. code-block:: yaml

    # config/services.yaml
    services:
        App\GraphQL\Type\BookType:
            tags:
                - { name: 'graphql.output_type', class: 'App\Model\Book' }

Learn more in the GraphQLite documentation: `custom types`_,
`field middlewares`_, `argument resolving`_ (parameter middlewares) and
`internals`_ (type mappers).

.. _`autowiring`: https://graphqlite.thecodingmachine.io/docs/autowiring
.. _`custom types`: https://graphqlite.thecodingmachine.io/docs/custom-types
.. _`field middlewares`: https://graphqlite.thecodingmachine.io/docs/field-middlewares
.. _`argument resolving`: https://graphqlite.thecodingmachine.io/docs/argument-resolving
.. _`internals`: https://graphqlite.thecodingmachine.io/docs/internals
