<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Types;

use TheCodingMachine\GraphQL\Controllers\Annotations\SourceField;
use TheCodingMachine\GraphQL\Controllers\Annotations\Type;
use TheCodingMachine\Graphql\Controllers\Bundle\Tests\Fixtures\Entities\Product;


/**
 * @Type(class=Product::class)
 * @SourceField(name="name")
 * @SourceField(name="price")
 */
class ProductType
{

}