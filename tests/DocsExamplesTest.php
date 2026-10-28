<?php

namespace TheCodingMachine\GraphQLite\Bundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\InMemoryUser;
use TheCodingMachine\GraphQLite\Bundle\Controller\GraphQLiteController;
use function file_get_contents;
use function glob;
use function json_decode;
use function json_encode;

/**
 * Keeps the examples of the docs/ folder honest:
 * - the GraphQL requests shown in the docs are executed against the classes of tests/DocsFixtures;
 * - every "code-block" preceded by a ".. fixture: <path>" comment must match that fixture file.
 */
class DocsExamplesTest extends TestCase
{
    private const DOCS_NAMESPACE = 'TheCodingMachine\\GraphQLite\\Bundle\\Tests\\DocsFixtures\\';

    public function testGettingStartedProductsQuery(): void
    {
        $result = $this->query($this->bootKernel(), '{
  products {
    id
    name
    priceInCents
  }
}');

        $this->assertSame([
            'data' => [
                'products' => [
                    ['id' => 1, 'name' => 'Mug', 'priceInCents' => 950],
                    ['id' => 2, 'name' => 'T-shirt', 'priceInCents' => 1990],
                ],
            ],
        ], $result);
    }

    public function testGettingStartedProductQuery(): void
    {
        $result = $this->query($this->bootKernel(), '{
  product(id: 2) {
    name
  }
}');

        $this->assertSame(['data' => ['product' => ['name' => 'T-shirt']]], $result);
    }

    public function testGettingStartedCreateProductMutation(): void
    {
        $result = $this->query($this->bootKernel(), 'mutation {
  createProduct(input: {name: "Hoodie", priceInCents: 3990}) {
    id
    name
  }
}');

        $this->assertSame(['data' => ['createProduct' => ['id' => 3, 'name' => 'Hoodie']]], $result);
    }

    public function testSecurityLogin(): void
    {
        $kernel = $this->bootKernel();
        $kernel->getContainer()->set('session', new Session(new MockArraySessionStorage()));

        $result = $this->query($kernel, 'mutation {
  login(userName: "foo", password: "bar") {
    userName
    roles
  }
}');

        $this->assertSame(['data' => ['login' => ['userName' => 'foo', 'roles' => ['ROLE_USER']]]], $result);
    }

    public function testSecurityMe(): void
    {
        $kernel = $this->bootKernel();
        $meQuery = '{
  me {
    userName
    roles
  }
}';

        $this->assertSame(['data' => ['me' => null]], $this->query($kernel, $meQuery));

