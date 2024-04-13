<?php


namespace TheCodingMachine\GraphQLite\Bundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('graphqlite');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode->info('Read more about GraphQLite available options at: https://graphqlite.thecodingmachine.io/docs/symfony-bundle')
            ->children()
            ->arrayNode('namespace')
                ->children()
                ->arrayNode('controllers')->isRequired()
                    ->beforeNormalization()->castToArray()->end()
                    ->requiresAtLeastOneElement()
                    ->scalarPrototype()->cannotBeEmpty()->end()
                ->end()
                ->arrayNode('types')->isRequired()
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
            ->arrayNode('security')
                ->children()
                ->enumNode('enable_login')->values(['on', 'off', 'auto'])->defaultValue('auto')->info('Enable to automatically create a login/logout mutation. "on": enable, "auto": enable if security bundle is available.')->end()
                ->enumNode('enable_me')->values(['on', 'off', 'auto'])->defaultValue('auto')->info('Enable to automatically create a "me" query to fetch the current user. "on": enable, "auto": enable if security bundle is available.')->end()
                ->booleanNode('introspection')->defaultValue(true)->info('Allow the introspection of the GraphQL API.')->end()
                ->integerNode('maximum_query_complexity')->info('Define a maximum query complexity value.')->end()
                ->integerNode('maximum_query_depth')->info('Define a maximum query depth value.')->end()
                ->scalarNode('firewall_name')->defaultValue('main')->info('The name of the firewall to use for login')->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
