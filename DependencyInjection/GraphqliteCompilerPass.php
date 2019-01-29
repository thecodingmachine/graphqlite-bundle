<?php


namespace TheCodingMachine\Graphqlite\Bundle\DependencyInjection;

use function class_exists;
use Doctrine\Common\Annotations\AnnotationException;
use Doctrine\Common\Annotations\AnnotationReader as DoctrineAnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\CachedReader;
use Doctrine\Common\Cache\ApcuCache;
use function function_exists;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use Psr\Container\ContainerInterface;
use ReflectionClass;
use function str_replace;
use function strpos;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\Graphqlite\Bundle\Mappers\ContainerFetcherTypeMapper;
use TheCodingMachine\Graphqlite\Bundle\QueryProviders\ControllerQueryProvider;
use TheCodingMachine\GraphQLite\FieldsBuilderFactory;
use TheCodingMachine\GraphQLite\InputTypeGenerator;
use TheCodingMachine\GraphQLite\InputTypeUtils;
use TheCodingMachine\GraphQLite\Mappers\GlobTypeMapper;
use TheCodingMachine\GraphQLite\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQLite\Mappers\StaticTypeMapper;
use TheCodingMachine\GraphQLite\NamingStrategy;
use TheCodingMachine\GraphQLite\TypeGenerator;
use TheCodingMachine\GraphQLite\Types\MutableObjectType;
use TheCodingMachine\GraphQLite\Types\ResolvableInputObjectType;

/**
 * Detects controllers and types automatically and tag them.
 */
class GraphqliteCompilerPass implements CompilerPassInterface
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
        /**
         * @var array<string, string> An array matching class name to the the container identifier of a factory creating a type.
         */
        $types = [];

        /**
         * @var array<string, string> An array matching class name to the the container identifier of a factory creating an input type.
         */
        $inputTypes = [];

        /**
         * @var array<string, string> An array matching a Graphql type name to the the container identifier of a factory creating a type.
         */
        $typesByName = [];

        $namingStrategy = new NamingStrategy();
        $reader = $this->getAnnotationReader();
        //$inputTypeUtils = new InputTypeUtils($reader, $namingStrategy);

        // Let's scan the whole container and tag the services that belong to the namespace we want to inspect.
        $controllersNamespaces = $container->getParameter('graphqlite.namespace.controllers');
        $typesNamespaces = $container->getParameter('graphqlite.namespace.types');

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->isAbstract() || $definition->getClass() === null) {
                continue;
            }
            $class = $definition->getClass();
            foreach ($controllersNamespaces as $controllersNamespace) {
                if (strpos($class, $controllersNamespace) === 0) {
                    $definition->addTag('graphql.annotated.controller');
                }
            }

            foreach ($typesNamespaces as $typesNamespace) {
                if (strpos($class, $typesNamespace) === 0) {
                    //$definition->addTag('graphql.annotated.type');
                    // Set the types public
                    $reflectionClass = new ReflectionClass($class);
                    if ($this->getAnnotationReader()->getTypeAnnotation($reflectionClass) !== null || $this->getAnnotationReader()->getExtendTypeAnnotation($reflectionClass) !== null) {
                        $definition->setPublic(true);
                    } else {
                        foreach ($reflectionClass->getMethods() as $method) {
                            $factory = $reader->getFactoryAnnotation($method);
                            if ($factory !== null) {
                                $definition->setPublic(true);
                            }
                        }
                    }
                }
            }
        }

        foreach ($container->findTaggedServiceIds('graphql.annotated.controller') as $id => $tag) {
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();
            if ($class === null) {
                throw new \RuntimeException(sprintf('Service %s has no class defined.', $id));
            }

            $reflectionClass = new ReflectionClass($class);
            $isController = false;
            $method = null;
            foreach ($reflectionClass->getMethods() as $method) {
                $query = $reader->getRequestAnnotation($method, Query::class);
                if ($query !== null) {
                    $isController = true;
                    break;
                }
                $mutation = $reader->getRequestAnnotation($method, Mutation::class);
                if ($mutation !== null) {
                    $isController = true;
                    break;
                }
            }

            if ($isController) {
                // Let's create a QueryProvider from this controller
                $controllerIdentifier = $class.'__QueryProvider';
                $queryProvider = new Definition(ControllerQueryProvider::class);
                $queryProvider->setPrivate(true);
                $queryProvider->setFactory([self::class, 'createQueryProvider']);
                $queryProvider->addArgument(new Reference($id));
                $queryProvider->addArgument(new Reference(FieldsBuilderFactory::class));
                $queryProvider->addArgument(new Reference(RecursiveTypeMapperInterface::class));
                $queryProvider->addTag('graphql.queryprovider');
                $container->setDefinition($controllerIdentifier, $queryProvider);
            }
        }
