<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Controller;


use TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Entities\Product;
use TheCodingMachine\GraphQL\Controllers\Annotations\Mutation;
use TheCodingMachine\GraphQL\Controllers\Annotations\Query;

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
     * @Mutation()
     */
    public function saveProduct(Product $product): Product
    {
        return $product;
    }

}
