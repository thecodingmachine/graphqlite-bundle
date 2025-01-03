<?php


namespace TheCodingMachine\GraphQLite\Bundle\Types;

use Symfony\Component\Security\Core\Role\Role;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\Type;
use Symfony\Component\Security\Core\User\UserInterface;
use TheCodingMachine\GraphQLite\FieldNotFoundException;

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
