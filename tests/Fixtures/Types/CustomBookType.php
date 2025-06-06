<?php

namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Types;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;

class CustomBookType extends ObjectType
{
    public function __construct()
    {
        parent::__construct([
            'name' => 'Book',
            'description' => 'A book',
            'fields' => [
                'title' => [
                    'type' => Type::nonNull(Type::string()),
                    'description' => 'Book title',
                ],
            ],
        ]);
    }

}