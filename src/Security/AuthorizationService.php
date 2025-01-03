<?php


namespace TheCodingMachine\GraphQLite\Bundle\Security;


use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use TheCodingMachine\GraphQLite\Security\AuthorizationServiceInterface;

class AuthorizationService implements AuthorizationServiceInterface
{
    /**
     * @var AuthorizationCheckerInterface|null
     */
    private $authorizationChecker;
    /**
     * @var TokenStorageInterface|null
     */
    private $tokenStorage;

    public function __construct(?AuthorizationCheckerInterface $authorizationChecker, ?TokenStorageInterface $tokenStorage)
    {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * Returns true if the "current" user has access to the right "$right"
     *
     * @param mixed $subject The scope this right applies on. $subject is typically an object or a FQCN. Set $subject to "null" if the right is global.
     */
    public function isAllowed(string $right, $subject = null): bool
    {
        if ($this->authorizationChecker === null || $this->tokenStorage === null) {
            throw new \LogicException('The SecurityBundle is not registered in your application. Try running "composer require symfony/security-bundle".');
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return false;
        }

        return $this->authorizationChecker->isGranted($right, $subject);
    }
}
