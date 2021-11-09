<?php
namespace TheCodingMachine\GraphQLite\Bundle\Controller\GraphQL;


use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use TheCodingMachine\GraphQLite\Annotations\Query;

class MeController
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(TokenStorageInterface $tokenStorage)
    {
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @Query()
     */
    public function me(): ?UserInterface
    {
        $token = $this->tokenStorage->getToken();
        if ($token === null) {
            return null;
        }

        $user = $token->getUser();

        if (!$user instanceof UserInterface) {
            // getUser() can be the "anon." string.
            return null;
        }

        return $user;
    }
}
