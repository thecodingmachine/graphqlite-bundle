<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle;


use TheCodingMachine\GraphQL\Controllers\Bundle\DependencyInjection\OverblogGraphiQLEndpointWiringPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TheCodingMachine\GraphQL\Controllers\Bundle\DependencyInjection\GraphQLControllersCompilerPass;

class GraphQLControllersBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new GraphQLControllersCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new OverblogGraphiQLEndpointWiringPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
    }
}
