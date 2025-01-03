<?php


namespace TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Controller;


use Porpaginas\Arrays\ArrayResult;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use TheCodingMachine\GraphQLite\Annotations\FailWith;
use TheCodingMachine\GraphQLite\Annotations\Logged;
use TheCodingMachine\GraphQLite\Annotations\Right;
use TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Entities\Contact;
use TheCodingMachine\GraphQLite\Bundle\Tests\Fixtures\Entities\Product;
use TheCodingMachine\GraphQLite\Annotations\Mutation;
use TheCodingMachine\GraphQLite\Annotations\Query;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLAggregateException;
use TheCodingMachine\GraphQLite\Exceptions\GraphQLException;
use TheCodingMachine\GraphQLite\Validator\Annotations\Assertion;

class TestGraphqlController
{
    #[Query]
    public function test(string $foo): string
    {
        return 'echo ' .$foo;
    }

    /**
     * @return Product[]
     */
    #[Query]
    public function products(): array
    {
        return [
            new Product('Mouf', 9999)
        ];
    }

    #[Query]
    public function contact(): Contact
    {
        return new Contact('Mouf');
    }

    #[Mutation]
    public function saveProduct(Product $product): Product
    {
        return $product;
    }

    /**
     * @return Contact[]
     */
    #[Query]
    public function contacts(): ArrayResult
    {
        return new ArrayResult([new Contact('Mouf')]);
    }

    #[Query]
    public function triggerException(int $code = 0): string
    {
        throw new MyException('Boom', $code);
    }

    #[Query]
    public function triggerAggregateException(): string
    {
        $exception1 = new GraphQLException('foo', 401);
        $exception2 = new GraphQLException('bar', 404, null, ['field' => 'baz', 'category' => 'MyCat']);
        throw new GraphQLAggregateException([$exception1, $exception2]);
    }

    #[Query]
    #[Logged]
    #[FailWith(null)]
    public function loggedQuery(): string
    {
        return 'foo';
    }

    #[Query]
    #[Right('ROLE_ADMIN')]
    #[FailWith(null)]
    public function withAdminRight(): string
    {
        return 'foo';
    }

    #[Query]
    #[Right('ROLE_USER')]
    #[FailWith(null)]
    public function withUserRight(): string
    {
        return 'foo';
    }

    #[Query]
    public function getUri(Request $request): string
    {
        return $request->getPathInfo();
    }

    #[Query]
    public function findByMail(
        #[Assertion(constraint: new Assert\Email())]
        string $email = 'a@a.com'
    ): string {
        return $email;
    }
}
