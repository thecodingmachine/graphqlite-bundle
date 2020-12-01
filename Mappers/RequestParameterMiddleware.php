<?php


namespace TheCodingMachine\Graphqlite\Bundle\Mappers;


use phpDocumentor\Reflection\DocBlock;
use phpDocumentor\Reflection\Type;
use ReflectionParameter;
use Symfony\Component\HttpFoundation\Request;
use TheCodingMachine\GraphQLite\Annotations\ParameterAnnotations;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterHandlerInterface;
use TheCodingMachine\GraphQLite\Mappers\Parameters\ParameterMiddlewareInterface;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class RequestParameterMiddleware implements ParameterMiddlewareInterface
{

    public function mapParameter(ReflectionParameter $parameter, DocBlock $docBlock, ?Type $paramTagType, ParameterAnnotations $parameterAnnotations, ParameterHandlerInterface $next): ParameterInterface
    {
        $parameterType = $parameter->getType();
        if ($parameterType && $parameterType->getName() === Request::class) {
            return new RequestParameter();
        }
        return $next->mapParameter($parameter, $docBlock, $paramTagType, $parameterAnnotations);
    }
}
