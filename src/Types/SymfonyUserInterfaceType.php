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
        // @phpstan-ignore-next-line Forward Compatibility for Symfony >=5.3
        if (method_exists($user, 'getUserIdentifier')) {
            return $user->getUserIdentifier();
        }

        // @phpstan-ignore-next-line Backward Compatibility for Symfony <5.3
        if (method_exists($user, 'getUsername')) {
            return $user->getUsername();
        }

        throw FieldNotFoundException::missingField(UserInterface::class, 'userName');
    }

    /**
     * @return string[]
     */
    #[Field]
    public function getRoles(UserInterface $user): array
    {
        $roles = [];
        foreach ($user->getRoles() as $role) {
            // @phpstan-ignore-next-line BC for Symfony 4
            if (class_exists(Role::class) && $role instanceof Role) {
                $role = $role->getRole();
            }

            $roles[] = $role;
        }
        return $roles;
    }
}
