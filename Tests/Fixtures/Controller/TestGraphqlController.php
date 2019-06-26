<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Controller;


use GraphQL\Error\Error;
use Porpaginas\Arrays\ArrayResult;
use TheCodingMachine\GraphQLite\Annotations\FailWith;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities\Contact;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities\Product;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

class TestGraphqlController
{

    /**
     * @Query()
     */
    public function test(string $foo): string
    {
        return 'echo ' .$foo;
    }

    /**
     * @Query()
     * @return Product[]
     */
    public function products(): array
    {
        return [
            new Product('Mouf', 9999)
        ];
    }

    /**
     * @Query()
     */
    public function contact(): Contact
    {
        return new Contact('Mouf');
    }

    /**
     * @Mutation()
     */
    public function saveProduct(Product $product): Product
    {
        return $product;
    }

    /**
     * @Query()
     * @return Contact[]
     */
    public function contacts(): ArrayResult
    {
        return new ArrayResult([new Contact('Mouf')]);
    }

    /**
     * @Query()
     * @return string
     */
    public function triggerException(int $code = 0): string
    {
        throw new MyException('Boom', $code);
    }

    /**
     * @Query()
     * @Logged()
     * @FailWith(null)
     * @return string
     */
    public function loggedQuery(): string
    {
        return 'foo';
    }
}
