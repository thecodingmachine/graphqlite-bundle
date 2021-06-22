<?php


namespace TheCodingMachine\Graphqlite\Bundle\Types;

use Symfony\Component\Security\Core\Role\Role;
use TheCodingMachine\GraphQLite\Annotations\Field;
use TheCodingMachine\GraphQLite\Annotations\SourceField;
use TheCodingMachine\GraphQLite\Annotations\Type;
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * @Type(class=UserInterface::class)
 * @SourceField(name="userName")
 */
class SymfonyUserInterfaceType
{
    /**
     * @Field()
     * @return string[]
     */
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
