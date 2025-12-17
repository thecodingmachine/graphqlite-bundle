<?php

namespace TheCodingMachine\GraphQLite\Bundle\UploadMiddlewareUtils;

use Laminas\Diactoros\Response;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Class used to allow extraction of request from PSR-15 middleware
 *
 * @internal
 */
class DummyResponseWithRequest extends Response
{
    private ServerRequestInterface $request;

    public function __construct(ServerRequestInterface $request)
    {
        parent::__construct();
        $this->request = $request;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
