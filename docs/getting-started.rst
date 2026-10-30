Getting Started
===============

This tutorial builds a small product catalog API. At the end, you will be able
to list products, fetch one product and create new products, all through
GraphQL.

It assumes the bundle is installed and configured with these namespaces (see
:doc:`installation`):

.. code-block:: yaml

    # config/packages/graphqlite.yaml
    graphqlite:
        namespace:
            controllers: App\GraphQL\Controller\
            types:
                - App\Entity\
                - App\GraphQL\Input\

Step 1: Declare a Type
----------------------

A GraphQL API returns *objects* that have *fields*. In GraphQLite, a type is a
plain PHP class with the ``#[Type]`` attribute, and each public property or
method with the ``#[Field]`` attribute becomes a field. The GraphQL type of
each field comes from the PHP type: ``int`` becomes ``Int!``, ``string``
becomes ``String!``, ``?float`` becomes ``Float`` (nullable), etc.

.. fixture: tests/DocsFixtures/Entity/Product.php

.. code-block:: php

    // src/Entity/Product.php
    namespace App\Entity;

    use TheCodingMachine\GraphQLite\Annotations\Field;
    use TheCodingMachine\GraphQLite\Annotations\Type;

    #[Type]
    class Product
    {
        public function __construct(
            #[Field]
            public readonly int $id,
            #[Field]
            public readonly string $name,
            #[Field]
            public readonly int $priceInCents,
        ) {
        }
    }

