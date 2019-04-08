<?php


namespace TheCodingMachine\Graphqlite\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('graphqlite');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
            //->scalarNode('controllers_namespace')->defaultValue('App\\Controllers')->end()
            //->scalarNode('types_namespace')->defaultValue('App\\Types')->end()
            ->arrayNode('namespace')/*->isRequired()*/
                ->children()
                ->arrayNode('controllers')
                    ->requiresAtLeastOneElement()
                    ->beforeNormalization()->castToArray()->end()
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('types')
                    ->requiresAtLeastOneElement()
                    ->beforeNormalization()->castToArray()->end()
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
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
