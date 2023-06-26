<?php

declare(strict_types=1);

namespace TheCodingMachine\GraphQLite\Bundle\Command;

use GraphQL\Type\Schema as TypeSchema;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TheCodingMachine\GraphQLite\Schema;

/**
 * Shamelessly stolen from Api Platform
 */
#[AsCommand('graphqlite:dump-schema')]
class DumpSchemaCommand extends Command
{
    /**
     * @var Schema
     */
    private $schema;

    public function __construct(Schema $schema)
    {
        $this->schema = $schema;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Export the GraphQL schema in Schema Definition Language (SDL)')
            ->addOption('output', 'o', InputOption::VALUE_REQUIRED, 'Write output to file');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $schemaExport = SchemaPrinterForGraphQLite::doPrint($this->schema, ['sortTypes' => true]);

        $filename = $input->getOption('output');
        if (\is_string($filename)) {
            file_put_contents($filename, $schemaExport);
            $io->success(sprintf('Data written to %s.', $filename));
        } else {
            $output->writeln($schemaExport);
        }

        return 0;
    }
}

class SchemaPrinterForGraphQLite extends SchemaPrinter {

    protected static function hasDefaultRootOperationTypes(TypeSchema $schema): bool
    {
        return $schema->getQueryType() === $schema->getType('Query')
            && $schema->getMutationType() === $schema->getType('Mutation')
            // Commenting this out because graphqlite cannot map Subscription type 
            // && $schema->getSubscriptionType() === $schema->getType('Subscription');
        ;
    }
}