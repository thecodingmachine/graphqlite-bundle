<?php


namespace TheCodingMachine\GraphQLite\Bundle\DependencyInjection;

use Generator;
use GraphQL\Server\ServerConfig;
use GraphQL\Validator\Rules\DisableIntrospection;
use GraphQL\Validator\Rules\QueryComplexity;
use GraphQL\Validator\Rules\QueryDepth;
use Kcs\ClassFinder\Finder\ComposerFinder;
use Psr\SimpleCache\CacheInterface;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use Symfony\Component\Cache\Adapter\ApcuAdapter;
use Symfony\Component\Cache\Adapter\PhpFilesAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use TheCodingMachine\CacheUtils\ClassBoundCache;
use TheCodingMachine\CacheUtils\ClassBoundCacheContract;
use TheCodingMachine\CacheUtils\ClassBoundCacheContractInterface;
use TheCodingMachine\CacheUtils\ClassBoundMemoryAdapter;
use TheCodingMachine\CacheUtils\FileBoundCache;
use TheCodingMachine\GraphQLite\AggregateControllerQueryProviderFactory;
use TheCodingMachine\GraphQLite\AnnotationReader;
use TheCodingMachine\GraphQLite\Annotations\Autowire;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Bundle\Controller\GraphQL\LoginController;
use TheCodingMachine\GraphQLite\Bundle\Controller\GraphQL\MeController;
use TheCodingMachine\GraphQLite\Bundle\Types\SymfonyUserInterfaceType;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException as GraphQLException;
use TheCodingMachine\GraphQLite\Mappers\StaticClassListTypeMapperFactory;
use TheCodingMachine\GraphQLite\Mappers\StaticTypeMapper;
use TheCodingMachine\GraphQLite\SchemaFactory;
use Webmozart\Assert\Assert;
use function assert;
use function class_exists;
use function filter_var;
use function ini_get;
use function interface_exists;

/**
 * Detects controllers and types automatically and tag them.
 */
class GraphQLiteCompilerPass implements CompilerPassInterface
{
    private ?AnnotationReader $annotationReader = null;

    private string $cacheDir;

    private ?CacheInterface $cache = null;

    private ?ClassBoundCacheContractInterface $codeCache = null;


