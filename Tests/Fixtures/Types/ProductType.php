<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Types;

use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities\Product;


/**
 * @Type(class=Product::class)
 * @SourceField(name="name")
 * @SourceField(name="price")
 */
class ProductType
{

}