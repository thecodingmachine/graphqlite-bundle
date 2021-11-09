<?php

namespace TheCodingMachine\GraphQLite\Bundle\GraphiQL;

use Overblog\GraphiQLBundle\Config\GraphiQLControllerEndpoint;
use Overblog\GraphiQLBundle\Config\GraphQLEndpoint\GraphQLEndpointInvalidSchemaException;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

final class EndpointResolver implements GraphiQLControllerEndpoint
{
    /**
     * @var RequestStack
     */
    protected $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getBySchema($name)
    {
        if ('default' === $name) {
            $request = $this->requestStack->getCurrentRequest();
            Assert::notNull($request);

            return $request->getBaseUrl().'/graphql';
        }

        throw GraphQLEndpointInvalidSchemaException::forSchemaAndResolver($name, self::class);
    }

    public function getDefault()
    {
        return $this->getBySchema('default');
    }
}
