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

        $rootNode->info('Read more about GraphQLite available options at: https://graphqlite.thecodingmachine.io/docs/symfony-bundle')
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
                ->booleanNode('INCLUDE_DEBUG_MESSAGE')->defaultFalse()->info('Include exception messages in output when an error arises')->end()
                ->booleanNode('INCLUDE_TRACE')->defaultFalse()->info('Include stacktrace in output when an error arises')->end()
                ->booleanNode('RETHROW_INTERNAL_EXCEPTIONS')->defaultFalse()->info('Exceptions are not caught by the engine and propagated to Symfony')->end()
                ->booleanNode('RETHROW_UNSAFE_EXCEPTIONS')->defaultTrue()->info('Exceptions that do not implement ClientAware interface are not caught by the engine and propagated to Symfony.')->end()
            ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
