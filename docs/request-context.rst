The Request and the GraphQL Context
===================================

Most of the time, you don't need the HTTP request: GraphQLite reads the query
and its variables from it and calls your methods with the right arguments.
Sometimes you still need it, for instance to read a header or the client IP.

Injecting the Request
---------------------

Type-hint a parameter with the Symfony ``Request`` in any query, mutation or
field. GraphQLite fills it with the current request, and the parameter does
not appear in the GraphQL schema:

.. code-block:: php

    // src/GraphQL/Controller/ProductController.php
    namespace App\GraphQL\Controller;

    use Symfony\Component\HttpFoundation\Request;
    use TheCodingMachine\GraphQLite\Annotations\Query;

    final class ProductController
    {
        #[Query]
        public function preferredLanguage(Request $request): ?string
        {
            return $request->getPreferredLanguage(['en', 'fr']);
        }
    }

The GraphQL Context
-------------------

In webonyx/graphql-php, the *context* is an object shared by all the resolvers
of a query. The bundle uses a
``TheCodingMachine\GraphQLite\Bundle\Context\SymfonyGraphQLContext``, which
implements ``SymfonyRequestContextInterface``: call ``getRequest()`` on it to
get the Symfony request. You need it only when writing low-level code that
receives the context, such as a custom field middleware or a custom
``ObjectType`` (see :doc:`extending`).

How the Endpoint Works
----------------------

GraphQLite runs on top of webonyx/graphql-php, which works with `PSR-7`_
requests. For each request on ``/graphql``, the bundle:

#. converts the Symfony request into a PSR-7 request with the
   `PSR-7 Bridge`_;
#. decodes a JSON body if needed (an invalid JSON body gets an error
   response);
#. handles multipart file uploads (see :doc:`file-uploads`);
#. runs the query and returns a ``JsonResponse``.

The endpoint accepts ``GET`` and ``POST`` requests, with the query and the
variables in the URL, in a JSON body or in a form body. Batched queries (a
JSON list of queries) are not enabled: such a request gets a ``400`` error.

The HTTP status code depends on the result: ``200`` on success, and the
status code of the error otherwise (e.g. ``401`` or ``403`` for security
errors, ``400`` for an invalid query).

.. _`PSR-7`: https://www.php-fig.org/psr/psr-7/
.. _`PSR-7 Bridge`: https://symfony.com/doc/current/components/psr7.html
