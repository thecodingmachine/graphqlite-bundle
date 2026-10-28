<?php

// src/GraphQL/Controller/ProductController.php
namespace TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\GraphQL\Controller;

use TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\Entity\Product;
use TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\GraphQL\Input\CreateProductInput;
use TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\Repository\ProductRepository;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;

final class ProductController
{
    public function __construct(
        private readonly ProductRepository $productRepository,
    ) {
    }

    /**
     * @return list<Product>
     */
    #[Query]
    public function products(): array
    {
        return $this->productRepository->findAll();
    }

    #[Query]
    public function product(int $id): ?Product
    {
        return $this->productRepository->find($id);
    }

    #[Mutation]
    public function createProduct(CreateProductInput $input): Product
    {
        return $this->productRepository->add($input->name, $input->priceInCents);
    }
}
