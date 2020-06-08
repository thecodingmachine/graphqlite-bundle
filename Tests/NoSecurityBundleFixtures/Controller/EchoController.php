<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\NoSecurityBundleFixtures\Controller;


use TheCodingMachine\GraphQLite\Annotations\Query;

class EchoController
{
    /**
     * @Query()
     */
    public function echoMsg(string $message): string {
        return $message;
    }
}
