<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\DependencyInjection;


use GraphQL\Error\Debug;
use GraphQL\Server\ServerConfig;
use GraphQL\Type\Definition\ObjectType;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;

class GraphqlControllersExtension extends Extension
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
        $loader->load('graphql-controllers.xml');

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
