<?php

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:intranet:seed-admin',
    description: 'Create or update the default intranet admin user.',
)]
final class SeedIntranetAdminCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('email', InputArgument::OPTIONAL, 'Admin email', 'admin@clouddev.local')
            ->addArgument('password', InputArgument::OPTIONAL, 'Admin password', 'admin123');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = strtolower(trim((string) $input->getArgument('email')));
        $password = trim((string) $input->getArgument('password'));

        if ($email === '' || $password === '') {
            $io->error('Email and password are required.');

            return Command::INVALID;
        }

        $this->connection->beginTransaction();

        try {
            $roleId = $this->connection->fetchOne('SELECT id FROM roles WHERE code = :code', [
                'code' => 'admin',
            ]);

            if ($roleId === false) {
                $this->connection->insert('roles', [
                    'code' => 'admin',
                    'label' => 'Administrator',
                ]);
                $roleId = $this->connection->lastInsertId();
            }

            $passwordHash = password_hash($password, PASSWORD_ARGON2ID);
            if (!is_string($passwordHash) || $passwordHash === '') {
                throw new \RuntimeException('Unable to hash password.');
            }

            $userId = $this->connection->fetchOne('SELECT id FROM users WHERE email = :email', [
                'email' => $email,
            ]);

            if ($userId === false) {
                $this->connection->insert('users', [
                    'role_id' => (int) $roleId,
                    'first_name' => 'Admin',
                    'last_name' => 'CloudDev',
                    'email' => $email,
                    'password_hash' => $passwordHash,
                    'is_active' => true,
                ]);
                $io->success(sprintf('Admin account created: %s', $email));
            } else {
                $this->connection->update(
                    'users',
                    [
                        'role_id' => (int) $roleId,
                        'password_hash' => $passwordHash,
                        'updated_at' => date('Y-m-d H:i:s'),
                    ],
                    ['id' => (int) $userId]
                );
                $io->success(sprintf('Admin account updated: %s', $email));
            }

            $this->connection->commit();

            return Command::SUCCESS;
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            $io->error(sprintf('Seed failed: %s', $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