    /**
     * You can modify the container here before it is dumped to PHP code.
     */
    public function process(ContainerBuilder $container): void
    {
        $reader = $this->getAnnotationReader();
        $cacheDir = $container->getParameter('kernel.cache_dir');
        assert(is_string($cacheDir));
        $this->cacheDir = $cacheDir;
        //$inputTypeUtils = new InputTypeUtils($reader, $namingStrategy);

        // Let's scan the whole container and tag the services that belong to the namespace we want to inspect.
        $controllersNamespaces = $container->getParameter('graphqlite.namespace.controllers');
        $typesNamespaces = $container->getParameter('graphqlite.namespace.types');
        assert(is_iterable($controllersNamespaces));
        assert(is_iterable($typesNamespaces));

        $firewallName = $container->getParameter('graphqlite.security.firewall_name');
        assert(is_string($firewallName));
        $firewallConfigServiceName = 'security.firewall.map.config.'.$firewallName;

        // 2 seconds of TTL in environment mode. Otherwise, let's cache forever!

        $schemaFactory = $container->getDefinition(SchemaFactory::class);

        $env = $container->getParameter('kernel.environment');
        if ($env === 'prod') {
            $schemaFactory->addMethodCall('prodMode');
        } elseif ($env === 'dev') {
            $schemaFactory->addMethodCall('devMode');
        }

        $disableLogin = false;
        if ($container->getParameter('graphqlite.security.enable_login') === 'auto'
         && (!$container->has($firewallConfigServiceName) ||
                !$container->has(UserPasswordHasherInterface::class) ||
                !$container->has(TokenStorageInterface::class) ||
                !$container->has('session.factory')
            )) {
            $disableLogin = true;
        }
        if ($container->getParameter('graphqlite.security.enable_login') === 'off') {
            $disableLogin = true;
        }
        // If the security is disabled, let's remove the LoginController
        if ($disableLogin === true) {
            $container->removeDefinition(LoginController::class);
        }

        if ($container->getParameter('graphqlite.security.enable_login') === 'on') {
            if (!$container->has('session.factory')) {
                throw new GraphQLException('In order to enable the login/logout mutations (via the graphqlite.security.enable_login parameter), you need to enable session support (via the "framework.session.enabled" config parameter).');
            }
            if (!$container->has(UserPasswordHasherInterface::class) || !$container->has(TokenStorageInterface::class) || !$container->has($firewallConfigServiceName)) {
                throw new GraphQLException('In order to enable the login/logout mutations (via the graphqlite.security.enable_login parameter), you need to install the security bundle. Please be sure to correctly configure the user provider (in the security.providers configuration settings)');
            }
        }

        if ($disableLogin === false) {
            // Let's do some dark magic. We need the user provider. We need its name. It is stored in the "config" object.
            $providerConfigKey = 'security.firewall.map.config.'.$firewallName;
            $provider = $container->findDefinition($providerConfigKey)->getArgument(5);
            if (!is_string($provider)){
                throw new GraphQLException('Expecting to find user provider name from ' . $providerConfigKey);
            }

            $container->findDefinition(LoginController::class)->setArgument(0, new Reference($provider));

            $this->registerController(LoginController::class, $container);
        }

        $disableMe = false;
        if ($container->getParameter('graphqlite.security.enable_me') === 'auto'
            && !$container->has(TokenStorageInterface::class)) {
            $disableMe = true;
        }
        if ($container->getParameter('graphqlite.security.enable_me') === 'off') {
            $disableMe = true;
        }
        // If the security is disabled, let's remove the LoginController
        if ($disableMe === true) {
            $container->removeDefinition(MeController::class);
        }

        if ($container->getParameter('graphqlite.security.enable_me') === 'on') {
            if (!$container->has(TokenStorageInterface::class)) {
                throw new GraphQLException('In order to enable the "me" query (via the graphqlite.security.enable_me parameter), you need to install the security bundle.');
            }
        }

        // ServerConfig rules
        $serverConfigDefinition = $container->findDefinition(ServerConfig::class);
        $rulesDefinition = [];
        if ($container->getParameter('graphqlite.security.disableIntrospection')) {
            $rulesDefinition[] =  $container->findDefinition(DisableIntrospection::class);
        }

        $complexity = $container->getParameter('graphqlite.security.maximum_query_complexity');
        if ($complexity) {
            Assert::integerish($complexity);

            $rulesDefinition[] =  $container->findDefinition(QueryComplexity::class)
                ->setArgument(0, (int) $complexity);
        }

        $depth = $container->getParameter('graphqlite.security.maximum_query_depth');
        if ($depth) {
            Assert::integerish($depth);

            $rulesDefinition[] =  $container->findDefinition(QueryDepth::class)
                ->setArgument(0, (int) $depth);
        }

        $serverConfigDefinition->addMethodCall('setValidationRules', [$rulesDefinition]);

        if ($disableMe === false) {
            $this->registerController(MeController::class, $container);
        }

        // Perf improvement: let's remove the AggregateControllerQueryProviderFactory if it is empty.
        if (empty($container->findDefinition(AggregateControllerQueryProviderFactory::class)->getArgument(0))) {
            $container->removeDefinition(AggregateControllerQueryProviderFactory::class);
        }

        // Let's register the mapping with UserInterface if UserInterface is available.
        if (interface_exists(UserInterface::class)) {
            $staticTypes = $container->getDefinition(StaticClassListTypeMapperFactory::class)->getArgument(0);
            if (!is_array($staticTypes)){
                throw new GraphQLException(sprintf('Expecting array in %s, arg #1', StaticClassListTypeMapperFactory::class));
            }
            $staticTypes[] = SymfonyUserInterfaceType::class;
            $container->getDefinition(StaticClassListTypeMapperFactory::class)->setArgument(0, $staticTypes);
        }

        foreach ($container->getDefinitions() as $definition) {
            if ($definition->isAbstract() || $definition->getClass() === null) {
                continue;
            }

            /** @var class-string $class */
            $class = $definition->getClass();

            foreach ($typesNamespaces as $typesNamespace) {
                \assert(\is_string($typesNamespace));

                if (false === str_starts_with($class, $typesNamespace)) {
                    continue;
                }

                // Set the types public
                $reflectionClass = new ReflectionClass($class);
                $typeAnnotation = $this->getAnnotationReader()->getTypeAnnotation($reflectionClass);
                if ($typeAnnotation !== null && $typeAnnotation->isSelfType()) {
                    continue;
                }
                if ($typeAnnotation !== null || $this->getAnnotationReader()->getExtendTypeAnnotation($reflectionClass) !== null) {
                    $definition->setPublic(true);
                }
                foreach ($reflectionClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                    $factory = $reader->getFactoryAnnotation($method);
                    if ($factory !== null) {
                        $definition->setPublic(true);
                    }
                }
            }
        }

        foreach ($controllersNamespaces as $controllersNamespace) {
            \assert(\is_string($controllersNamespace));

            $schemaFactory->addMethodCall('addNamespace', [ $controllersNamespace ]);
            foreach ($this->getClassList($controllersNamespace) as $refClass) {
                $this->makePublicInjectedServices($refClass, $reader, $container, true);
            }
        }

        foreach ($typesNamespaces as $typeNamespace) {
            \assert(\is_string($typeNamespace));

            $schemaFactory->addMethodCall('addNamespace', [ $typeNamespace ]);
            foreach ($this->getClassList($typeNamespace) as $refClass) {
                $this->makePublicInjectedServices($refClass, $reader, $container, false);
            }
        }

        // Register custom output types
        $taggedServices = $container->findTaggedServiceIds('graphql.output_type');

        $customTypes = [];
        $customNotMappedTypes = [];
        foreach ($taggedServices as $id => $tags) {
            foreach ($tags as $attributes) {
                \assert(\is_array($attributes));

                if (isset($attributes['class']) && is_string($attributes['class'])) {
                    $phpClass = $attributes['class'];
                    if (false === class_exists($phpClass)) {
                        throw new \RuntimeException(sprintf('The class attribute of the graphql.output_type annotation of the %s service must point to an existing PHP class. Value passed: %s', $id, $phpClass));
                    }

                    $customTypes[$phpClass] = new Reference($id);
                } else {
                    $customNotMappedTypes[] = new Reference($id);
                }
            }
        }

        $staticMapperDefinition = new Definition(StaticTypeMapper::class, [
            '$types' => $customTypes,
            '$notMappedTypes' => $customNotMappedTypes
        ]);
        $staticMapperDefinition->addTag('graphql.type_mapper');
        $container->setDefinition(StaticTypeMapper::class, $staticMapperDefinition);

        // Register graphql.queryprovider
        $this->mapAdderToTag('graphql.queryprovider', 'addQueryProvider', $container, $schemaFactory);
        $this->mapAdderToTag('graphql.queryprovider_factory', 'addQueryProviderFactory', $container, $schemaFactory);
        $this->mapAdderToTag('graphql.root_type_mapper_factory', 'addRootTypeMapperFactory', $container, $schemaFactory);
        $this->mapAdderToTag('graphql.parameter_middleware', 'addParameterMiddleware', $container, $schemaFactory);
        $this->mapAdderToTag('graphql.field_middleware', 'addFieldMiddleware', $container, $schemaFactory);
        $this->mapAdderToTag('graphql.type_mapper', 'addTypeMapper', $container, $schemaFactory);
        $this->mapAdderToTag('graphql.type_mapper_factory', 'addTypeMapperFactory', $container, $schemaFactory);

        // Configure cache
        if (ApcuAdapter::isSupported() && (PHP_SAPI !== 'cli' || filter_var(ini_get('apc.enable_cli'), FILTER_VALIDATE_BOOLEAN))) {
            $container->setAlias('graphqlite.cache', 'graphqlite.apcucache');
        } else {
            $container->setAlias('graphqlite.cache', 'graphqlite.phpfilescache');
        }
    }

