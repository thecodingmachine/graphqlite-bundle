<?php


namespace TheCodingMachine\Graphqlite\Bundle\Mappers;


use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMapperInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class RequestParameterMapper implements ParameterMapperInterface
{

    public function mapParameter(ReflectionParameter $parameter, DocBlock $docBlock, ?Type $paramTagType, ParameterAnnotations $parameterAnnotations): ?ParameterInterface
    {
        if ($parameter->getType()->getName() === Request::class) {
            return new RequestParameter();
        }
        return null;
    }
}