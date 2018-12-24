<?php

namespace TheCodingMachine\GraphQL\Controllers\Bundle\Tests;

use PHPUnit\Framework\TestCase;
use TheCodingMachine\GraphQL\Controllers\Schema;

class FunctionalTest extends TestCase
{
    public function testServiceWiring()
    {
        $kernel = new GraphQLControllersTestingKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();



        $schema = $container->get(Schema::class);
        $this->assertInstanceOf(Schema::class, $schema);
        $schema->assertValid();
    }
}
