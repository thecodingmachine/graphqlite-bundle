<?php


namespace TheCodingMachine\Graphqlite\Bundle\Controller\GraphQL;


use Exception;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException as GraphQLException;

class InvalidUserPasswordException extends GraphQLException
{
    public static function create(Exception $previous = null)
    {
        return new self('The provided user / password is incorrect.', 401, $previous);
    }
}