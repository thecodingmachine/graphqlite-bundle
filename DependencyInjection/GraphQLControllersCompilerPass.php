<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
use function function_exists;
use GraphQL\Type\Definition\ObjectType;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TheCodingMachine\GraphQL\Controllers\AnnotationReader;
use TheCodingMachine\GraphQL\Controllers\Annotations\Mutation;
use TheCodingMachine\GraphQL\Controllers\Annotations\Query;
use TheCodingMachine\GraphQL\Controllers\Annotations\Type;
use TheCodingMachine\GraphQL\Controllers\Bundle\Mappers\ContainerFetcherTypeMapper;
use TheCodingMachine\GraphQL\Controllers\ControllerQueryProvider;
use TheCodingMachine\GraphQL\Controllers\ControllerQueryProviderFactory;
use TheCodingMachine\GraphQL\Controllers\Mappers\RecursiveTypeMapper;
use TheCodingMachine\GraphQL\Controllers\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQL\Controllers\NamingStrategy;
use TheCodingMachine\GraphQL\Controllers\TypeGenerator;

/**
 * Detects controllers and types automatically and tag them.
 */
class GraphQLControllersCompilerPass implements CompilerPassInterface
{
    /**
     * @var AnnotationReader
     */
    private $annotationReader;

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        //$controllersNamespace = ltrim($container->getParameter('graphqlcontrollers.controllers_namespace'), '\\');

        /**
         * @var array<string, string> An array matching class name to the the container identifier of a factory creating a type.
         */
        $types = [];

        /**
         * @var array<string, string> An array matching a GraphQL type name to the the container identifier of a factory creating a type.
         */
        $typesByName = [];

        $namingStrategy = new NamingStrategy();

        foreach ($container->getDefinitions() as $id => $definition) {
            $class = $definition->getClass();
            if ($class === null) {
                continue;
            }

            $reflectionClass = new ReflectionClass($class);
            if ($this->isController($class, $reflectionClass)) {
                // Let's create a QueryProvider from this controller
                $controllerIdentifier = $class.'__QueryProvider';
                $queryProvider = new Definition(ControllerQueryProvider::class);
                $queryProvider->setPrivate(true);
                $queryProvider->setFactory([self::class, 'createQueryProvider']);
                $queryProvider->addArgument(new Reference($id));
                $queryProvider->addArgument(new Reference(ControllerQueryProviderFactory::class));
                $queryProvider->addArgument(new Reference(RecursiveTypeMapperInterface::class));
                $queryProvider->addTag('graphql.queryprovider');
                $container->setDefinition($controllerIdentifier, $queryProvider);
            }

            $typeAnnotation = $this->annotationReader->getTypeAnnotation($reflectionClass);
            if ($typeAnnotation !== null) {
                $objectTypeIdentifier = $class.'__Type';

                $objectType = new Definition(ObjectType::class);
                $objectType->setPrivate(false);
                $objectType->setFactory([self::class, 'createObjectType']);
                $objectType->addArgument(new Reference($id));
                $objectType->addArgument(new Reference(TypeGenerator::class));
                $objectType->addArgument(new Reference(RecursiveTypeMapperInterface::class));
                $container->setDefinition($objectTypeIdentifier, $objectType);

                $types[$typeAnnotation->getClass()] = $objectTypeIdentifier;
                $typesByName[$namingStrategy->getOutputTypeName($class, $typeAnnotation)] = $objectTypeIdentifier;
                //$definition->addTag('graphql.annotated_type');
            }
        }

        $containerFetcherTypeMapper = $container->getDefinition(ContainerFetcherTypeMapper::class);
        $containerFetcherTypeMapper->replaceArgument(1, $types);
        $containerFetcherTypeMapper->replaceArgument(3, $typesByName);
        /*$containerFetcherTypeMapper = new Definition(ContainerFetcherTypeMapper::class);
        $containerFetcherTypeMapper->addArgument($container->getDefinition('service_container'));
        $containerFetcherTypeMapper->addArgument($types);
        $containerFetcherTypeMapper->addArgument([]);
        $containerFetcherTypeMapper->addTag('graphql.type_mapper');
        $container->setDefinition(ContainerFetcherTypeMapper::class, $containerFetcherTypeMapper);*/
    }

    /**
     * @param object $controller
     */
    public static function createQueryProvider($controller, ControllerQueryProviderFactory $controllerQueryProviderFactory, RecursiveTypeMapperInterface $recursiveTypeMapper): ControllerQueryProvider
    {
        return $controllerQueryProviderFactory->buildQueryProvider($controller, $recursiveTypeMapper);
    }

    /**
     * @param object $typeClass
     */
    public static function createObjectType($typeClass, TypeGenerator $typeGenerator, RecursiveTypeMapperInterface $recursiveTypeMapper): ObjectType
    {
        return $typeGenerator->mapAnnotatedObject($typeClass, $recursiveTypeMapper);
    }

    private function isController(string $className, ReflectionClass $reflectionClass): bool
    {
        $reader = $this->getAnnotationReader();
        foreach ($reflectionClass->getMethods() as $method) {
            $query = $reader->getRequestAnnotation($method, Query::class);
            if ($query !== null) {
                return true;
            }
            $mutation = $reader->getRequestAnnotation($method, Mutation::class);
            if ($mutation !== null) {
                return true;
            }
        }
        return false;
    }

    /**
     * Returns a cached Doctrine annotation reader.
     * Note: we cannot get the annotation reader service in the container as we are in a compiler pass.
     */
    private function getAnnotationReader(): AnnotationReader
    {
        if ($this->annotationReader === null) {
            AnnotationRegistry::registerLoader('class_exists');
            $doctrineAnnotationReader = new DoctrineAnnotationReader();

            if (function_exists('apcu_fetch')) {
                $doctrineAnnotationReader = new CachedReader($doctrineAnnotationReader, new ApcuCache(), true);
            }

            $this->annotationReader = new AnnotationReader($doctrineAnnotationReader);
        }
        return $this->annotationReader;
    }
}
