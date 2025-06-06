<?php

namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Entities;

class Book
{
    public function __construct(
        private readonly string $title
    )
    {
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}