<?php


namespace TheCodingMachine\GraphQLite\Bundle\Types;

use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use Symfony\Component\Security\Core\User\UserInterface;

#[Type(class: UserInterface::class)]
class SymfonyUserInterfaceType
{
    #[Field]
    public function getUserName(UserInterface $user): string
    {
        return $user->getUserIdentifier();
    }

    /**
     * @return string[]
     */
    #[Field]
    public function getRoles(UserInterface $user): array
    {
        $roles = [];
        foreach ($user->getRoles() as $role) {
            $roles[] = $role;
        }

        return $roles;
    }
}
