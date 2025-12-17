<?php

namespace TheCodingMachine\GraphQLite\Bundle\UploadMiddlewareUtils;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Middleware to extract the request from the middleware chain
 *
 * @internal
 */
class RequestExtractorMiddleware implements RequestHandlerInterface
{
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new DummyResponseWithRequest($request);
    }

}
