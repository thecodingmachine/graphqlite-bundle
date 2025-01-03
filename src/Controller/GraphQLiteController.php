<?php


namespace TheCodingMachine\GraphQLite\Bundle\Controller;


use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Upload\UploadMiddleware;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TheCodingMachine\GraphQLite\Bundle\Context\SymfonyGraphQLContext;
use TheCodingMachine\GraphQLite\Http\HttpCodeDecider;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;
use function array_map;
use function class_exists;
use function json_decode;

/**
 * Listens to every single request and forward Graphql requests to Graphql Webonix standardServer.
 */
class GraphQLiteController
{
    /**
     * @var HttpMessageFactoryInterface
     */
    private $httpMessageFactory;
    /** @var int */
    private $debug;
    /**
     * @var ServerConfig
     */
    private $serverConfig;
    /**
     * @var HttpCodeDeciderInterface
     */
    private $httpCodeDecider;

    public function __construct(ServerConfig $serverConfig, ?HttpMessageFactoryInterface $httpMessageFactory = null, ?int $debug = null, ?HttpCodeDeciderInterface $httpCodeDecider = null)
    {
        $this->serverConfig = $serverConfig;
        $this->httpMessageFactory = $httpMessageFactory ?: new PsrHttpFactory(new ServerRequestFactory(), new StreamFactory(), new UploadedFileFactory(), new ResponseFactory());
        $this->debug = $debug ?? $serverConfig->getDebugFlag();
        $this->httpCodeDecider = $httpCodeDecider ?? new HttpCodeDecider();
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

        if (strtoupper($request->getMethod()) === 'POST' && empty($psr7Request->getParsedBody())) {
            $content = $psr7Request->getBody()->getContents();
            $parsedBody = json_decode($content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \RuntimeException('Invalid JSON received in POST body: '.json_last_error_msg());
            }
            if (!is_array($parsedBody)){
                throw new \RuntimeException('Expecting associative array from request, got ' . gettype($parsedBody));
            }
            $psr7Request = $psr7Request->withParsedBody($parsedBody);
        }

        // Let's parse the request and adapt it for file uploads.
        if (class_exists(UploadMiddleware::class)) {
            $uploadMiddleware = new UploadMiddleware();
            $psr7Request = $uploadMiddleware->processRequest($psr7Request);
            \assert($psr7Request instanceof ServerRequestInterface);
        }

        return $this->handlePsr7Request($psr7Request, $request);
    }

    private function handlePsr7Request(ServerRequestInterface $request, Request $symfonyRequest): JsonResponse
    {
        // Let's put the request in the context.
        $serverConfig = clone $this->serverConfig;
        $serverConfig->setContext(new SymfonyGraphQLContext($symfonyRequest));

        $standardService = new StandardServer($serverConfig);
        $result = $standardService->executePsrRequest($request);

        if ($result instanceof ExecutionResult) {
            return new JsonResponse($result->toArray($this->debug), $this->httpCodeDecider->decideHttpStatusCode($result));
        }
        if (is_array($result)) {
            $finalResult = array_map(function (ExecutionResult $executionResult): array {
                return $executionResult->toArray($this->debug);
            }, $result);
            // Let's return the highest result.
            $statuses = array_map([$this->httpCodeDecider, 'decideHttpStatusCode'], $result);
            $status = empty($statuses) ? 500 : max($statuses);

            return new JsonResponse($finalResult, $status);
        }

        throw new RuntimeException('Only SyncPromiseAdapter is supported');
    }
}
