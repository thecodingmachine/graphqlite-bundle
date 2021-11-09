<?php

namespace TheCodingMachine\GraphQLite\Bundle\Context;

use Symfony\Component\HttpFoundation\Request;

interface SymfonyRequestContextInterface
{
    /**
     * @return Request
     */
    public function getRequest(): Request;
}
