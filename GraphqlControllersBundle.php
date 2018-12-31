<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle;

use TheCodingMachine\Graphql\Controllers\Bundle\DependencyInjection\OverblogGraphiQLEndpointWiringPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TheCodingMachine\Graphql\Controllers\Bundle\DependencyInjection\GraphqlControllersCompilerPass;

class GraphqlControllersBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new GraphqlControllersCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new OverblogGraphiQLEndpointWiringPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
    }
}
