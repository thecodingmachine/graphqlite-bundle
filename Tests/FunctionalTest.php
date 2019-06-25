<?php

namespace TheCodingMachine\Graphqlite\Bundle\Tests;

use function json_decode;
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

    public function testServiceAutowiring()
    {
        $kernel = new GraphqliteTestingKernel('test', true);
        $kernel->boot();
        $container = $kernel->getContainer();

        $schema = $container->get(Schema::class);
        $this->assertInstanceOf(Schema::class, $schema);
        $schema->assertValid();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          contact {
            injectService
          } 
        }']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'contact' => [
                    'injectService' => 'OK',
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
          triggerException
        }']);

        $response = $kernel->handle($request);

        $this->assertSame(500, $response->getStatusCode());

        // Let's test that the highest exception code compatible with an HTTP is kept.
        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          triggerError1: triggerException(code: 404) 
          triggerError2: triggerException(code: 401)
          triggerError3: triggerException(code: 10245)
        }']);

        $response = $kernel->handle($request);

        $this->assertSame(404, $response->getStatusCode(), $response->getContent());
    }
}
