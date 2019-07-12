<?php

namespace TheCodingMachine\Graphqlite\Bundle\Tests;

use function json_decode;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use function spl_object_hash;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;
use TheCodingMachine\Graphqlite\Bundle\Controller\GraphqliteController;
use TheCodingMachine\Graphqlite\Bundle\Security\AuthenticationService;
use TheCodingMachine\GraphQLite\Schema;
use function var_dump;

class FunctionalTest extends TestCase
{
    public function testServiceWiring(): void
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

    public function testServiceAutowiring(): void
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

    public function testErrors(): void
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

    public function testLoggedMiddleware(): void
    {
        $kernel = new GraphqliteTestingKernel('test', true);
        $kernel->boot();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          loggedQuery
        }']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'loggedQuery' => null
            ]
        ], $result);
    }

    public function testLoggedMiddleware2(): void
    {
        $kernel = new GraphqliteTestingKernel('test', true);
        $kernel->boot();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          loggedQuery
          withAdminRight
          withUserRight
        }']);

        $this->logIn($kernel->getContainer());

        // Test again, bypassing the kernel (cause this triggers a reboot of the container that disconnects the user)
        $response = $kernel->getContainer()->get(GraphqliteController::class)->handleRequest($request);


        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'loggedQuery' => 'foo',
                'withAdminRight' => null,
                'withUserRight' => 'foo',
            ]
        ], $result);

    }

    public function testInjectQuery(): void
    {
        $kernel = new GraphqliteTestingKernel('test', true);
        $kernel->boot();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          uri
        }']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'uri' => '/graphql'
            ]
        ], $result);
    }

    private function logIn(ContainerInterface $container)
    {
        // put a token into the storage so the final calls can function
        $user = new User('foo', 'pass');
        $token = new UsernamePasswordToken($user, '', 'provider', ['ROLE_USER']);
        $container->get('security.token_storage')->setToken($token);
    }
}
