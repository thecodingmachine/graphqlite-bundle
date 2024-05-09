<?php


namespace TheCodingMachine\GraphQLite\Bundle\Controller\GraphQL;

use Exception;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class InvalidUserPasswordException extends GraphQLException
{
    public static function create(Exception $previous = null): self
    {
        return new self('The provided user / password is incorrect.', 401, $previous);
    }
}
