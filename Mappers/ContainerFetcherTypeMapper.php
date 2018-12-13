<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\Mappers;

use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\OutputType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQL\Controllers\Mappers\CannotMapTypeException;
use TheCodingMachine\GraphQL\Controllers\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQL\Controllers\Mappers\TypeMapperInterface;

/**
 * A type mapper that fetches types from the container that is directly injected in the type mapper.
 * Useful in Symfony Bundle to provide better performance.
 */
final class ContainerFetcherTypeMapper implements TypeMapperInterface
{
    /**
     * An array mapping a fully qualified class name to a container entry resolving to a matching Type
     *
     * @var array<string,string> Key: class name, Value: container entry resolving to the output type.
     */
    private $types;
    /**
     * An array mapping a fully qualified class name to a container entry resolving to a matching Type
     *
     * @var array<string,string> Key: class name, Value: container entry resolving to the input type.
     */
    private $inputTypes;
    /**
     * An array mapping a GraphQL type to a container entry resolving to a matching Type
     *
     * @var array<string,string> Key: class name, Value: container entry resolving to the type.
     */
    private $typesByName;
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param array<string,string> $types Key: class name, Value: container entry resolving to the type.
     * @param array<string,string> $inputTypes Key: class name, Value: container entry resolving to the type.
     * @param array<string,string> $typesByName Key: type name, Value: container entry resolving to the type.
     */
    public function __construct(ContainerInterface $container, array $types, array $inputTypes, array $typesByName)
    {
        $this->container = $container;
        $this->types = $types;
        $this->inputTypes = $inputTypes;
        $this->typesByName = $typesByName;
    }

    /**
     * Returns true if this type mapper can map the $className FQCN to a GraphQL type.
     *
     * @param string $className
     * @return bool
     */
    public function canMapClassToType(string $className): bool
    {
        return isset($this->types[$className]);
    }

    /**
     * Maps a PHP fully qualified class name to a GraphQL type.
     *
     * @param string $className
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @return ObjectType
     * @throws CannotMapTypeException
     */
    public function mapClassToType(string $className, RecursiveTypeMapperInterface $recursiveTypeMapper): ObjectType
    {
        if (isset($this->types[$className])) {
            return $this->container->get($this->types[$className]);
        }
        throw CannotMapTypeException::createForType($className);
    }

    /**
     * Returns the list of classes that have matching input GraphQL types.
     *
     * @return string[]
     */
    public function getSupportedClasses(): array
    {
        return array_keys($this->types);
    }

    /**
     * Returns true if this type mapper can map the $className FQCN to a GraphQL input type.
     *
     * @param string $className
     * @return bool
     */
    public function canMapClassToInputType(string $className): bool
    {
        return isset($this->inputTypes[$className]);
    }

    /**
     * Maps a PHP fully qualified class name to a GraphQL input type.
     *
     * @param string $className
     * @return InputType
     * @throws CannotMapTypeException
     */
    public function mapClassToInputType(string $className): InputType
    {
        if (isset($this->inputTypes[$className])) {
            return $this->container->get($this->inputTypes[$className]);
        }
        throw CannotMapTypeException::createForInputType($className);
    }

    /**
     * Returns true if this type mapper can map the $typeName GraphQL name to a GraphQL type.
     *
     * @param string $typeName The name of the GraphQL type
     * @return bool
     */
    public function canMapNameToType(string $typeName): bool
    {
        return isset($this->typesByName[$typeName]);
    }

    /**
     * Returns a GraphQL type by name (can be either an input or output type)
     *
     * @param string $typeName The name of the GraphQL type
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @return Type&(InputType|OutputType)
     */
    public function mapNameToType(string $typeName, RecursiveTypeMapperInterface $recursiveTypeMapper): Type
    {
        if (isset($this->typesByName[$typeName])) {
            return $this->container->get($this->typesByName[$typeName]);
        }
        throw CannotMapTypeException::createForName($typeName);
    }
}
