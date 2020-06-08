<?php

namespace TheCodingMachine\Graphqlite\Bundle\Tests;

use function json_decode;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use function spl_object_hash;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\User;
use TheCodingMachine\Graphqlite\Bundle\Controller\GraphqliteController;
use TheCodingMachine\Graphqlite\Bundle\Security\AuthenticationService;
use TheCodingMachine\GraphQLite\GraphQLRuntimeException as GraphQLException;
use TheCodingMachine\GraphQLite\Schema;
use function var_dump;

/**
 * This test class is supposed to work even if the security bundle is not installed in the project.
 * It is here to check we don't have hidden dependencies on this bundle and that it remains optional.
 */
class NoSecurityBundleTest extends TestCase
{
    public function testServiceWiring(): void
    {
        $kernel = new GraphqliteTestingKernel(true, null, false, null, true, null, null, ['TheCodingMachine\\Graphqlite\\Bundle\\Tests\\NoSecurityBundleFixtures\\Controller\\']);
        $kernel->boot();
        $container = $kernel->getContainer();

        $schema = $container->get(Schema::class);
        $this->assertInstanceOf(Schema::class, $schema);
        $schema->assertValid();

        $request = Request::create('/graphql', 'GET', ['query' => '
        { 
          echoMsg(message: "Hello world")
        }']);

        $response = $kernel->handle($request);

        $result = json_decode($response->getContent(), true);

        $this->assertSame([
            'data' => [
                'echoMsg' => 'Hello world'
            ]
        ], $result);
    }
}
