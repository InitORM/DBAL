<?php

declare(strict_types=1);

namespace InitORM\DBAL\DataMapper\Interfaces;

use InitORM\DBAL\DataMapper\Exceptions\DataMapperException;
use PDOStatement;

/**
 * A thin, fluent wrapper around a single {@see PDOStatement} that handles
 * value binding, fetch-mode selection, and result extraction.
 *
 * Unknown method calls and unknown property reads are forwarded to the
 * underlying statement; the `@mixin` annotation enables IDE autocomplete for
 * the native PDOStatement surface.
 *
 * @mixin PDOStatement
 */
interface DataMapperInterface
{
    /**
     * @throws DataMapperException When the underlying statement has not been initialised.
     */
    public function getStatement(): PDOStatement;

    /**
     * @param array<string, scalar|null>|null $params Optional named parameters; if supplied,
     *        these are passed straight to {@see PDOStatement::execute()} and override any
     *        previously bound values.
     */
    public function execute(?array $params = null): bool;

    /**
     * Return the SQL string the underlying statement was prepared with.
     */
    public function getQuery(): string;

    /**
     * Bind a single named value, automatically choosing the PDO type via
     * {@see self::bind()}. The leading `:` on `$key` is optional.
     */
    public function bindValue(string $key, $value): bool;

    /**
     * Bind every entry in `$fields` as a named value. Returns `true` once all
     * binds succeed.
     *
     * @param array<string, scalar|null> $fields
     */
    public function bindValues(array $fields): bool;

    /**
     * Pick the PDO `PARAM_*` constant for `$value`.
     *
     * - `bool`   → `PARAM_BOOL`
     * - `int`    → `PARAM_INT`
     * - `null`   → `PARAM_NULL`
     * - anything else → `PARAM_STR`
     *
     * @param mixed $value
     */
    public function bind($value): int;

    /**
     * Forward to {@see PDOStatement::rowCount()}. Driver-specific: for SELECT
     * queries the return value is only reliable on drivers that buffer
     * results (e.g. MySQL).
     */
    public function numRows(): int;

    /**
     * Set fetch mode to `PDO::FETCH_CLASS`. Subsequent fetches return
     * instances of `$class` (or stdClass when null).
     */
    public function asClass(?string $class = null): self;

    /**
     * Set fetch mode to either `FETCH_INTO` (when `$obj` is supplied) or
     * `FETCH_OBJ`.
     */
    public function asObject(?object $obj = null): self;

    public function asAssoc(): self;

    public function asLazy(): self;

    public function asArray(): self;

    /**
     * Alias of {@see self::asArray()}.
     */
    public function asBoth(): self;

    /**
     * Fetch the next row, returning `null` when no row is available.
     *
     * @return array<string, mixed>|object|null
     */
    public function row();

    /**
     * Fetch every remaining row as an array (possibly empty).
     *
     * @return array<int, array<string, mixed>|object>
     */
    public function rows(): array;
}
