<?php

namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Entities;

use stdClass;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Controller\TestGraphqlController;
use TheCodingMachine\GraphQLite\Annotations\Autowire;

#[Type]
class Contact
{
    /**
     * @var string
     */
    private $name;

    public function __construct(string $name)
    {
        $this->name = $name;
    }

    #[Field(name: 'name')]
    public function getName(): string
    {
        return $this->name;
    }

    #[Field]
    public function injectService(
        #[Autowire]
        TestGraphqlController $testService = null,
        #[Autowire(identifier: 'someService')]
        stdClass $someService = null,
        #[Autowire(identifier: 'someAlias')]
        stdClass $someAlias = null,
    ): string {
        if (!$testService instanceof TestGraphqlController || $someService === null || $someAlias === null) {
            return 'KO';
        }
        return 'OK';
    }

    #[Field(prefetchMethod: 'prefetchData')]
    public function injectServicePrefetch($prefetchData): string
    {
        return $prefetchData;
    }

    public function prefetchData(
        iterable $iterable,
        #[Autowire(identifier: 'someOtherService')]
        stdClass $someOtherService = null,
    ) {
        if ($someOtherService === null) {
            return 'KO';
        }
        return 'OK';
    }

    #[Field]
    public function getManager(): ?Contact
    {
        return null;
    }
}
