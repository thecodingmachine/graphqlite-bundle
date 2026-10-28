<?php

// src/Repository/ProductRepository.php
namespace TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\Repository;

use TheCodingMachine\GraphQLite\Bundle\Tests\DocsFixtures\Entity\Product;

final class ProductRepository
{
    /** @var array<int, Product> */
    private array $products = [];

    private int $nextId = 1;

    public function __construct()
    {
        $this->add('Mug', 950);
        $this->add('T-shirt', 1990);
    }

    /** @return list<Product> */
    public function findAll(): array
    {
        return array_values($this->products);
    }

    public function find(int $id): ?Product
    {
        return $this->products[$id] ?? null;
    }

    public function add(string $name, int $priceInCents): Product
    {
        $product = new Product($this->nextId++, $name, $priceInCents);

        return $this->products[$product->id] = $product;
    }

    public function remove(int $id): bool
    {
        if (!isset($this->products[$id])) {
            return false;
        }
        unset($this->products[$id]);

        return true;
    }
}
