<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('graphqlcontrollers');

        $rootNode
            ->children()
            ->scalarNode('controllers_namespace')->defaultValue('App\\Controllers')->end()
            ->scalarNode('types_namespace')->defaultValue('App\\Types')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
