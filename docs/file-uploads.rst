File Uploads
============

GraphQL has no built-in support for files. Most clients and servers use the
`GraphQL multipart request specification`_ to send files along with a query.
The bundle supports it through the `ecodev/graphql-upload`_ library. Install
it, and that's all:

.. code-block:: terminal

    $ composer require ecodev/graphql-upload

The bundle detects the library and handles multipart requests on
``/graphql``. To receive a file, type-hint a parameter with the PSR-7
``UploadedFileInterface``:

.. code-block:: php

    // src/GraphQL/Controller/ProductController.php
    namespace App\GraphQL\Controller;

    use Psr\Http\Message\UploadedFileInterface;
    use TheCodingMachine\GraphQLite\Annotations\Mutation;

    final class ProductController
    {
        #[Mutation]
        public function uploadProductPicture(int $productId, UploadedFileInterface $picture): bool
        {
            $picture->moveTo('/path/to/pictures/'.$productId.'.jpg');

            return true;
        }
    }

The parameter has the ``Upload`` GraphQL type. The client sends the file as a
variable:

.. code-block:: text

    mutation ($picture: Upload!) {
      uploadProductPicture(productId: 1, picture: $picture)
    }

Your GraphQL client must support the multipart request specification. See
the `list of compatible clients`_.

.. caution::

    Like any file sent by a user, validate the size and the type of the file
    before storing it, and never trust the client file name.

.. _`GraphQL multipart request specification`: https://github.com/jaydenseric/graphql-multipart-request-spec
.. _`ecodev/graphql-upload`: https://github.com/Ecodev/graphql-upload
.. _`list of compatible clients`: https://github.com/jaydenseric/graphql-multipart-request-spec#client
