<?php


namespace TheCodingMachine\Graphqlite\Bundle\DependencyInjection;


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
use TheCodingMachine\GraphQLite\GraphQLException;
use function var_dump;

class GraphqliteExtension extends Extension
{

    /**
     * Loads a specific configuration.
     *
     * @throws \InvalidArgumentException When provided tag is not defined in this extension
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        //$config = $this->processConfiguration($this->getConfiguration($config, $container), $config);
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config/container'));

        if (isset($configs[0]['namespace']['controllers'])) {
            $namespaceController = array_map(function($namespace) { return rtrim($namespace, '\\') . '\\'; }, $configs[0]['namespace']['controllers']);
        } else {
            $namespaceController = [];
        }
        if (isset($configs[0]['namespace']['types'])) {
            $namespaceType = array_map(function($namespace) { return rtrim($namespace, '\\') . '\\'; }, $configs[0]['namespace']['types']);
        } else {
            $namespaceType = [];
        }

        $container->setParameter('graphqlite.namespace.controllers', $namespaceController);
        $container->setParameter('graphqlite.namespace.types', $namespaceType);

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


        /*$definition = $container->getDefinition(Configuration::class);
        $definition->replaceArgument(0, $config['bean_namespace']);
        $definition->replaceArgument(1, $config['dao_namespace']);

        if (isset($config['naming'])) {
            $definitionNamingStrategy = $container->getDefinition(\TheCodingMachine\TDBM\Utils\DefaultNamingStrategy::class);
            $definitionNamingStrategy->addMethodCall('setBeanPrefix', [$config['naming']['bean_prefix']]);
            $definitionNamingStrategy->addMethodCall('setBeanSuffix', [$config['naming']['bean_suffix']]);
            $definitionNamingStrategy->addMethodCall('setBaseBeanPrefix', [$config['naming']['base_bean_prefix']]);
            $definitionNamingStrategy->addMethodCall('setBaseBeanSuffix', [$config['naming']['base_bean_suffix']]);
            $definitionNamingStrategy->addMethodCall('setDaoPrefix', [$config['naming']['dao_prefix']]);
            $definitionNamingStrategy->addMethodCall('setDaoSuffix', [$config['naming']['dao_suffix']]);
            $definitionNamingStrategy->addMethodCall('setBaseDaoPrefix', [$config['naming']['base_dao_prefix']]);
            $definitionNamingStrategy->addMethodCall('setBaseDaoSuffix', [$config['naming']['base_dao_suffix']]);
            $definitionNamingStrategy->addMethodCall('setExceptions', [$config['naming']['exceptions']]);
        }*/
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
