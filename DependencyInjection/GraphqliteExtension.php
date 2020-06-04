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

        if (isset($configs[0]['namespace']['controllers'])) {
            $controllers = $configs[0]['namespace']['controllers'];
            if (!is_array($controllers)) {
                $controllers = [ $controllers ];
            }
            $namespaceController = array_map(function($namespace) { return rtrim($namespace, '\\') . '\\'; }, $controllers);
        } else {
            $namespaceController = [];
        }
        if (isset($configs[0]['namespace']['types'])) {
            $types = $configs[0]['namespace']['types'];
            if (!is_array($types)) {
                $types = [ $types ];
            }
            $namespaceType = array_map(function($namespace) { return rtrim($namespace, '\\') . '\\'; }, $types);
        } else {
            $namespaceType = [];
        }

        $enableLogin = $configs[0]['security']['enable_login'] ?? 'auto';
        $enableMe = $configs[0]['security']['enable_me'] ?? 'auto';

        $container->setParameter('graphqlite.namespace.controllers', $namespaceController);
        $container->setParameter('graphqlite.namespace.types', $namespaceType);
        $container->setParameter('graphqlite.security.enable_login', $enableLogin);
        $container->setParameter('graphqlite.security.enable_me', $enableMe);
        $container->setParameter('graphqlite.security.introspection', $configs[0]['security']['introspection'] ?? true);
        $container->setParameter('graphqlite.security.maximum_query_complexity', $configs[0]['security']['maximum_query_complexity'] ?? null);
        $container->setParameter('graphqlite.security.maximum_query_depth', $configs[0]['security']['maximum_query_depth'] ?? null);
        $container->setParameter('graphqlite.security.firewall_name', $configs[0]['security']['firewall_name'] ?? 'main');

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

    private function getNamespaceDir(string $namespace): string
    {
        $classNameMapper = ClassNameMapper::createFromComposerFile(null, null, true);

        $possibleFileNames = $classNameMapper->getPossibleFileNames($namespace.'Xxx');
        if (count($possibleFileNames) > 1) {
            throw new \RuntimeException(sprintf('According to your composer.json, classes belonging to the "%s" namespace can be located in several directories: %s. This is an issue for the GraphQLite lib. Please make sure that a namespace can only be resolved to one PHP file.', $namespace, implode(", ", $possibleFileNames)));
        } elseif (empty($possibleFileNames)) {
            throw new \RuntimeException(sprintf('Files in namespace "%s" cannot be autoloaded by Composer. Please set up a PSR-4 autoloader in Composer or change the namespace configured in "graphqlite.namespace.controllers" and "graphqlite.namespace.types"', $namespace));
        }

        return substr($possibleFileNames[0], 0, -8);
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