/*
        foreach ($container->findTaggedServiceIds('graphql.annotated.type') as $id => $tag) {
            $used = false;
            $definition = $container->findDefinition($id);
            $class = $definition->getClass();
            if ($class === null) {
                throw new \RuntimeException(sprintf('Service %s has no class defined.', $id));
            }

            $reflectionClass = new ReflectionClass($class);
            foreach ($reflectionClass->getMethods() as $method) {
                $factory = $reader->getFactoryAnnotation($method);
                if ($factory !== null) {
                    $objectTypeIdentifier = $class.'__'.$method->getName().'__InputType';

                    $objectType = new Definition(ResolvableInputObjectType::class);
                    $objectType->setPrivate(false);
                    $objectType->setFactory([self::class, 'createInputObjectType']);
                    $objectType->addArgument(new Reference($id));
                    $objectType->addArgument($method->getName());
                    $objectType->addArgument(new Reference(InputTypeGenerator::class));
                    $objectType->addArgument(new Reference(RecursiveTypeMapperInterface::class));
                    $container->setDefinition($objectTypeIdentifier, $objectType);

                    [$inputName, $inputClassName] = $inputTypeUtils->getInputTypeNameAndClassName($method);

                    $inputTypes[$inputClassName] = $objectTypeIdentifier;
                    $typesByName[$inputName] = $objectTypeIdentifier;

                    $used = true;
                }
            }

            $typeAnnotation = $this->annotationReader->getTypeAnnotation($reflectionClass);
            if ($typeAnnotation !== null) {
                $objectTypeIdentifier = $class.'__Type';

                $objectType = new Definition(ObjectType::class);
                $objectType->setPrivate(false);
                $objectType->setFactory([self::class, 'createObjectType']);
                $objectType->addArgument($id);
                $objectType->addArgument(new Reference(TypeGenerator::class));
                $objectType->addArgument(new Reference(RecursiveTypeMapperInterface::class));
                $container->setDefinition($objectTypeIdentifier, $objectType);

                $types[$typeAnnotation->getClass()] = $objectTypeIdentifier;
                $typesByName[$namingStrategy->getOutputTypeName($class, $typeAnnotation)] = $objectTypeIdentifier;
                //$definition->addTag('graphql.annotated_type');

                $used = true;
            }

            // If the service is used for GraphQL, since it is referenced by service name in the factories, let's make it public
            if ($used) {
                $container->findDefinition($id)->setPublic(true);
            }
        }

        $containerFetcherTypeMapper = $container->getDefinition(ContainerFetcherTypeMapper::class);
        $containerFetcherTypeMapper->replaceArgument(1, $types);
        $containerFetcherTypeMapper->replaceArgument(2, $inputTypes);
        $containerFetcherTypeMapper->replaceArgument(3, $typesByName);
*/

        foreach ($typesNamespaces as $typesNamespace) {
            $definition = new Definition(GlobTypeMapper::class);
            $definition->addArgument($typesNamespace);
            $definition->setAutowired(true);
            $definition->addTag('graphql.type_mapper');
            $container->setDefinition('globTypeMapper_'.str_replace('\\', '__', $typesNamespace), $definition);
        }


        // Register custom output types
        $taggedServices = $container->findTaggedServiceIds('graphql.output_type');

        $customTypes = [];
        $customNotMappedTypes = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                if (isset($attributes["class"])) {
                    $phpClass = $attributes["class"];
                    if (!class_exists($phpClass)) {
                        throw new \RuntimeException(sprintf('The class attribute of the graphql.output_type annotation of the %s service must point to an existing PHP class. Value passed: %s', $id, $phpClass));
                    }
                    $customTypes[$phpClass] = new Reference($id);
                } else {
                    $customNotMappedTypes = new Reference($id);
                }
            }
        }

        if (!empty($customTypes)) {
            $definition = $container->getDefinition(StaticTypeMapper::class);
            $definition->addMethodCall('setTypes', [$customTypes]);
        }
        if (!empty($customNotMappedTypes)) {
            $definition = $container->getDefinition(StaticTypeMapper::class);
            $definition->addMethodCall('setNotMappedTypes', [$customNotMappedTypes]);
        }
    }

    /**
     * @param object $controller
     */
    public static function createQueryProvider($controller, FieldsBuilderFactory $fieldsBuilderFactory, RecursiveTypeMapperInterface $recursiveTypeMapper): ControllerQueryProvider
    {
        return new ControllerQueryProvider($controller, $fieldsBuilderFactory->buildFieldsBuilder($recursiveTypeMapper));
    }

    /**
     * @param string $typeClass
     */
    /*public static function createObjectType(string $typeClass, TypeGenerator $typeGenerator, RecursiveTypeMapperInterface $recursiveTypeMapper): MutableObjectType
    {
        return $typeGenerator->mapAnnotatedObject($typeClass, $recursiveTypeMapper);
    }*/

    /**
     * @param object $factory
     */
    /*public static function createInputObjectType($factory, string $methodName, InputTypeGenerator $inputTypeGenerator, RecursiveTypeMapperInterface $recursiveTypeMapper): InputObjectType
    {
        return $inputTypeGenerator->mapFactoryMethod($factory, $methodName, $recursiveTypeMapper);
    }*/

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

            $this->annotationReader = new AnnotationReader($doctrineAnnotationReader, AnnotationReader::LAX_MODE);
        }
        return $this->annotationReader;
    }
}
