Cache and Performance
=====================

Reading the attributes of every class to build the schema is slow, so
GraphQLite caches the result. The bundle configures this cache for you.

How the Schema Is Cached
------------------------

The cache is stored in APCu when the extension is available (and enabled for
the current SAPI), and in PHP files in the Symfony cache directory
(``var/cache/<env>/``) otherwise.

The ``schema.auto_reload`` option (see :doc:`configuration`) decides when
the cache is refreshed:

* ``true`` (default in ``dev``): GraphQLite checks your controller and type
  files on every request and refreshes the schema when one of them changed.
  Edit a class, reload, done;
* ``false`` (default in ``prod``): the schema is built once and kept until
  the cache is cleared. This is the fastest mode.

Deploying to Production
-----------------------

Run the usual cache commands as part of your deployment:

.. code-block:: terminal

    $ php bin/console cache:clear --env=prod

The bundle registers its cache as a Symfony *cache clearer*, so
``cache:clear`` also clears the GraphQLite cache.

.. caution::

    APCu memory is separate for each PHP process pool: the console (CLI) cannot
    clear the APCu cache of your web server (PHP-FPM, FrankenPHP...). When the
    schema is cached in APCu, restart or reload PHP after deploying, as you
    probably already do for OPcache.

Performance Tips
----------------

* Keep the ``namespace`` options narrow: list only the namespaces that
  contain GraphQL classes, rather than ``App\``. Fewer classes to scan means a
  faster cache warm-up and a faster ``dev`` environment;
* avoid the "N+1 queries" problem when fetching related objects: use the
  `prefetch method`_ of GraphQLite to load them in batch;
* fetch only what the client asked for with the `query plan`_;
* limit the cost of the queries clients can send with
  ``maximum_query_complexity`` and ``maximum_query_depth`` (see
  :doc:`security`).

.. _`prefetch method`: https://graphqlite.thecodingmachine.io/docs/prefetch-method
.. _`query plan`: https://graphqlite.thecodingmachine.io/docs/query-plan
