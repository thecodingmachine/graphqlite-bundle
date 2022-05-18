<?php

namespace TheCodingMachine\GraphQLite\Bundle\Tests;

use function json_decode;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;
use TheCodingMachine\GraphQLite\Bundle\Controller\GraphQLiteController;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException as GraphQLException;
use TheCodingMachine\GraphQLite\Schema;

class FunctionalTest extends TestCase
{
    public function testServiceWiring(): void
    {
        $kernel = new GraphQLiteTestingKernel();
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
        $kernel = new GraphQLiteTestingKernel();
        $kernel->boot();
        $container = $kernel->getContainer();

        $schema = $container->get(Schema::class);
        $this->assertInstanceOf(Schema::class, $schema);
        $schema->assertValid();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          contact {
            injectService
            injectServicePrefetch
          } 
        }']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'contact' => [
                    'injectService' => 'OK',
                    'injectServicePrefetch' => 'OK',
                ]
            ]
        ], $result);
    }

    public function testErrors(): void
    {
        $kernel = new GraphQLiteTestingKernel();
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

        $this->assertSame(400, $response->getStatusCode());

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

    public function testExceptionHandler(): void
    {
        $kernel = new GraphQLiteTestingKernel();
        $kernel->boot();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          triggerAggregateException
        }']);

        $response = $kernel->handle($request);

        $this->assertSame(404, $response->getStatusCode());

        $result = json_decode($response->getContent(), true);

        $this->assertSame('foo', $result['errors'][0]['message']);
        $this->assertSame('bar', $result['errors'][1]['message']);
        $this->assertSame('MyCat', $result['errors'][1]['extensions']['category']);
        $this->assertSame('baz', $result['errors'][1]['extensions']['field']);
    }

    public function testLoggedMiddleware(): void
    {
        $kernel = new GraphQLiteTestingKernel();
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
        $kernel = new GraphQLiteTestingKernel();
        $kernel->boot();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          loggedQuery
          withAdminRight
          withUserRight
        }']);

        $this->logIn($kernel->getContainer());

        // Test again, bypassing the kernel (cause this triggers a reboot of the container that disconnects the user)
        $response = $kernel->getContainer()->get(GraphQLiteController::class)->handleRequest($request);


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
        $kernel = new GraphQLiteTestingKernel();
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

    public function testLoginQuery(): void
    {
        $kernel = new GraphQLiteTestingKernel();
        $kernel->boot();

        $session = new Session(new MockArraySessionStorage());
        $container = $kernel->getContainer();
        $container->set('session', $session);

        $parameters = ['query' => '
            mutation login { 
              login(userName: "foo", password: "bar") {
                userName
                roles
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'login' => [
                    'userName' => 'foo',
                    'roles' => [
                        'ROLE_USER'
                    ]
                ]
            ]
        ], $result);
    }

    public function testMeQuery(): void
    {
        $kernel = new GraphQLiteTestingKernel();
        $kernel->boot();

        $session = new Session(new MockArraySessionStorage());
        $container = $kernel->getContainer();
        $container->set('session', $session);

        $parameters = ['query' => '
            {
              me {
                userName
                roles
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'me' => null
            ]
        ], $result);

    }

    public function testNoLoginNoSessionQuery(): void
    {
        $kernel = new GraphQLiteTestingKernel(false, 'off');
        $kernel->boot();

        $parameters = ['query' => '
            mutation login { 
              login(userName: "foo", password: "bar") {
                userName
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertArrayHasKey('errors', $result);
        $this->assertSame('Cannot query field "login" on type "Mutation".', $result['errors'][0]['message']);
    }

    public function testForceLoginNoSession(): void
    {
        $kernel = new GraphQLiteTestingKernel(false, 'on');
        $this->expectException(GraphQLException::class);
        $this->expectExceptionMessage('In order to enable the login/logout mutations (via the graphqlite.security.enable_login parameter), you need to enable session support (via the "framework.session.enabled" config parameter)');
        $kernel->boot();
    }

    public function testForceMeNoSecurity(): void
    {
        $kernel = new GraphQLiteTestingKernel(false, 'off', false, 'on');
        $this->expectException(GraphQLException::class);
        $this->expectExceptionMessage('In order to enable the "me" query (via the graphqlite.security.enable_me parameter), you need to install the security bundle.');
        $kernel->boot();
    }

    public function testForceLoginNoSecurity(): void
    {
        $kernel = new GraphQLiteTestingKernel(true, 'on', false);
        $this->expectException(GraphQLException::class);
        $this->expectExceptionMessage('In order to enable the login/logout mutations (via the graphqlite.security.enable_login parameter), you need to install the security bundle. Please be sure to correctly configure the user provider (in the security.providers configuration settings)');
        $kernel->boot();
    }

    /*public function testAutoMeNoSecurity(): void
    {
        $kernel = new GraphqliteTestingKernel(true, null, false);
        $kernel->boot();

        $session = new Session(new MockArraySessionStorage());
        $container = $kernel->getContainer();
        $container->set('session', $session);

        $request = Request::create('/graphql', 'POST', ['query' => '
        {
          me {
            userName
            roles
          }
        }
        ']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'me' => [
                    'userName' => 'anon.',
                    'roles' => [],
                ]
            ]
        ], $result);
    }*/

    public function testAllOff(): void
    {
        $kernel = new GraphQLiteTestingKernel(true, 'off', true, 'off');
        $kernel->boot();

        $session = new Session(new MockArraySessionStorage());
        $container = $kernel->getContainer();
        $container->set('session', $session);

        $parameters = ['query' => '
            {
              me {
                userName
                roles
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame('Cannot query field "me" on type "Query".', $result['errors'][0]['message']);
    }

    public function testValidation(): void
    {
        $kernel = new GraphQLiteTestingKernel(true, 'off', true, 'off');
        $kernel->boot();

        $session = new Session(new MockArraySessionStorage());
        $container = $kernel->getContainer();
        $container->set('session', $session);

        $parameters = ['query' => '
            {
              findByMail(email: "notvalid")
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);
        $errors = $result['errors'];

        $this->assertSame('This value is not a valid email address.', $errors[0]['message']);
        $this->assertSame('email', $errors[0]['extensions']['field']);
        $this->assertSame('Validate', $errors[0]['extensions']['category']);
    }

    public function testWithIntrospection(): void
    {
        $kernel = new GraphQLiteTestingKernel(true, null, true, null);
        $kernel->boot();

        $parameters = ['query' => '
            {
              __schema {
                queryType {
                  name
                }
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);
        $data = $result['data'];

        $this->assertArrayHasKey('__schema', $data);
    }

    public function testDisableIntrospection(): void
    {
        $kernel = new GraphQLiteTestingKernel(true, null, true, null, false, 2, 2);
        $kernel->boot();

        $parameters = ['query' => '
            {
              __schema {
                queryType {
                  name
                }
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);
        $errors = $result['errors'];

        $this->assertSame('GraphQL introspection is not allowed, but the query contained __schema or __type', $errors[0]['message']);
    }

    public function testMaxQueryComplexity(): void
    {
        $kernel = new GraphQLiteTestingKernel(true, null, true, null, false, 2, null);
        $kernel->boot();

        $parameters = ['query' => '
            { 
              products 
              { 
                name,
                price,
                seller {
                  name
                }
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);
        $errors = $result['errors'];

        $this->assertSame('Max query complexity should be 2 but got 5.', $errors[0]['message']);
    }

    public function testMaxQueryDepth(): void
    {
        $kernel = new GraphQLiteTestingKernel(true, null, true, null, false, null, 1);
        $kernel->boot();

        $parameters = ['query' => '
            { 
              products 
              { 
                name,
                price,
                seller {
                  name
                  manager {
                    name
                    manager {
                      name
                    }
                  }
                }
              }
            }
        '];

        $request = Request::create('/graphql', 'POST', $parameters, [], [], ['CONTENT_TYPE' => 'application/json']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);
        $errors = $result['errors'];

        $this->assertSame('Max query depth should be 1 but got 3.', $errors[0]['message']);
    }

    private function logIn(ContainerInterface $container)
    {
        // put a token into the storage so the final calls can function
        $user = new User('foo', 'pass');
        $token = new UsernamePasswordToken($user, '', 'provider', ['ROLE_USER']);
        $container->get('security.token_storage')->setToken($token);
    }

    /**
     * @requires PHP 8.0
     */
    public function testPhp8Attributes(): void
    {
        $kernel = new GraphQLiteTestingKernel();
        $kernel->boot();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          testPhp8(foo: "bar")
        }']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'testPhp8' => 'echo bar'
            ]
        ], $result);
    }
}
