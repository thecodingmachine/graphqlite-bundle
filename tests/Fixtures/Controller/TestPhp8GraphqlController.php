<?php


namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Controller;


use TheCodingMachine\GraphQLite\Annotations\Query;

class TestPhp8GraphqlController
{
    #[Query]
    public function testPhp8(string $foo): string
    {
        return 'echo ' .$foo;
    }
}
