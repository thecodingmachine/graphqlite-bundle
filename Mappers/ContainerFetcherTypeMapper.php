<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\Mappers;

use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\NamedType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputType;
use GraphQL\Type\Definition\OutputType;
use Psr\Container\ContainerInterface;
use TheCodingMachine\GraphQL\Controllers\Mappers\CannotMapTypeException;
use TheCodingMachine\GraphQL\Controllers\Mappers\CannotMapTypeExceptionInterface;
use TheCodingMachine\GraphQL\Controllers\Mappers\RecursiveTypeMapperInterface;
use TheCodingMachine\GraphQL\Controllers\Mappers\TypeMapperInterface;
use TheCodingMachine\GraphQL\Controllers\Types\MutableObjectType;

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
     * An array mapping a Graphql type to a container entry resolving to a matching Type
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
     * Returns true if this type mapper can map the $className FQCN to a Graphql type.
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
     * @param string $className The exact class name to look for (this function does not look into parent classes).
     * @param OutputType|null $subType An optional sub-type if the main class is an iterator that needs to be typed.
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @return MutableObjectType
     * @throws CannotMapTypeExceptionInterface
     */
    public function mapClassToType(string $className, ?OutputType $subType, RecursiveTypeMapperInterface $recursiveTypeMapper): MutableObjectType
    {
        $key = $className;
        if ($subType instanceof NamedType && $subType !== null) {
            $key .= '____'.$subType->name;
        }
        if (isset($this->types[$key])) {
            return $this->container->get($this->types[$key]);
        }
        throw CannotMapTypeException::createForType($className);
    }

    /**
     * Returns the list of classes that have matching input Graphql types.
     *
     * @return string[]
     */
    public function getSupportedClasses(): array
    {
        return array_keys($this->types);
    }

    /**
     * Returns true if this type mapper can map the $className FQCN to a Graphql input type.
     *
     * @param string $className
     * @return bool
     */
    public function canMapClassToInputType(string $className): bool
    {
        return isset($this->inputTypes[$className]);
    }

    /**
     * Maps a PHP fully qualified class name to a Graphql input type.
     *
     * @param string $className
     * @return InputObjectType
     * @throws CannotMapTypeException
     */
    public function mapClassToInputType(string $className, RecursiveTypeMapperInterface $recursiveTypeMapper): InputObjectType
    {
        if (isset($this->inputTypes[$className])) {
            return $this->container->get($this->inputTypes[$className]);
        }
        throw CannotMapTypeException::createForInputType($className);
    }

    /**
     * Returns true if this type mapper can map the $typeName Graphql name to a Graphql type.
     *
     * @param string $typeName The name of the Graphql type
     * @return bool
     */
    public function canMapNameToType(string $typeName): bool
    {
        return isset($this->typesByName[$typeName]);
    }

    /**
     * Returns a Graphql type by name (can be either an input or output type)
     *
     * @param string $typeName The name of the Graphql type
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

    /**
     * Returns true if this type mapper can extend an existing type for the $className FQCN
     *
     * @param string $className
     * @param MutableObjectType $type
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @return bool
     */
    public function canExtendTypeForClass(string $className, MutableObjectType $type, RecursiveTypeMapperInterface $recursiveTypeMapper): bool
    {
        return false;
    }

    /**
     * Extends the existing GraphQL type that is mapped to $className.
     *
     * @param string $className
     * @param MutableObjectType $type
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @throws CannotMapTypeExceptionInterface
     */
    public function extendTypeForClass(string $className, MutableObjectType $type, RecursiveTypeMapperInterface $recursiveTypeMapper): void
    {
        throw CannotMapTypeException::createForType($className);
    }

    /**
     * Returns true if this type mapper can extend an existing type for the $typeName GraphQL type
     *
     * @param string $typeName
     * @param MutableObjectType $type
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @return bool
     */
    public function canExtendTypeForName(string $typeName, MutableObjectType $type, RecursiveTypeMapperInterface $recursiveTypeMapper): bool
    {
        return false;
    }

    /**
     * Extends the existing GraphQL type that is mapped to the $typeName GraphQL type.
     *
     * @param string $typeName
     * @param MutableObjectType $type
     * @param RecursiveTypeMapperInterface $recursiveTypeMapper
     * @throws CannotMapTypeExceptionInterface
     */
    public function extendTypeForName(string $typeName, MutableObjectType $type, RecursiveTypeMapperInterface $recursiveTypeMapper): void
    {
        throw CannotMapTypeException::createForName($typeName);
    }
}
