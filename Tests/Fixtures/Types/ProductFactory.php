<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Types;

use TheCodingMachine\GraphQL\Controllers\Annotations\Factory;
use TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Entities\Product;


class ProductFactory
{

    /**
     * @Factory()
     */
    public function buildProduct(string $name, float $price): Product
    {
        return new Product($name, $price);
    }
}
