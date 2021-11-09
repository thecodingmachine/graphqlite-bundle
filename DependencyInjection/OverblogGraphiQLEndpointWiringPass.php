<?php

namespace TheCodingMachine\GraphQLite\Bundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TheCodingMachine\GraphQLite\Bundle\GraphiQL\EndpointResolver;

final class OverblogGraphiQLEndpointWiringPass implements CompilerPassInterface
{
    //@todo https://github.com/symfony/symfony/blob/master/src/Symfony/Component/DependencyInjection/Tests/Compiler/RemoveUnusedDefinitionsPassTest.php
    public function process(ContainerBuilder $container): void
    {
        $endPointDefinition = new Definition(EndpointResolver::class);
        $endPointDefinition->addArgument(new Reference('request_stack'));

        $container->setDefinition('overblog_graphiql.controller.graphql.endpoint', $endPointDefinition);
    }
}
