<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Controller;


use GraphQL\Error\Error;
use Porpaginas\Arrays\ArrayResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assertion;
use TheCodingMachine\GraphQLite\Annotations\FailWith;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities\Contact;
use TheCodingMachine\Graphqlite\Bundle\Tests\Fixtures\Entities\Product;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLAggregateException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\Graphqlite\Validator\Annotations\Assert;
use TheCodingMachine\Graphqlite\Validator\Fixtures\Types\User;

class TestGraphqlController
{

    /**
     * @Query()
     */
    public function test(string $foo): string
    {
        return 'echo ' .$foo;
    }

    /**
     * @Query()
     * @return Product[]
     */
    public function products(): array
    {
        return [
            new Product('Mouf', 9999)
        ];
    }

    /**
     * @Query()
     */
    public function contact(): Contact
    {
        return new Contact('Mouf');
    }

    /**
     * @Mutation()
     */
    public function saveProduct(Product $product): Product
    {
        return $product;
    }

    /**
     * @Query()
     * @return Contact[]
     */
    public function contacts(): ArrayResult
    {
        return new ArrayResult([new Contact('Mouf')]);
    }

    /**
     * @Query()
     * @return string
     */
    public function triggerException(int $code = 0): string
    {
        throw new MyException('Boom', $code);
    }

    /**
     * @Query()
     * @return string
     */
    public function triggerAggregateException(): string
    {
        $exception1 = new GraphQLException('foo', 401);
        $exception2 = new GraphQLException('bar', 404, null, 'MyCat', ['field' => 'baz']);
        throw new GraphQLAggregateException([$exception1, $exception2]);
    }

    /**
     * @Query()
     * @Logged()
     * @FailWith(null)
     * @return string
     */
    public function loggedQuery(): string
    {
        return 'foo';
    }

    /**
     * @Query()
     * @Right("ROLE_ADMIN")
     * @FailWith(null)
     * @return string
     */
    public function withAdminRight(): string
    {
        return 'foo';
    }

    /**
     * @Query()
     * @Right("ROLE_USER")
     * @FailWith(null)
     * @return string
     */
    public function withUserRight(): string
    {
        return 'foo';
    }

    /**
     * @Query()
     * @return string
     */
    public function getUri(Request $request): string
    {
        return $request->getPathInfo();
    }

    /**
     * @Query
     * @Assert(for="email", constraint=@Assertion\Email())
     */
    public function findByMail(string $email = 'a@a.com'): string
    {
        return $email;
    }
}
