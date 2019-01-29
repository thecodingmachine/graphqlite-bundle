<?php

namespace TheCodingMachine\Graphqlite\Bundle\GraphiQL;

use Overblog\GraphiQLBundle\Config\GraphiQLControllerEndpoint;
use Symfony\Component\HttpFoundation\RequestStack;

final class EndpointResolver implements GraphiQLControllerEndpoint
{
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getBySchema($name)
    {
        if ('default' === $name) {
            $request = $this->requestStack->getCurrentRequest();

            return $request->getBaseUrl().'/graphql';
        }

        throw GraphQLEndpointInvalidSchemaException::forSchemaAndResolver($name, self::class);
    }

    public function getDefault()
    {
        return $this->getBySchema('default');
    }
}
