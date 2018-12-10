<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\EventListeners;


use GraphQL\Server\StandardServer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Bridge\PsrHttpMessage\Factory\DiactorosFactory;
use Symfony\Bridge\PsrHttpMessage\Factory\HttpFoundationFactory;
use Symfony\Bridge\PsrHttpMessage\HttpFoundationFactoryInterface;
use Symfony\Bridge\PsrHttpMessage\HttpMessageFactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Zend\Diactoros\Response\TextResponse;

/**
 * Listens to every single request and forward GraphQL requests to GraphQL Webonix standardServer.
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

    public function __construct(StandardServer $standardServer, HttpMessageFactoryInterface $httpMessageFactory = null, string $graphqlUri = '/graphql')
    {
        $this->httpMessageFactory = $httpMessageFactory ?: new DiactorosFactory();
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
        $request = $event->getRequest();

        if (!$this->isGraphqlRequest($request)) {
            return;
        }

        $psr7Request = $this->httpMessageFactory->createRequest($request);

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
        return $this->isMethodAllowed($request) && ($this->hasUri($request) || $this->hasGraphQLHeader($request));
    }
    private function isMethodAllowed(Request $request) : bool
    {
        return in_array($request->getMethod(), $this->allowedMethods, true);
    }
    private function hasUri(Request $request) : bool
    {
        return $this->graphqlUri === $request->getPathInfo();
    }
    private function hasGraphQLHeader(Request $request) : bool
    {
        if (! $request->headers->has('content-type')) {
            return false;
        }

        $requestHeaderList = $request->headers->get('content-type');
        foreach ($this->graphqlHeaderList as $allowedHeader) {
            if (in_array($allowedHeader, $requestHeaderList, true)) {
                return true;
            }
        }
        return false;
    }
}
