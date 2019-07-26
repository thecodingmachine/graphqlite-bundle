<?php

namespace TheCodingMachine\Graphqlite\Bundle\Types;

use PHPUnit\Framework\TestCase;
use RuntimeException;

class BasicUserTest extends TestCase
{

    public function testGetPassword()
    {
        $user = new BasicUser('foo');

        $this->expectException(RuntimeException::class);
        $user->getPassword();
    }

    public function testEraseCredentials()
    {
        $user = new BasicUser('foo');

        $this->expectException(RuntimeException::class);
        $user->eraseCredentials();
    }

    public function testGetSalt()
    {
        $user = new BasicUser('foo');

        $this->expectException(RuntimeException::class);
        $user->getSalt();
    }
}
