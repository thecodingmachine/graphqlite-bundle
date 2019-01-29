<?php


namespace TheCodingMachine\Graphqlite\Bundle\Security;


use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;

class AuthenticationService implements AuthenticationServiceInterface
{
    /**
     * @var TokenStorageInterface|null
     */
    private $tokenStorage;

    public function __construct(?TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns true if the "current" user is logged
     *
     * @return bool
     */
    public function isLogged(): bool
    {
        if ($this->tokenStorage === null) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return false;
        }

        if (!\is_object($token->getUser())) {
            // e.g. anonymous authentication
            return false;
        }

        return true;
    }
}
