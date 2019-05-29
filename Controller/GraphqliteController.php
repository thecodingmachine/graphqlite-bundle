<?php


namespace TheCodingMachine\Graphqlite\Bundle\Controller;


use function array_map;
use GraphQL\Error\ClientAware;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Server\StandardServer;
use GraphQL\Upload\UploadMiddleware;
use function json_decode;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to every single request and forward Graphql requests to Graphql Webonix standardServer.
 */
class GraphqliteController
{
    /**
     * @var HttpMessageFactoryInterface
     */
    private $httpMessageFactory;
    /** @var StandardServer */
    private $standardServer;
    /** @var bool|int */
    private $debug;

    public function __construct(StandardServer $standardServer, HttpMessageFactoryInterface $httpMessageFactory = null, ?int $debug = Debug::RETHROW_UNSAFE_EXCEPTIONS)
    {
        $this->standardServer = $standardServer;
        $this->httpMessageFactory = $httpMessageFactory ?: new DiactorosFactory();
        $this->debug = $debug ?? false;
    }

    public function loadRoutes(): RouteCollection
    {
        $routes = new RouteCollection();

        // prepare a new route
        $path = '/graphql';
        $defaults = [
            '_controller' => self::class.'::handleRequest',
        ];
        $route = new Route($path, $defaults);

        // add the new route to the route collection
        $routeName = 'graphqliteRoute';
        $routes->add($routeName, $route);

        return $routes;
    }

    public function handleRequest(Request $request): Response
    {
        $psr7Request = $this->httpMessageFactory->createRequest($request);

        if (strtoupper($request->getMethod()) === "POST" && empty($psr7Request->getParsedBody())) {
            $content = $psr7Request->getBody()->getContents();
            $parsedBody = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON received in POST body: '.json_last_error_msg());
            }
            $psr7Request = $psr7Request->withParsedBody($parsedBody);
        }

        // Let's parse the request and adapt it for file uploads.
        $uploadMiddleware = new UploadMiddleware();
        $psr7Request = $uploadMiddleware->processRequest($psr7Request);

        return $this->handlePsr7Request($psr7Request);
    }

    private function handlePsr7Request(ServerRequestInterface $request): JsonResponse
    {
        $result = $this->standardServer->executePsrRequest($request);

        if ($result instanceof ExecutionResult) {
            return new JsonResponse($result->toArray($this->debug), $this->decideHttpStatusCode($result));
        }
        if (is_array($result)) {
            $finalResult = array_map(function (ExecutionResult $executionResult) {
                return $executionResult->toArray($this->debug);
            }, $result);
            // Let's return the highest result.
            $statuses = array_map([$this, 'decideHttpStatusCode'], $result);
            $status = max($statuses);
            return new JsonResponse($finalResult, $status);
        }
        if ($result instanceof Promise) {
            throw new RuntimeException('Only SyncPromiseAdapter is supported');
        }
        throw new RuntimeException('Unexpected response from StandardServer::executePsrRequest'); // @codeCoverageIgnore
    }

    /**
     * Decides the HTTP status code based on the answer.
     *
     * @see https://github.com/APIs-guru/graphql-over-http#status-codes
     */
    private function decideHttpStatusCode(ExecutionResult $result): int
    {
        // If the data entry in the response has any value other than null (when the operation has successfully executed without error) then the response should use the 200 (OK) status code.
        if ($result->data !== null) {
            return 200;
        }

        if (empty($result->errors)) {
            return 200;
        }

        $status = 0;
        // There might be many errors. Let's return the highest code we encounter.
        foreach ($result->errors as $error) {
            if ($error->getCategory() === Error::CATEGORY_GRAPHQL) {
                $code = 400;
            } else {
                $code = $error->getCode();
            }
            $status = max($status, $code);
        }

        // If exceptions have been thrown and they have not a "HTTP like code", let's throw a 500.
        if ($status < 200) {
            $status = 500;
        }

        return $status;
    }
}
