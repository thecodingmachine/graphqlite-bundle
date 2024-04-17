<?php

namespace TheCodingMachine\GraphQLite\Bundle\Exceptions;

use Exception;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;

class JsonException extends GraphQLException
{
    public static function create(?string $reason = null, int $code = 400, Exception $previous = null): self
    {
        return new self(
            message: 'Invalid JSON.',
            code: $code,
            previous: $previous,
            extensions: ['reason' => $reason]
        );
    }
}
