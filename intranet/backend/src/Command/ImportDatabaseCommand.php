<?php

namespace App\Command;

use App\Service\DatabaseDumpImporter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpKernel\KernelInterface;

#[AsCommand(
    name: 'app:intranet:import-database',
    description: 'Import a PostgreSQL dump (pg_dump) into the configured DATABASE_URL database.',
)]
final class ImportDatabaseCommand extends Command
{
    public function __construct(
        private readonly DatabaseDumpImporter $importer,
        private readonly KernelInterface $kernel,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('file', null, InputOption::VALUE_REQUIRED, 'Path to .sql or .sql.gz dump');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $file = $input->getOption('file');

        try {
            $result = $this->importer->import(
                $this->kernel->getProjectDir(),
                \is_string($file) ? $file : null,
            );
        } catch (\Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('PostgreSQL dump imported successfully.');
        if ($result['output'] !== '') {
            $io->writeln($result['output']);
        }

        return Command::SUCCESS;
    }
}
