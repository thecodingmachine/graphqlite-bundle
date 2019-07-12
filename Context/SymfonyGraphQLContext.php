<?php


namespace TheCodingMachine\Graphqlite\Bundle\Context;


use Symfony\Component\HttpFoundation\Request;

class SymfonyGraphQLContext implements SymfonyRequestContextInterface
{
    /**
     * @var Request
     */
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest(): Request
    {
        return $this->request;
    }
}
