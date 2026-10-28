<?php

// src/GraphQL/Controller/AdminProductController.php
namespace TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\GraphQL\Controller;

use TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\Repository\ProductRepository;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Right;

final class AdminProductController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    #[Mutation]
    #[Logged]
    #[Right('ROLE_ADMIN')]
    public function deleteProduct(int $id): bool
    {
        return $this->productRepository->remove($id);
    }
}
