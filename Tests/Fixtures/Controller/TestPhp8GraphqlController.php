<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Controller;


use GraphQL\Error\Error;
use Porpaginas\Arrays\ArrayResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use TheCodingMachine\GraphQLite\Annotations\FailWith;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities\Contact;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities\Product;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLAggregateException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\Graphqlite\Validator\Annotations\Assertion;
use TheCodingMachine\Graphqlite\Validator\Fixtures\Types\User;

class TestPhp8GraphqlController
{
    #[Query]
    public function testPhp8(string $foo): string
    {
        return 'echo ' .$foo;
    }
}
