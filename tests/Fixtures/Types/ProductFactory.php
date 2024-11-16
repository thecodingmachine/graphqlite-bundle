<?php


namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Types;

use TheCodingMachine\GraphQLite\Annotations\Factory;
use TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Entities\Product;


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
