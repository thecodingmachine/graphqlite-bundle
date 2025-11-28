<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();

    $services->defaults()
        ->private()
        ->autowire()
        ->autoconfigure();

    $services->set(\TheCodingMachine\GraphQLite\SchemaFactory::class)
        ->args(
            [
                service('graphqlite.psr16cache'),
                service('service_container'),
            ],
        )
        ->call(
            'setAuthenticationService',
            [service(\TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface::class)],
        )
        ->call(
            'setAuthorizationService',
            [service(\TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface::class)],
        );

    $services->set(\TheCodingMachine\GraphQLite\Schema::class)
        ->public()
        ->factory([service(\TheCodingMachine\GraphQLite\SchemaFactory::class), 'createSchema']);

    $services->set(\TheCodingMachine\GraphQLite\AggregateControllerQueryProviderFactory::class)
        ->args(
            [
                [],
                service_locator([]),
            ],
        )
        ->tag('graphql.queryprovider_factory');

    $services->alias(\GraphQL\Type\Schema::class, \TheCodingMachine\GraphQLite\Schema::class);

    $services->set(\TheCodingMachine\GraphQLite\AnnotationReader::class);

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Security\AuthenticationService::class)
        ->args([service('security.token_storage')->nullOnInvalid()]);

    $services->alias(
        \TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface::class,
        \TheCodingMachine\GraphQLite\Bundle\Security\AuthenticationService::class,
    );

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Security\AuthorizationService::class)
        ->args([service('security.authorization_checker')->nullOnInvalid()]);

    $services->alias(
        \TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface::class,
        \TheCodingMachine\GraphQLite\Bundle\Security\AuthorizationService::class,
    );

    $services->set(\GraphQL\Server\ServerConfig::class, \TheCodingMachine\GraphQLite\Bundle\Server\ServerConfig::class)
        ->call('setSchema', [service(\TheCodingMachine\GraphQLite\Schema::class)])
        ->call(
            'setErrorFormatter',
            [
                [
                    \TheCodingMachine\GraphQLite\Exceptions\WebonyxErrorHandler::class,
                    'errorFormatter',
                ],
            ],
        )
        ->call(
            'setErrorsHandler',
            [
                [
                    \TheCodingMachine\GraphQLite\Exceptions\WebonyxErrorHandler::class,
                    'errorHandler',
                ],
            ],
        );

    $services->set(\GraphQL\Validator\Rules\DisableIntrospection::class)
        ->args(['$enabled' => '%graphqlite.security.disableIntrospection%']);

    $services->set(\GraphQL\Validator\Rules\QueryComplexity::class);

    $services->set(\GraphQL\Validator\Rules\QueryDepth::class);

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Controller\GraphQLiteController::class)
        ->public()
        ->tag('routing.route_loader');

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Mappers\RequestParameterMiddleware::class)
        ->tag('graphql.parameter_middleware');

    $services->set(\TheCodingMachine\GraphQLite\Validator\Mappers\Parameters\AssertParameterMiddleware::class)
        ->args([service('validator.validator_factory')])
        ->tag('graphql.parameter_middleware');

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Controller\GraphQL\LoginController::class)
        ->public()
        ->args(['$firewallName' => '%graphqlite.security.firewall_name%']);

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Controller\GraphQL\MeController::class)
        ->public();

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Types\SymfonyUserInterfaceType::class)
        ->public();

    $services->set(\TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory::class)
        ->args([[]])
        ->tag('graphql.type_mapper_factory');

    $services->set('graphqlite.phpfilescache', \Symfony\Component\Cache\Adapter\PhpFilesAdapter::class)
        ->args(
            [
                'graphqlite',
                0,
                '%kernel.cache_dir%',
            ],
        );

    $services->set('graphqlite.apcucache', \Symfony\Component\Cache\Adapter\ApcuAdapter::class)
        ->args(['graphqlite']);

    $services->set('graphqlite.psr16cache', \Symfony\Component\Cache\Psr16Cache::class)
        ->args([service('graphqlite.cache')]);

    $services->set('graphqlite.cacheclearer', \Symfony\Component\HttpKernel\CacheClearer\Psr6CacheClearer::class)
        ->args([[service('graphqlite.cache')]])
        ->tag('kernel.cache_clearer');

    $services->set(\TheCodingMachine\GraphQLite\Bundle\Command\DumpSchemaCommand::class);
};