    private function registerController(string $controllerClassName, ContainerBuilder $container): void
    {
        $aggregateQueryProvider = $container->findDefinition(AggregateControllerQueryProviderFactory::class);

        $controllersList = $aggregateQueryProvider->getArgument(0);
        if (!is_array($controllersList)){
            throw new GraphQLException(sprintf('Expecting array in %s, arg #1', AggregateControllerQueryProviderFactory::class));
        }

        $controllersList[] = $controllerClassName;
        $aggregateQueryProvider->setArgument(0, $controllersList);

        $serviceLocatorMap = [];
        foreach ($controllersList as $controller) {
            \assert(\is_string($controller));

            $serviceLocatorMap[$controller] = new Reference($controller);
        }

        $aggregateQueryProvider->setArgument(1, new ServiceLocatorArgument($serviceLocatorMap));
    }

    /**
     * Register a method call on SchemaFactory for each tagged service, passing the service in parameter.
     */
    private function mapAdderToTag(string $tag, string $methodName, ContainerBuilder $container, Definition $schemaFactory): void
    {
        $taggedServices = $container->findTaggedServiceIds($tag);

        foreach ($taggedServices as $id => $tags) {
            $schemaFactory->addMethodCall($methodName, [new Reference($id)]);
        }
    }

    /**
     * @param ReflectionClass<object> $refClass
     */
    private function makePublicInjectedServices(ReflectionClass $refClass, AnnotationReader $reader, ContainerBuilder $container, bool $isController): void
    {
        $services = $this->getCodeCache()->get($refClass, function() use ($refClass, $reader, $container, $isController): array {
            $services = [];
            foreach ($refClass->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
                $field = $reader->getRequestAnnotation($method, Field::class) ?? $reader->getRequestAnnotation($method, Query::class) ?? $reader->getRequestAnnotation($method, Mutation::class);
                if ($field !== null) {
                    if ($isController) {
                        $services[$refClass->getName()] = $refClass->getName();
                    }
                    $services += $this->getListOfInjectedServices($method, $container);
                    if ($field instanceof Field && $field->getPrefetchMethod() !== null) {
                        $services += $this->getListOfInjectedServices($refClass->getMethod($field->getPrefetchMethod()), $container);
                    }
                }
            }

            return $services;
        });

        if (!is_array($services)){
            throw new GraphQLException('An error occurred in compiler pass');
        }

        foreach ($services as $service) {
            if (!is_string($service)){
                throw new GraphQLException('expecting string as service');
            }
            if ($container->hasAlias($service)) {
                $container->getAlias($service)->setPublic(true);
            } else {
                $container->getDefinition($service)->setPublic(true);
            }
        }

    }

