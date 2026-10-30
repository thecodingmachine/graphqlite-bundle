Validation
==========

GraphQL already checks the *shape* of the data: a client cannot send a string
where an ``Int!`` is expected. Business rules (a valid email, a positive
price...) are your job, and the bundle lets you use the `Symfony Validator`_
for them. It comes with the bundle: there is nothing to install.

Validating Objects
------------------

The recommended way is to put the constraints on your objects, as you would
in any Symfony application, validate them, and throw a
``ValidationFailedException`` when there are violations:

.. code-block:: php

    // src/GraphQL/Input/CreateProductInput.php
    namespace App\GraphQL\Input;

    use Symfony\Component\Validator\Constraints as Assert;
    use TheCodingMachine\GraphQLite\Annotations\Field;
    use TheCodingMachine\GraphQLite\Annotations\Input;

    #[Input]
    final class CreateProductInput
    {
        public function __construct(
            #[Field]
            #[Assert\NotBlank]
            public readonly string $name,
            #[Field]
            #[Assert\Positive]
            public readonly int $priceInCents,
        ) {
        }
    }

.. code-block:: php

    // src/GraphQL/Controller/ProductController.php
    namespace App\GraphQL\Controller;

    use App\Entity\Product;
    use App\GraphQL\Input\CreateProductInput;
    use Symfony\Component\Validator\Validator\ValidatorInterface;
    use TheCodingMachine\GraphQLite\Annotations\Mutation;
    use TheCodingMachine\GraphQLite\Validator\ValidationFailedException;

    final class ProductController
    {
        public function __construct(
            private readonly ValidatorInterface $validator,
        ) {
        }

        #[Mutation]
        public function createProduct(CreateProductInput $input): Product
        {
            ValidationFailedException::throwException($this->validator->validate($input));

            // ...
        }
    }

Each violation becomes an error in the GraphQL response, with the name of the
invalid field:

.. code-block:: json

    {
        "errors": [
            {
                "message": "This value should be positive.",
                "extensions": {
                    "category": "Validate",
                    "code": "778b7ae0-84d3-481a-9dec-35fdb64b1d78",
                    "field": "priceInCents"
                }
            }
        ]
    }

Validating Arguments
--------------------

When an argument is not mapped to an object, add the ``#[Assertion]``
attribute to the parameter:

.. code-block:: php

    use Symfony\Component\Validator\Constraints as Assert;
    use TheCodingMachine\GraphQLite\Annotations\Query;
    use TheCodingMachine\GraphQLite\Validator\Annotations\Assertion;

    #[Query]
    public function findUserByEmail(
        #[Assertion(constraint: new Assert\Email())]
        string $email,
    ): ?User {
        // ...
    }

The ``constraint`` option also accepts a list of constraints:
``#[Assertion(constraint: [new Assert\NotBlank(), new Assert\Email()])]``.

.. note::

    GraphQLite can also validate ``#[Input]`` objects automatically with an
    "input type validator", but the bundle does not configure one. Validate
    input objects in your mutation as shown above.

The GraphQLite `validation`_ documentation has more details.

.. _`Symfony Validator`: https://symfony.com/doc/current/validation.html
.. _`validation`: https://graphqlite.thecodingmachine.io/docs/validation