        $this->logIn($kernel, ['ROLE_USER']);
        $this->assertSame(['data' => ['me' => ['userName' => 'foo', 'roles' => ['ROLE_USER']]]], $this->queryBypassingKernel($kernel, $meQuery));
    }

    public function testSecurityLogout(): void
    {
        $kernel = $this->bootKernel();
        $kernel->getContainer()->set('session', new Session(new MockArraySessionStorage()));

        $this->assertSame(['data' => ['logout' => true]], $this->query($kernel, 'mutation {
  logout
}'));
    }

    public function testSecurityDeleteProductRequiresLogin(): void
    {
        $result = $this->query($this->bootKernel(), 'mutation {
  deleteProduct(id: 1)
}');

        $this->assertSame('You need to be logged to access this field', $result['errors'][0]['message']);
    }

    public function testSecurityDeleteProductRequiresRight(): void
    {
        $kernel = $this->bootKernel();
        $mutation = 'mutation {
  deleteProduct(id: 1)
}';

        $this->logIn($kernel, ['ROLE_USER']);
        $result = $this->queryBypassingKernel($kernel, $mutation);
        $this->assertSame('You do not have sufficient rights to access this field', $result['errors'][0]['message']);

        $this->logIn($kernel, ['ROLE_ADMIN']);
        $this->assertSame(['data' => ['deleteProduct' => true]], $this->queryBypassingKernel($kernel, $mutation));
    }

    /**
     * @dataProvider provideSnippets
     */
    public function testSnippetMatchesFixture(string $rstFile, string $fixtureFile, string $snippet): void
    {
        $fixturePath = __DIR__.'/../'.$fixtureFile;
        $this->assertFileExists($fixturePath, sprintf('%s references a missing fixture.', $rstFile));

        $fixture = file_get_contents($fixturePath);
        $fixture = preg_replace('/^<\?php\s*/', '', $fixture);
        $fixture = str_replace(self::DOCS_NAMESPACE, 'App\\', $fixture);

        $this->assertSame(
            $this->normalize($fixture),
            $this->normalize($snippet),
            sprintf('The code-block of %s for %s is out of sync with the fixture.', $rstFile, $fixtureFile),
        );
    }

    /**
     * @return iterable<string, array{string, string, string}>
     */
    public static function provideSnippets(): iterable
    {
        foreach (glob(__DIR__.'/../docs/*.rst') as $rstPath) {
            $rstFile = 'docs/'.basename($rstPath);
            $lines = explode("\n", file_get_contents($rstPath));
            $count = count($lines);

            for ($i = 0; $i < $count; $i++) {
                if (!preg_match('/^\.\. fixture: (\S+)$/', $lines[$i], $matches)) {
                    continue;
                }

                // Find the code-block directive following the fixture comment.
                $j = $i + 1;
                while ($j < $count && trim($lines[$j]) === '') {
                    $j++;
                }
                if ($j >= $count || !preg_match('/^\.\. code-block:: php$/', $lines[$j])) {
                    throw new \LogicException(sprintf('%s:%d: ".. fixture:" must be followed by a ".. code-block:: php".', $rstFile, $i + 1));
                }

                // Collect the indented content of the code-block.
                $snippetLines = [];
                for ($j++; $j < $count; $j++) {
                    if (trim($lines[$j]) !== '' && !preg_match('/^\s/', $lines[$j])) {
                        break;
                    }
                    $snippetLines[] = $lines[$j];
                }

                yield $rstFile.' -> '.$matches[1] => [$rstFile, $matches[1], self::dedent($snippetLines)];
            }
        }
    }

    /**
     * @param list<string> $lines
     */
    private static function dedent(array $lines): string
    {
        $indent = null;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $lineIndent = strlen($line) - strlen(ltrim($line));
            $indent = $indent === null ? $lineIndent : min($indent, $lineIndent);
        }

        return implode("\n", array_map(static fn (string $line): string => (string) substr($line, (int) $indent), $lines));
    }

    private function normalize(string $code): string
    {
        return trim(preg_replace('/[ \t]+$/m', '', $code));
    }

    private function bootKernel(): KernelInterface
    {
        $kernel = new GraphQLiteTestingKernel(
            controllersNamespace: [self::DOCS_NAMESPACE.'GraphQL\\Controller\\'],
            typesNamespace: [self::DOCS_NAMESPACE.'Entity\\', self::DOCS_NAMESPACE.'GraphQL\\Input\\'],
        );
        $kernel->boot();

        return $kernel;
    }

    /**
     * Sends the query to /graphql as a JSON body, like the curl example of the docs.
     *
     * @return array<string, mixed>
     */
    private function query(KernelInterface $kernel, string $query): array
    {
        $request = Request::create('/graphql', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['query' => $query]));

        return json_decode($kernel->handle($request)->getContent(), true);
    }

    /**
     * Going through the kernel would reboot the container and drop the token set by logIn().
     *
     * @return array<string, mixed>
     */
    private function queryBypassingKernel(KernelInterface $kernel, string $query): array
    {
        $request = Request::create('/graphql', 'POST', [], [], [], ['CONTENT_TYPE' => 'application/json'], json_encode(['query' => $query]));
        $response = $kernel->getContainer()->get(GraphQLiteController::class)->handleRequest($request);

        return json_decode($response->getContent(), true);
    }

    /**
     * @param list<string> $roles
     */
    private function logIn(KernelInterface $kernel, array $roles): void
    {
        $token = new UsernamePasswordToken(new InMemoryUser('foo', 'bar', $roles), 'main', $roles);
        $kernel->getContainer()->get('security.untracked_token_storage')->setToken($token);
    }
}