    /**
     * @return array<string, string> key = value = service name
     */
    private function getListOfInjectedServices(ReflectionMethod $method, ContainerBuilder $container): array
    {
        /** @var array<string, string> $services */
        $services = [];

        $parametersByName = null;

        $annotations = $this->getAnnotationReader()->getParameterAnnotationsPerParameter($method->getParameters());
        foreach ($annotations as $parameterName => $parameterAnnotations) {
            $parameterAutowireAnnotation = $parameterAnnotations->getAnnotationsByType(Autowire::class);
            foreach ($parameterAutowireAnnotation as $autowire) {
                $id = $autowire->getIdentifier();
                if ($id !== null) {
                    $services[$id] = $id;
                    continue;
                }

                $parametersByName ??= self::getParametersByName($method);
                if (!isset($parametersByName[$parameterName])) {
                    throw new \LogicException('Should not happen');
                }

                $parameter = $parametersByName[$parameterName];
                $type = $parameter->getType();
                if ($type instanceof ReflectionNamedType) {
                    $fqcn = $type->getName();
                    if ($container->has($fqcn)) {
                        $services[$fqcn] = $fqcn;
                    }
                }
            }
        }

        return $services;
    }

    /**
     * @return array<string, ReflectionParameter>
     */
    private static function getParametersByName(ReflectionMethod $method): array
    {
        $parameters = [];
        foreach ($method->getParameters() as $parameter) {
            $parameters[$parameter->getName()] = $parameter;
        }
        return $parameters;
    }

    /**
     * Returns a GraphQLite annotation reader.
     * Note: we cannot get the annotation reader service in the container as we are in a compiler pass.
     */
    private function getAnnotationReader(): AnnotationReader
    {
        return $this->annotationReader ??= new AnnotationReader();
    }

    private function getPsr16Cache(): CacheInterface
    {
        if ($this->cache === null) {
            if (ApcuAdapter::isSupported()) {
                $this->cache = new Psr16Cache(new ApcuAdapter('graphqlite_bundle'));
            } else {
                $this->cache = new Psr16Cache(new PhpFilesAdapter('graphqlite_bundle', 0, $this->cacheDir));
            }
        }

        return $this->cache;
    }

    private function getCodeCache(): ClassBoundCacheContractInterface
    {
        return $this->codeCache ??= new ClassBoundCacheContract(
            new ClassBoundMemoryAdapter(new ClassBoundCache(new FileBoundCache($this->getPsr16Cache())))
        );
    }

    /**
     * Returns the array of globbed classes.
     * Only instantiable classes are returned.
     *
     * @return Generator<class-string, ReflectionClass<object>, void, void>
     */
    private function getClassList(string $namespace): Generator
    {
        // dev note: this code will be broken if there's a broken class (which has mismatch in real namespace
        //  with PSR-4 configuration) in the configured namespace
        // see also: https://github.com/alekitto/class-finder/issues/24#issuecomment-2480847327
        $finder = new ComposerFinder();
        foreach ($finder->inNamespace($namespace) as $class) {
            assert($class instanceof ReflectionClass);
            yield $class->getName() => $class;
        }
    }

}
