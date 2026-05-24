<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Interfaces;

/**
 * Logging surface for a Connection — query log buffer and the user-supplied
 * `log` sink used by {@see self::createLog()}.
 */
interface LoggableConnectionInterface
{
    /**
     * Append an entry to the in-memory query log.
     *
     * No-op when query logging is disabled. `$startTime` is a `microtime(true)`
     * value taken just before the query ran; its difference with the current
     * `microtime(true)` is stored in the `timer` field.
     *
     * @param array<string, mixed>|null $args
     */
    public function addQueryLog(string $query, ?array $args = null, ?float $startTime = null): void;

    /**
     * @return array<int, array{query: string, args: array<string, mixed>|null, timer: float}>
     */
    public function getQueryLogs(): array;

    public function setQueryLogs(bool $status = false): self;

    /**
     * Forward a message to the configured `log` sink (PSR-3 logger, callable,
     * file path, or `critical`-method duck type). Returns `false` when no
     * sink is configured.
     *
     * Placeholders in `$context` follow PSR-3 conventions: `{key}` in the
     * message is replaced with `(string) $context[$key]`.
     *
     * @param array<string, scalar|\Stringable|null>|null $context
     */
    public function createLog(string $message, ?array $context = null): bool;

    public function setDebug(bool $status = false): self;

    public function getDebug(): bool;
}
