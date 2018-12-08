<?php


namespace TheCodingMachine\GraphQL\Controllers\Bundle\Security;


use Symfony\Component\Security\Csrf\TokenStorage\TokenStorageInterface;
use TheCodingMachine\GraphQL\Controllers\Security\AuthenticationServiceInterface;

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

        if (null === $token = $this->tokenStorage->getToken()) {
            return false;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return false;
        }

        return true;
    }
}
