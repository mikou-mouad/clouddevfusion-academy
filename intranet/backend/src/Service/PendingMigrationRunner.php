<?php

namespace App\Service;

use App\Kernel;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

final class PendingMigrationRunner
{
    public function runIfPending(string $projectDir): void
    {
        $flag = $projectDir.'/var/migrate.pending';
        if (!is_file($flag)) {
            return;
        }

        unlink($flag);

        if (!is_file($projectDir.'/vendor/autoload.php')) {
            touch($flag);

            return;
        }

        $kernel = new Kernel(
            $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod',
            (bool) ($_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? false),
        );
        $kernel->boot();

        $application = new Application($kernel);
        $application->setAutoExit(false);

        $exitCode = $application->run(
            new ArrayInput([
                'command' => 'doctrine:migrations:migrate',
                '--no-interaction' => true,
                '--allow-no-migration' => true,
            ]),
            new NullOutput(),
        );

        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf('Doctrine migrations failed with exit code %d', $exitCode));
        }
    }
}
