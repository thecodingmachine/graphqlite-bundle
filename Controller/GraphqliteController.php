<?php


namespace TheCodingMachine\Graphqlite\Bundle\Controller;


use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use TheCodingMachine\GraphQLite\Http\HttpCodeDecider;
use function array_map;
use GraphQL\Error\ClientAware;
use GraphQL\Error\Debug;
use GraphQL\Error\Error;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Executor\Promise\Promise;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Upload\UploadMiddleware;
use function in_array;
use function json_decode;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
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
use TheCodingMachine\Graphqlite\Bundle\Context\SymfonyGraphQLContext;

/**
 * Listens to every single request and forward Graphql requests to Graphql Webonix standardServer.
 */
class GraphqliteController
{
    /**
     * @var HttpMessageFactoryInterface
     */
    private $httpMessageFactory;
    /** @var bool|int */
    private $debug;
    /**
     * @var ServerConfig
     */
    private $serverConfig;

    public function __construct(ServerConfig $serverConfig, HttpMessageFactoryInterface $httpMessageFactory = null, ?int $debug = Debug::RETHROW_UNSAFE_EXCEPTIONS)
    {
        $this->serverConfig = $serverConfig;
        $this->httpMessageFactory = $httpMessageFactory ?: new PsrHttpFactory(new ServerRequestFactory(), new StreamFactory(), new UploadedFileFactory(), new ResponseFactory());
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

        return $this->handlePsr7Request($psr7Request, $request);
    }

    private function handlePsr7Request(ServerRequestInterface $request, Request $symfonyRequest): JsonResponse
    {
        // Let's put the request in the context.
        $serverConfig = clone $this->serverConfig;
        $serverConfig->setContext(new SymfonyGraphQLContext($symfonyRequest));

        $standardService = new StandardServer($serverConfig);
        $result = $standardService->executePsrRequest($request);

        $httpCodeDecider = new HttpCodeDecider();
        if ($result instanceof ExecutionResult) {
            return new JsonResponse($result->toArray($this->debug), $httpCodeDecider->decideHttpStatusCode($result));
        }
        if (is_array($result)) {
            $finalResult = array_map(function (ExecutionResult $executionResult) {
                return $executionResult->toArray($this->debug);
            }, $result);
            // Let's return the highest result.
            $statuses = array_map([$httpCodeDecider, 'decideHttpStatusCode'], $result);
            $status = max($statuses);
            return new JsonResponse($finalResult, $status);
        }
        if ($result instanceof Promise) {
            throw new RuntimeException('Only SyncPromiseAdapter is supported');
        }
        throw new RuntimeException('Unexpected response from StandardServer::executePsrRequest'); // @codeCoverageIgnore
    }
}
