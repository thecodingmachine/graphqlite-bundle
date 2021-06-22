<?php

declare(strict_types=1);

namespace TheCodingMachine\Graphqlite\Bundle\Command;

use GraphQL\Type\Definition\ObjectType;
use GraphQL\Utils\SchemaPrinter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TheCodingMachine\GraphQLite\Schema;

/**
 * Shamelessly stolen from Api Platform
 */
class DumpSchemaCommand extends Command
{
    protected static $defaultName = 'graphqlite:dump-schema';

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

        // Trying to guarantee deterministic order
        $this->sortSchema();

        $schemaExport = SchemaPrinter::doPrint($this->schema);

        $filename = $input->getOption('output');
        if (\is_string($filename)) {
            file_put_contents($filename, $schemaExport);
            $io->success(sprintf('Data written to %s.', $filename));
        } else {
            $output->writeln($schemaExport);
        }

        return 0;
    }

    private function sortSchema(): void
    {
        $config = $this->schema->getConfig();

        $refl = new \ReflectionProperty(ObjectType::class, 'fields');
        $refl->setAccessible(true);

        $fields = $config->query->getFields();
        ksort($fields);
        $refl->setValue($config->query, $fields);

        $fields = $config->mutation->getFields();
        ksort($fields);
        $refl->setValue($config->mutation, $fields);
    }
}
