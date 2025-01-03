<?php

namespace TheCodingMachine\GraphQLite\Bundle\GraphiQL;

use Overblog\GraphiQLBundle\Config\GraphiQLControllerEndpoint;
use Overblog\GraphiQLBundle\Config\GraphQLEndpoint\GraphQLEndpointInvalidSchemaException;
use Symfony\Component\HttpFoundation\RequestStack;
use function assert;

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

    public function getBySchema($name): string
    {
        if ('default' !== $name) {
            /** @phpstan-ignore throw.notThrowable (Missing return type in the library) */
            throw GraphQLEndpointInvalidSchemaException::forSchemaAndResolver($name, self::class);
        }

        $request = $this->requestStack->getCurrentRequest();
        assert(!is_null($request));

        return $request->getBaseUrl().'/graphql';
    }

    public function getDefault(): string
    {
        return $this->getBySchema('default');
    }
}
