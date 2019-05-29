<?php

namespace TheCodingMachine\Graphqlite\Bundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use TheCodingMachine\GraphQLite\Schema;

class FunctionalTest extends TestCase
{
    public function testServiceWiring()
    {
        $kernel = new GraphqliteTestingKernel('test', true);
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

    public function testErrors()
    {
        $kernel = new GraphqliteTestingKernel('test', true);
        $kernel->boot();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          notExists
        }']);

        $response = $kernel->handle($request);

        $this->assertSame(400, $response->getStatusCode());

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          triggerError
        }']);

        $response = $kernel->handle($request);

        $this->assertSame(500, $response->getStatusCode());

    }
}
