<?php

namespace TheCodingMachine\Graphql\Controllers\Bundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use TheCodingMachine\GraphQL\Controllers\Schema;

class FunctionalTest extends TestCase
{
    public function testServiceWiring()
    {
        $kernel = new GraphqlControllersTestingKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        $schema = $container->get(Schema::class);
        $this->assertInstanceOf(Schema::class, $schema);
        $schema->assertValid();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          products 
          { 
            name,
            price 
          }
          
          contact {
            name,
            uppercaseName
          } 
          
          contacts {
            count
          }
        }']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'products' => [
                    [
                        'name' => 'Mouf',
                        'price' => 9999
                    ]
                ],
                'contact' => [
                    'name' => 'Mouf',
                    'uppercaseName' => 'MOUF'
                ],
                'contacts' => [
                    'count' => 1
                ]
            ]
        ], $result);
    }
}
