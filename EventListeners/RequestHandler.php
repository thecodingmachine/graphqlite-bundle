<?php


namespace TheCodingMachine\Graphqlite\Bundle\EventListeners;


use GraphQL\Error\Debug;
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
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Listens to every single request and forward Graphql requests to Graphql Webonix standardServer.
 */
class RequestHandler implements EventSubscriberInterface
{
    /**
     * @var HttpMessageFactoryInterface
     */
    private $httpMessageFactory;
    /** @var StandardServer */
    private $standardServer;
    /** @var string */
    private $graphqlUri;
    /** @var string[] */
    private $graphqlHeaderList = ['application/graphql'];
    /** @var string[] */
    private $allowedMethods = [
        'GET',
        'POST',
    ];
    /** @var bool|int */
    private $debug;

    public function __construct(StandardServer $standardServer, HttpMessageFactoryInterface $httpMessageFactory = null, string $graphqlUri = '/graphql', ?int $debug = Debug::RETHROW_UNSAFE_EXCEPTIONS)
    {
        $this->standardServer = $standardServer;
        $this->httpMessageFactory = $httpMessageFactory ?: new DiactorosFactory();
        $this->graphqlUri = $graphqlUri;
        $this->debug = $debug === null ? false : $debug;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        $events[KernelEvents::REQUEST][] = array('handleRequest', 33);
        return $events;
    }

    public function handleRequest(GetResponseEvent $event)
    {
        // Let's only handle the main request (the event might be triggered for sub-requests for error displaying for instance)
        if ($event->getRequestType() !== HttpKernelInterface::MASTER_REQUEST) {
            return;
        }

        $request = $event->getRequest();

        if (!$this->isGraphqlRequest($request)) {
            return;
        }

        $psr7Request = $this->httpMessageFactory->createRequest($request);

        if (strtoupper($request->getMethod()) == "POST" && empty($psr7Request->getParsedBody())) {
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


        // Hack for Graph
        /*if (strtoupper($request->getMethod()) == "GET") {
            $params = $request->getQueryParams();
            $params["variables"] = $params["variables"] === 'undefined' ? null : $params["variables"];
            $request = $request->withQueryParams($params);
        } else {
            $params = $request->getParsedBody();
            $params["variables"] = $params["variables"] === 'undefined' ? null : $params["variables"];
            $request = $request->withParsedBody($params);
        }*/

        $result = $this->handlePsr7Request($psr7Request);

        $response = new JsonResponse($result);
        $event->setResponse($response);
    }

    private function handlePsr7Request(ServerRequestInterface $request): array
    {
        $result = $this->standardServer->executePsrRequest($request);

        if ($result instanceof ExecutionResult) {
            return $result->toArray($this->debug);
        }
        if (is_array($result)) {
            return array_map(function (ExecutionResult $executionResult) {
                return $executionResult->toArray($this->debug);
            }, $result);
        }
        if ($result instanceof Promise) {
            throw new RuntimeException('Only SyncPromiseAdapter is supported');
        }
        throw new RuntimeException('Unexpected response from StandardServer::executePsrRequest'); // @codeCoverageIgnore
    }

    private function isGraphqlRequest(Request $request) : bool
    {
        return $this->isMethodAllowed($request) && ($this->hasUri($request) || $this->hasGraphqlHeader($request));
    }
    private function isMethodAllowed(Request $request) : bool
    {
        return in_array($request->getMethod(), $this->allowedMethods, true);
    }
    private function hasUri(Request $request) : bool
    {
        return $this->graphqlUri === $request->getPathInfo();
    }
    private function hasGraphqlHeader(Request $request) : bool
    {
        if (! $request->headers->has('content-type')) {
            return false;
        }

        $requestHeaderList = $request->headers->get('content-type', null, false);
        if ($requestHeaderList === null) {
            return false;
        }
        foreach ($this->graphqlHeaderList as $allowedHeader) {
            if (in_array($allowedHeader, $requestHeaderList, true)) {
                return true;
            }
        }
        return false;
    }
}
