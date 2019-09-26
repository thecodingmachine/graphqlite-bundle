<?php


namespace TheCodingMachine\Graphqlite\Bundle\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use TheCodingMachine\GraphQLite\Security\AuthenticationServiceInterface;
use function is_object;

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
        return $this->getUser() !== null;
    }

    /**
     * Returns an object representing the current logged user.
     * Can return null if the user is not logged.
     */
    public function getUser(): ?object
    {
        if ($this->tokenStorage === null) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $user = $token->getUser();
        if (!\is_object($user)) {
            // e.g. anonymous authentication
            return null;
        }
        return $user;
    }
}
