<?php

namespace TheCodingMachine\GraphQLite\Bundle\Controller;

use GraphQL\Error\Error;
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\StreamFactory;
use Laminas\Diactoros\UploadedFileFactory;
use LogicException;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use TheCodingMachine\GraphQLite\Bundle\UploadMiddlewareUtils\DummyResponseWithRequest;
use TheCodingMachine\GraphQLite\Bundle\UploadMiddlewareUtils\RequestExtractorMiddleware;
use TheCodingMachine\GraphQLite\Http\HttpCodeDecider;
use TheCodingMachine\GraphQLite\Http\HttpCodeDeciderInterface;
use GraphQL\Executor\ExecutionResult;
use GraphQL\Server\ServerConfig;
use GraphQL\Server\StandardServer;
use GraphQL\Upload\UploadMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use TheCodingMachine\GraphQLite\Bundle\Context\SymfonyGraphQLContext;
use TheCodingMachine\GraphQLite\Bundle\Exceptions\JsonException;

use function array_map;
use function class_exists;
use function get_debug_type;
use function json_decode;

/**
 * Listens to every single request and forwards GraphQL requests to Webonyx's {@see \GraphQL\Server\StandardServer}.
 */
class GraphQLiteController
{
    private HttpMessageFactoryInterface $httpMessageFactory;
    private int $debug;
    private ServerConfig $serverConfig;
    private HttpCodeDeciderInterface $httpCodeDecider;

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
            try {
                $parsedBody = json_decode(
                    json: $content,
                    associative: true,
                    flags: \JSON_THROW_ON_ERROR
                );
            } catch (\JsonException $e) {
                return $this->invalidJsonBodyResponse($e);
            }

            if (!is_array($parsedBody)) {
                return $this->invalidRequestBodyExpectedAssociativeResponse($parsedBody);
            }

            $psr7Request = $psr7Request->withParsedBody($parsedBody);
        }

        // Let's parse the request and adapt it for file uploads by extracting it from the middleware.
        if (class_exists(UploadMiddleware::class)) {
            $uploadMiddleware = new UploadMiddleware();
            $dummyResponseWithRequest = $uploadMiddleware->process($psr7Request, new RequestExtractorMiddleware());
            if (! $dummyResponseWithRequest instanceof DummyResponseWithRequest) {
                throw new LogicException(DummyResponseWithRequest::class . ' expect, got ' . get_debug_type($dummyResponseWithRequest));
            }
            $psr7Request = $dummyResponseWithRequest->getRequest();
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

    private function invalidJsonBodyResponse(\JsonException $e): JsonResponse
    {
        $jsonException = JsonException::create(
            reason: $e->getMessage(),
            code: Response::HTTP_UNSUPPORTED_MEDIA_TYPE,
            previous: $e,
        );
        $result = new ExecutionResult(
            null,
            [
                new Error(
                    'Invalid JSON.',
                    previous: $jsonException,
                    extensions: $jsonException->getExtensions(),
                ),
            ]
        );

        return new JsonResponse(
            $result->toArray($this->debug),
            $this->httpCodeDecider->decideHttpStatusCode($result)
        );
    }

    private function invalidRequestBodyExpectedAssociativeResponse(mixed $parsedBody): JsonResponse
    {
        $jsonException = JsonException::create(
            reason: 'Expecting associative array from request, got ' . gettype($parsedBody),
            code: Response::HTTP_UNPROCESSABLE_ENTITY,
        );
        $result = new ExecutionResult(
            null,
            [
                new Error(
                    'Invalid JSON.',
                    previous: $jsonException,
                    extensions: $jsonException->getExtensions(),
                ),
            ]
        );

        return new JsonResponse(
            $result->toArray($this->debug),
            $this->httpCodeDecider->decideHttpStatusCode($result)
        );
    }
}
