<?php


namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Entities;


class Product
{
    /**
     * @var string
     */
    private $name;
    /**
     * @var float
     */
    private $price;

    public function __construct(string $name, float $price)
    {
        $this->name = $name;
        $this->price = $price;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return float
     */
    public function getPrice(): float
    {
        return $this->price;
    }
}
