<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\DependencyInjection;

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
            //->scalarNode('controllers_namespace')->defaultValue('App\\Controllers')->end()
            //->scalarNode('types_namespace')->defaultValue('App\\Types')->end()
            ->arrayNode('namespace')->isRequired()
                ->children()
                ->scalarNode('controllers')->isRequired()->cannotBeEmpty()->end()
                ->scalarNode('types')->isRequired()->cannotBeEmpty()->end()
                ->end()
            ->end()
            ->arrayNode('debug')
                ->children()
                ->booleanNode('INCLUDE_DEBUG_MESSAGE')->defaultFalse()->end()
                ->booleanNode('INCLUDE_TRACE')->defaultFalse()->end()
                ->booleanNode('RETHROW_INTERNAL_EXCEPTIONS')->defaultFalse()->end()
                ->booleanNode('RETHROW_UNSAFE_EXCEPTIONS')->defaultTrue()->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
