<?php


namespace TheCodingMachine\GraphQLite\Bundle;

use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use TheCodingMachine\GraphQLite\Bundle\DependencyInjection\GraphQLiteExtension;
use TheCodingMachine\GraphQLite\Bundle\DependencyInjection\OverblogGraphiQLEndpointWiringPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use TheCodingMachine\GraphQLite\Bundle\DependencyInjection\GraphQLiteCompilerPass;

class GraphQLiteBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new GraphQLiteCompilerPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION);
        $container->addCompilerPass(new OverblogGraphiQLEndpointWiringPass(), PassConfig::TYPE_BEFORE_OPTIMIZATION, -1);
    }

    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new GraphQLiteExtension();
        }

        return $this->extension;
    }
}
