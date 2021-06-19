<?php


namespace TheCodingMachine\Graphqlite\Bundle\Tests\Command;


use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use TheCodingMachine\Graphqlite\Bundle\Tests\GraphqliteTestingKernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;

class DumpSchemaCommandTest extends TestCase
{
    public function testExecute()
    {
        $kernel = new GraphqliteTestingKernel();
        $application = new Application($kernel);

        $command = $application->find('graphqlite:dump-schema');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        $this->assertRegExp(
            '/type Product {[\s"]*seller: Contact\s*name: String!\s*price: Float!\s*}/',
            $commandTester->getDisplay()
        );
    }
}
