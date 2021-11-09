<?php


namespace TheCodingMachine\GraphQLite\Bundle\Mappers;


use GraphQL\Type\Definition\ResolveInfo;
use TheCodingMachine\GraphQLite\Bundle\Context\SymfonyRequestContextInterface;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException as GraphQLException;
use TheCodingMachine\GraphQLite\Parameters\ParameterInterface;

class RequestParameter implements ParameterInterface
{

    /**
     * @param array<string, mixed> $args
     * @param mixed $context
     *
     * @return mixed
     */
    public function resolve(?object $source, array $args, $context, ResolveInfo $info)
    {
        if (!$context instanceof SymfonyRequestContextInterface) {
            throw new GraphQLException('Cannot type-hint on a Symfony Request object in your query/mutation/field. The request context must implement SymfonyRequestContextInterface.');
        }
        return $context->getRequest();
    }
}
