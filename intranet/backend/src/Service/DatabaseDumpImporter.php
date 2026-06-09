<?php

namespace App\Service;

final class DatabaseDumpImporter
{
    /**
     * @return array{ok: bool, output: string}
     */
    public function import(string $projectDir, ?string $file = null): array
    {
        $dump = $file ?? $projectDir.'/var/intranet_db_dump.sql.gz';
        if (!is_file($dump)) {
            $sqlDump = $projectDir.'/var/intranet_db_dump.sql';
            $dump = is_file($sqlDump) ? $sqlDump : $dump;
        }

        if (!is_file($dump)) {
            throw new \RuntimeException(sprintf('Dump file not found: %s', $dump));
        }

        $databaseUrl = (string) ($_ENV['DATABASE_URL'] ?? $_SERVER['DATABASE_URL'] ?? '');
        if ($databaseUrl === '') {
            throw new \RuntimeException('DATABASE_URL is not configured.');
        }

        $params = $this->parseDatabaseUrl($databaseUrl);
        $psql = $this->findPsqlBinary();

        $env = $_ENV;
        $env['PGPASSWORD'] = $params['password'];

        $command = match (true) {
            str_ends_with($dump, '.gz') => sprintf(
                'gunzip -c %s | %s -h %s -p %s -U %s -d %s -v ON_ERROR_STOP=1',
                escapeshellarg($dump),
                escapeshellarg($psql),
                escapeshellarg($params['host']),
                escapeshellarg((string) $params['port']),
                escapeshellarg($params['user']),
                escapeshellarg($params['dbname']),
            ),
            default => sprintf(
                '%s -h %s -p %s -U %s -d %s -v ON_ERROR_STOP=1 -f %s',
                escapeshellarg($psql),
                escapeshellarg($params['host']),
                escapeshellarg((string) $params['port']),
                escapeshellarg($params['user']),
                escapeshellarg($params['dbname']),
                escapeshellarg($dump),
            ),
        };

        $descriptor = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptor, $pipes, $projectDir, $env);
        if (!\is_resource($process)) {
            throw new \RuntimeException('Could not start psql import process.');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]) ?: '';
        $stderr = stream_get_contents($pipes[2]) ?: '';
        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);
        $output = trim($stdout."\n".$stderr);

        if ($exitCode !== 0) {
            throw new \RuntimeException(sprintf("Database import failed (exit %d):\n%s", $exitCode, $output));
        }

        return ['ok' => true, 'output' => $output];
    }

    /**
     * @return array{user: string, password: string, host: string, port: int, dbname: string}
     */
    private function parseDatabaseUrl(string $databaseUrl): array
    {
        $parts = parse_url($databaseUrl);
        if (!\is_array($parts) || ($parts['scheme'] ?? '') !== 'postgresql') {
            throw new \RuntimeException('Unsupported DATABASE_URL format.');
        }

        $dbname = ltrim((string) ($parts['path'] ?? ''), '/');
        if ($dbname === '') {
            throw new \RuntimeException('DATABASE_URL has no database name.');
        }

        return [
            'user' => urldecode((string) ($parts['user'] ?? '')),
            'password' => urldecode((string) ($parts['pass'] ?? '')),
            'host' => (string) ($parts['host'] ?? '127.0.0.1'),
            'port' => (int) ($parts['port'] ?? 5432),
            'dbname' => $dbname,
        ];
    }

    private function findPsqlBinary(): string
    {
        $path = trim((string) shell_exec('command -v psql 2>/dev/null'));
        if ($path !== '' && is_executable($path)) {
            return $path;
        }

        foreach (['/usr/bin/psql', '/usr/local/bin/psql'] as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        throw new \RuntimeException('psql binary not found on server.');
    }
}
