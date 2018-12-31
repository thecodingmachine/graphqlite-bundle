<?php


namespace TheCodingMachine\Graphql\Controllers\Bundle\QueryProviders;


use TheCodingMachine\GraphQL\Controllers\FieldsBuilder;
use TheCodingMachine\GraphQL\Controllers\QueryField;
use TheCodingMachine\GraphQL\Controllers\QueryProviderInterface;

class ControllerQueryProvider implements QueryProviderInterface
{
    /**
     * @var object
     */
    private $controller;
    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @param object $controller
     * @param FieldsBuilder $fieldsBuilder
     */
    public function __construct($controller, FieldsBuilder $fieldsBuilder)
    {
        $this->controller = $controller;
        $this->fieldsBuilder = $fieldsBuilder;
    }

    /**
     * @return QueryField[]
     */
    public function getQueries(): array
    {
        return $this->fieldsBuilder->getQueries($this->controller);
    }

    /**
     * @return QueryField[]
     */
    public function getMutations(): array
    {
        return $this->fieldsBuilder->getMutations($this->controller);
    }
}
