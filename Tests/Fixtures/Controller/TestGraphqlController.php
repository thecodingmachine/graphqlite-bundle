<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Controller;


use Porpaginas\Arrays\ArrayResult;
use TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Entities\Contact;
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
}
