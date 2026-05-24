<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Support;

/**
 * Driver-aware DSN composer.
 *
 * Pulled out of {@see \InitORM\DBAL\Connection\Connection} so the DSN-building
 * algorithm can be unit-tested in isolation, and so adding a new driver
 * later only requires a change in one place.
 */
final class DsnBuilder
{
    /**
     * @param array{driver?: string, host?: string, port?: int|string, database?: string, charset?: string} $credentials
     */
    public function build(array $credentials): string
    {
        $driver = $credentials['driver'] ?? 'mysql';

        switch ($driver) {
            case 'sqlite':
                return 'sqlite:' . ($credentials['database'] ?? ':memory:');

            case 'pgsql':
                return $this->buildPgsql($credentials);

            default:
                return $this->buildMysqlLike($driver, $credentials);
        }
    }

    /**
     * @param array{host?: string, port?: int|string, database?: string, charset?: string} $c
     */
    private function buildMysqlLike(string $driver, array $c): string
    {
        $parts = [
            'host=' . ($c['host'] ?? '127.0.0.1'),
            'port=' . ($c['port'] ?? 3306),
        ];
        if (!empty($c['database'])) {
            $parts[] = 'dbname=' . $c['database'];
        }
        if (!empty($c['charset'])) {
            $parts[] = 'charset=' . $c['charset'];
        }

        return $driver . ':' . implode(';', $parts);
    }

    /**
     * @param array{host?: string, port?: int|string, database?: string} $c
     */
    private function buildPgsql(array $c): string
    {
        $parts = [
            'host=' . ($c['host'] ?? '127.0.0.1'),
            'port=' . ($c['port'] ?? 5432),
        ];
        if (!empty($c['database'])) {
            $parts[] = 'dbname=' . $c['database'];
        }

        return 'pgsql:' . implode(';', $parts);
    }
}
