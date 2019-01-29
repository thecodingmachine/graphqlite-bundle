<?php


namespace TheCodingMachine\Graphqlite\Bundle;

use TheCodingMachine\Graphqlite\Bundle\DependencyInjection\OverblogGraphiQLEndpointWiringPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TheCodingMachine\Graphqlite\Bundle\DependencyInjection\GraphqliteCompilerPass;

class GraphqliteBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new GraphqliteCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new OverblogGraphiQLEndpointWiringPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
    }
}
