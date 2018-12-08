<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\DependencyInjection;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Annotations\Reader;
use Doctrine\Common\Cache\ApcuCache;
use function function_exists;
use GraphQL\Type\Definition\ObjectType;
use ReflectionClass;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use TheCodingMachine\GraphQL\Controllers\Annotations\Mutation;
use TheCodingMachine\GraphQL\Controllers\Annotations\Query;
use TheCodingMachine\GraphQL\Controllers\Annotations\Type;
use TheCodingMachine\GraphQL\Controllers\Bundle\Mappers\ContainerFetcherTypeMapper;
use TheCodingMachine\GraphQL\Controllers\ControllerQueryProvider;
use TheCodingMachine\GraphQL\Controllers\ControllerQueryProviderFactory;
use TheCodingMachine\GraphQL\Controllers\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQL\Controllers\TypeGenerator;

/**
 * Detects controllers and types automatically and tag them.
 */
class GraphQLControllersCompilerPass implements CompilerPassInterface
{
    /**
     * @var Reader
     */
    private $annotationReader;

    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container)
    {
        //$controllersNamespace = ltrim($container->getParameter('graphqlcontrollers.controllers_namespace'), '\\');

        /**
         * @var array<string, Definition> An array matching the container identifier to a factory creating a type.
         */
        $types = [];

        foreach ($container->getDefinitions() as $definition) {
            $class = $definition->getClass();
            if ($class === null) {
                continue;
            }

            if ($this->isController($class)) {
                // Let's create a QueryProvider from this controller
                $controllerIdentifier = $class.'__QueryProvider';
                $queryProvider = new Definition(ControllerQueryProvider::class);
                $queryProvider->setPrivate(true);
                $queryProvider->setFactory([self::class, 'createQueryProvider']);
                $queryProvider->addArgument($definition);
                $queryProvider->addArgument($container->getDefinition(ControllerQueryProviderFactory::class));
                $queryProvider->addArgument($container->getDefinition(RecursiveTypeMapperInterface::class));
                $queryProvider->addTag('graphql.queryprovider');
                $container->setDefinition($controllerIdentifier, $queryProvider);
            }

            if ($this->isType($class)) {
                $objectTypeIdentifier = $class.'__Type';

                $objectType = new Definition(ObjectType::class);
                $objectType->setPrivate(true);
                $objectType->setFactory([self::class, 'createObjectType']);
                $objectType->addArgument($definition);
                $objectType->addArgument($container->getDefinition(TypeGenerator::class));
                $objectType->addArgument($container->getDefinition(RecursiveTypeMapperInterface::class));
                $container->setDefinition($objectTypeIdentifier, $objectType);

                $types[$class] = $objectTypeIdentifier;
                //$definition->addTag('graphql.annotated_type');
                // TODO: créer une classe qui liste tout ces types et les créé à la volée.
                // en constructeur, elle prend une array className => typeClassName (à feeder par la compiler pass)
            }
        }

        $containerFetcherTypeMapper = new Definition(ContainerFetcherTypeMapper::class);
        $containerFetcherTypeMapper->addArgument($container->getDefinition('container'));
        $containerFetcherTypeMapper->addArgument($types);
        $containerFetcherTypeMapper->addArgument([]);
        $containerFetcherTypeMapper->addTag('graphql.type_mapper');
        $container->setDefinition(ContainerFetcherTypeMapper::class, $containerFetcherTypeMapper);
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

    private function isType(string $className): bool
    {
        $reflectionClass = new ReflectionClass($className);
        $typeAnnotation = $this->getAnnotationReader()->getClassAnnotation($reflectionClass, Type::class);
        return $typeAnnotation !== null;
    }

    private function isController(string $className): bool
    {
        $reader = $this->getAnnotationReader();
        $reflectionClass = new ReflectionClass($className);
        foreach ($reflectionClass->getMethods() as $method) {
            $query = $reader->getMethodAnnotation($method, Query::class);
            if ($query !== null) {
                return true;
            }
            $mutation = $reader->getMethodAnnotation($method, Mutation::class);
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
    private function getAnnotationReader(): Reader
    {
        if ($this->annotationReader === null) {
            $this->annotationReader = new AnnotationReader();

            if (function_exists('apcu_fetch')) {
                $this->annotationReader = new CachedReader($this->annotationReader, new ApcuCache(), true);
            }
        }
        return $this->annotationReader;
    }
}
