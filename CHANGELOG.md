# Changelog

All notable changes to `initorm/dbal` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased] — 2.0.0

This release focuses on correctness, standards compliance, testability, and
documentation. Several long-standing bugs in DSN building and query
preparation have been fixed; these fixes are technically breaking for any
caller that depended on the previous (incorrect) behaviour.

### Added
- PSR-4 `src/` layout. Autoload entries updated accordingly.
- Optional [PSR-3](https://www.php-fig.org/psr/psr-3/) `LoggerInterface`
  support: any object implementing `Psr\Log\LoggerInterface` can be passed as
  the `log` credential. PSR-3 `{placeholder}` format is honoured in
  `createLog()`.
- `src/Connection/Support/DsnBuilder.php` — driver-aware DSN composer.
- `src/Connection/Support/QueryLogger.php` — encapsulates `addQueryLog` /
  `getQueryLogs`.
- `src/Connection/Support/Logger.php` — encapsulates the `createLog` strategy
  (PSR-3, callable, file path, duck-typed `critical`).
- `ConnectionAlreadyEstablishedException` (replaces the misnamed
  `ValidConnectionAvailableException`, which is kept as a deprecated alias).
- Split interfaces (`ConfigurableConnectionInterface`,
  `LoggableConnectionInterface`) to honour ISP; `ConnectionInterface` extends
  both so existing consumers keep working.
- Full PHPUnit test suite (SQLite in-memory) with per-bug regression tests.
- GitHub Actions workflows: `phpunit.yml` (PHP 8.0–8.4 matrix) and
  `composer-validate.yml`.
- `docs/` — full developer documentation with runnable examples.

### Changed
- **BREAKING** PHP requirement raised from `>=7.4` to `>=8.0`.
- **BREAKING** Top-level `Connection/` and `DataMapper/` directories moved to
  `src/Connection/` and `src/DataMapper/`. Consumers using Composer autoload
  are unaffected; only direct path references (rare) break.
- **BREAKING** `PDO::ATTR_PERSISTENT` default is now `false`. Persistent
  connections leak transactions and prepared-statement caches across
  requests; opt in explicitly via `['options' => [PDO::ATTR_PERSISTENT =>
  true]]` if you need them.
- **BREAKING** `DataMapper::bind()` returns `PDO::PARAM_BOOL` for boolean
  values (previously `PARAM_INT`). PostgreSQL boolean columns require this.
- **BREAKING** `DataMapper::rows()` returns `[]` (empty array) when no rows
  match, instead of `null`. The return type is now `array` (non-nullable).
- `Connection::query()` now uses `$queryOptions + ($options ?? [])` instead of
  `array_merge`, preserving the integer keys PDO requires for prepare
  options.
- `Connection::connect()` only emits `SET NAMES / SET CHARACTER SET` for the
  `mysql` driver, and rejects charset/collation values that contain non
  `[A-Za-z0-9_]` characters (defence-in-depth against SQL injection through
  configuration).
- The error message produced when a query fails no longer interpolates raw
  parameter values into the SQL string via `strtr`. The parameter array is
  serialised separately, avoiding `TypeError` when values contain `null`,
  `bool`, or `int`.
- `createLog()` now formats messages with PSR-3 style `{key}` placeholders.

### Fixed
- `Connection::getDsn()` no longer ignores its own auto-build branch
  (`!isset` against an always-present key) and no longer writes the charset
  into the `dbname=` part of the DSN.
- `Connection::connect()` reads the actual PDO driver name into the
  credentials even when the user did not explicitly provide a driver.
- `Connection::query()` removes the dead `errorCode()` branch that could
  never run under `ERRMODE_EXCEPTION`.
- `DataMapper::__get` / `__isset` PHPDoc no longer claims to throw an
  exception that the implementation cannot raise.

### Removed
- Unused `setDebug` / `getDebug` plumbing was wired into
  `Connection::query()`: when enabled, error messages now include the SQL
  and a JSON-encoded parameter dump. (Previously these methods set a flag
  that nothing read.)
- `@version 1.0` tags from file-level docblocks — versioning is owned by
  git tags.
