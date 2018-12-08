<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\Hydrator;


use GraphQL\Type\Definition\InputType;
use TheCodingMachine\GraphQL\Controllers\HydratorInterface;

class VoidHydrator implements HydratorInterface
{

    /**
     * Hydrates/returns an object based on a PHP array and a GraphQL type.
     *
     * @param mixed[] $data
     * @param InputType $type
     * @return object
     */
    public function hydrate(array $data, InputType $type)
    {
        throw new \RuntimeException('The VoidHydrator cannot hydrate anything.');
    }
}
