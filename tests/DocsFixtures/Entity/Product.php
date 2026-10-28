<?php

// src/Entity/Product.php
namespace TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\Entity;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;

#[Type]
class Product
{
    public function __construct(
        #[Field]
        public readonly int $id,
        #[Field]
        public readonly string $name,
        #[Field]
        public readonly int $priceInCents,
    ) {
    }
}
