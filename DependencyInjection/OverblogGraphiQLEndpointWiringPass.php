<?php

namespace TheCodingMachine\GraphQL\Controllers\Bundle\DependencyInjection;

use Overblog\GraphiQLBundle\Config\GraphiQLControllerEndpoint;
use Overblog\GraphiQLBundle\Config\GraphQLEndpoint\RootResolver;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TheCodingMachine\GraphQL\Controllers\Bundle\GraphiQL\EndpointResolver;

final class OverblogGraphiQLEndpointWiringPass implements CompilerPassInterface
{
    //@todo https://github.com/symfony/symfony/blob/master/src/Symfony/Component/DependencyInjection/Tests/Compiler/RemoveUnusedDefinitionsPassTest.php
    public function process(ContainerBuilder $container)
    {
        $endPointDefinition = new Definition(EndpointResolver::class);
        $endPointDefinition->addArgument(new Reference('request_stack'));

        $container->setDefinition('overblog_graphiql.controller.graphql.endpoint', $endPointDefinition);

    }
}