Fields can also be methods (``#[Field] public function getLabel(): string``),
which is handy for computed values.

.. note::

    Exposing an entity directly as a GraphQL type keeps this tutorial short.
    It is not required, though: many applications return dedicated classes
    (DTOs, view models) instead, so that the API does not change every time
    the storage does.

    The GraphQL type can also be declared outside of the data class, if you
    prefer to keep the class free of GraphQL attributes. Point
    ``#[Type(class: ...)]`` to the data class, and each field method receives
    the object as first argument (add the namespace of this class to the
    ``types`` option):

    .. code-block:: php

        // src/GraphQL/Type/ProductType.php
        namespace App\GraphQL\Type;

        use App\Entity\Product;
        use TheCodingMachine\GraphQLite\Annotations\Field;
        use TheCodingMachine\GraphQLite\Annotations\Type;

        #[Type(class: Product::class)]
        final class ProductType
        {
            #[Field]
            public function name(Product $product): string
            {
                return $product->name;
            }
        }

    Read more in the GraphQLite `external type declaration`_ documentation.

Step 2: Fetch the Data
----------------------

To keep this tutorial focused on GraphQL, the products are stored in memory.
In a real application, this class would load them from your storage, and the
rest of the tutorial would not change:

.. fixture: tests/DocsFixtures/Repository/ProductRepository.php

.. code-block:: php

    // src/Repository/ProductRepository.php
    namespace App\Repository;

    use App\Entity\Product;

    final class ProductRepository
    {
        /** @var array<int, Product> */
        private array $products = [];

        private int $nextId = 1;

        public function __construct()
        {
            $this->add('Mug', 950);
            $this->add('T-shirt', 1990);
        }

        /** @return list<Product> */
        public function findAll(): array
        {
            return array_values($this->products);
        }

        public function find(int $id): ?Product
        {
            return $this->products[$id] ?? null;
        }

        public function add(string $name, int $priceInCents): Product
        {
            $product = new Product($this->nextId++, $name, $priceInCents);

            return $this->products[$product->id] = $product;
        }

        public function remove(int $id): bool
        {
            if (!isset($this->products[$id])) {
                return false;
            }
            unset($this->products[$id]);

            return true;
        }
    }

Step 3: Write the Queries
-------------------------

Queries are the entry points of the API. They are public methods with the
``#[Query]`` attribute, in a class of the ``controllers`` namespace. The
controller is a regular service, so it can get the repository through its
constructor:

.. fixture: tests/DocsFixtures/GraphQL/Controller/ProductController.php

.. code-block:: php

    // src/GraphQL/Controller/ProductController.php
    namespace App\GraphQL\Controller;

    use App\Entity\Product;
    use App\GraphQL\Input\CreateProductInput;
    use App\Repository\ProductRepository;
    use TheCodingMachine\GraphQLite\Annotations\Mutation;
    use TheCodingMachine\GraphQLite\Annotations\Query;

    final class ProductController
    {
        public function __construct(
            private readonly ProductRepository $productRepository,
        ) {
        }

        /**
         * @return list<Product>
         */
        #[Query]
        public function products(): array
        {
            return $this->productRepository->findAll();
        }

        #[Query]
        public function product(int $id): ?Product
        {
            return $this->productRepository->find($id);
        }

        #[Mutation]
        public function createProduct(CreateProductInput $input): Product
        {
            return $this->productRepository->add($input->name, $input->priceInCents);
        }
    }

Let's look at what each method gives you:

* ``products()`` returns a list. PHP has no typed arrays, so the
  ``@return list<Product>`` annotation tells GraphQLite what is inside the
  array: the GraphQL type is ``[Product!]!``;
* ``product(int $id)``: method arguments become GraphQL arguments. ``?Product``
  means the query returns ``null`` when the product does not exist;
* ``createProduct()`` is a ``#[Mutation]``: the GraphQL way to say "this
  changes data". It takes an *input type*, described in the next step.

Step 4: Accept Input
--------------------

Clients send data to a mutation using *input types*. A class with the
``#[Input]`` attribute and ``#[Field]`` properties is enough. GraphQLite
passes the values sent by the client to the constructor, matching them by
name, so the object can be immutable:

.. fixture: tests/DocsFixtures/GraphQL/Input/CreateProductInput.php

.. code-block:: php

    // src/GraphQL/Input/CreateProductInput.php
    namespace App\GraphQL\Input;

    use TheCodingMachine\GraphQLite\Annotations\Field;
    use TheCodingMachine\GraphQLite\Annotations\Input;

    #[Input]
    final class CreateProductInput
    {
        public function __construct(
            #[Field]
            public readonly string $name,
            #[Field]
            public readonly int $priceInCents,
        ) {
        }
    }

The ``CreateProductInput`` object is then passed to ``createProduct()``.

Step 5: Call the API
--------------------

Start the Symfony local web server (or any web server) and send a query to
the ``/graphql`` endpoint:

.. code-block:: terminal

    $ curl -X POST http://127.0.0.1:8000/graphql \
        -H 'Content-Type: application/json' \
        -d '{"query": "{ products { id name priceInCents } }"}'

The response contains exactly the fields you asked for:

.. code-block:: json

    {
        "data": {
            "products": [
                {"id": 1, "name": "Mug", "priceInCents": 950},
                {"id": 2, "name": "T-shirt", "priceInCents": 1990}
            ]
        }
    }

From now on, queries are shown in GraphQL syntax only. The client sends them
in the ``query`` key of the JSON body, as above. Fetching a single product
with an argument:

.. code-block:: text

    {
      product(id: 2) {
        name
      }
    }

Creating a product with the mutation:

.. code-block:: text

    mutation {
      createProduct(input: {name: "Hoodie", priceInCents: 3990}) {
        id
        name
      }
    }

.. code-block:: json

    {
        "data": {
            "createProduct": {"id": 3, "name": "Hoodie"}
        }
    }

.. tip::

    Typing queries in curl gets old quickly. Install a GraphQL IDE to explore
    your schema with auto-completion: see :doc:`graphiql`.

What's Next?
------------

You built a complete, typed API with four small classes. From here:

* restrict who can call what in :doc:`security`;
* validate the user input in :doc:`validation`;
* review all the options in :doc:`configuration`.

The GraphQLite documentation covers everything else you can do with your
types: `queries`_, `mutations`_, `type mapping`_, `input types`_,
`extending a type`_, `pagination`_, `error handling`_, etc.

.. _`external type declaration`: https://graphqlite.thecodingmachine.io/docs/external-type-declaration
.. _`queries`: https://graphqlite.thecodingmachine.io/docs/queries
.. _`mutations`: https://graphqlite.thecodingmachine.io/docs/mutations
.. _`type mapping`: https://graphqlite.thecodingmachine.io/docs/type-mapping
.. _`input types`: https://graphqlite.thecodingmachine.io/docs/input-types
.. _`extending a type`: https://graphqlite.thecodingmachine.io/docs/extend-type
.. _`pagination`: https://graphqlite.thecodingmachine.io/docs/pagination
.. _`error handling`: https://graphqlite.thecodingmachine.io/docs/error-handling
