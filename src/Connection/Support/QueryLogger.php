<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Support;

/**
 * In-memory query log buffer.
 *
 * Separated from `Connection` so the storage strategy can be swapped or
 * tested in isolation. Entries are only appended when the buffer is enabled
 * via {@see self::enable()} (mirrors `setQueryLogs(true)` on the connection).
 *
 * @phpstan-type QueryLogEntry array{query: string, args: array<string, mixed>|null, timer: float}
 */
final class QueryLogger
{
    /** @var array<int, QueryLogEntry> */
    private array $logs = [];

    private bool $enabled;

    public function __construct(bool $enabled = false)
    {
        $this->enabled = $enabled;
    }

    public function enable(bool $enabled = true): void
    {
        $this->enabled = $enabled;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * @param array<string, mixed>|null $args
     */
    public function add(string $query, ?array $args = null, ?float $startTime = null): void
    {
        if (!$this->enabled) {
            return;
        }

        $timer = $startTime !== null
            ? round(microtime(true) - $startTime, 6)
            : 0.0;

        $this->logs[] = [
            'query' => $query,
            'args'  => $args,
            'timer' => $timer,
        ];
    }

    /**
     * @return array<int, QueryLogEntry>
     */
    public function all(): array
    {
        return $this->logs;
    }

    public function clear(): void
    {
        $this->logs = [];
    }
}
