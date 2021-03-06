<?php


namespace TheCodingMachine\Graphqlite\Bundle\DependencyInjection;


use TheCodingMachine\GraphQLite\Mappers\Root\RootTypeMapperFactoryInterface;
use function array_map;
use GraphQL\Error\Debug;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Definition\ObjectType;
use function implode;
use function is_dir;
use Mouf\Composer\ClassNameMapper;
use function rtrim;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException as GraphQLException;
use function var_dump;

class GraphqliteExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @param mixed[] $configs
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/container'));

        if (isset($config['namespace']['controllers'])) {
            $controllers = $config['namespace']['controllers'];
            if (!is_array($controllers)) {
                $controllers = [ $controllers ];
            }
            $namespaceController = array_map(
                function($namespace): string {
                    return rtrim($namespace, '\\') . '\\';
                },
                $controllers
            );
        } else {
            $namespaceController = [];
        }
        if (isset($config['namespace']['types'])) {
            $types = $config['namespace']['types'];
            if (!is_array($types)) {
                $types = [ $types ];
            }
            $namespaceType = array_map(
                function($namespace): string {
                    return rtrim($namespace, '\\') . '\\';
                },
                $types
            );
        } else {
            $namespaceType = [];
        }

        $enableLogin = $config['security']['enable_login'] ?? 'auto';
        $enableMe = $config['security']['enable_me'] ?? 'auto';

        $container->setParameter('graphqlite.namespace.controllers', $namespaceController);
        $container->setParameter('graphqlite.namespace.types', $namespaceType);
        $container->setParameter('graphqlite.security.enable_login', $enableLogin);
        $container->setParameter('graphqlite.security.enable_me', $enableMe);
        $container->setParameter('graphqlite.security.introspection', $config['security']['introspection'] ?? true);
        $container->setParameter('graphqlite.security.maximum_query_complexity', $config['security']['maximum_query_complexity'] ?? null);
        $container->setParameter('graphqlite.security.maximum_query_depth', $config['security']['maximum_query_depth'] ?? null);
        $container->setParameter('graphqlite.security.firewall_name', $config['security']['firewall_name'] ?? 'main');

        $loader->load('graphqlite.xml');

        $definition = $container->getDefinition(ServerConfig::class);
        if (isset($config['debug'])) {
            $debugCode = $this->toDebugCode($config['debug']);
        } else {
            $debugCode = Debug::RETHROW_UNSAFE_EXCEPTIONS;
        }
        $definition->addMethodCall('setDebug', [$debugCode]);

        $container->registerForAutoconfiguration(ObjectType::class)
            ->addTag('graphql.output_type');
        $container->registerForAutoconfiguration(RootTypeMapperFactoryInterface::class)
            ->addTag('graphql.root_type_mapper_factory');
    }

    /**
     * @param array<string, int> $debug
     * @return int
     */
    private function toDebugCode(array $debug): int
    {
        $code = 0;
        $code |= ($debug['INCLUDE_DEBUG_MESSAGE'] ?? 0)*Debug::INCLUDE_DEBUG_MESSAGE;
        $code |= ($debug['INCLUDE_TRACE'] ?? 0)*Debug::INCLUDE_TRACE;
        $code |= ($debug['RETHROW_INTERNAL_EXCEPTIONS'] ?? 0)*Debug::RETHROW_INTERNAL_EXCEPTIONS;
        $code |= ($debug['RETHROW_UNSAFE_EXCEPTIONS'] ?? 0)*Debug::RETHROW_UNSAFE_EXCEPTIONS;
        return $code;
    }
}
