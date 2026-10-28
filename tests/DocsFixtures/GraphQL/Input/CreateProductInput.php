<?php

// src/GraphQL/Input/CreateProductInput.php
namespace TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\GraphQL\Input;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Input;

#[Input]
final class CreateProductInput
{
    public function __construct(
        #[Field]
        public readonly string $name,
        #[Field]
        public readonly int $priceInCents,
    ) {
    }
}
