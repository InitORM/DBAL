# InitORM DBAL

[![PHPUnit](https://github.com/InitORM/DBAL/actions/workflows/phpunit.yml/badge.svg)](https://github.com/InitORM/DBAL/actions/workflows/phpunit.yml)
[![Latest Stable Version](https://poser.pugx.org/initorm/dbal/v/stable)](https://packagist.org/packages/initorm/dbal)
[![Total Downloads](https://poser.pugx.org/initorm/dbal/downloads)](https://packagist.org/packages/initorm/dbal)
[![License](https://poser.pugx.org/initorm/dbal/license)](LICENSE)
[![PHP Version Require](https://poser.pugx.org/initorm/dbal/require/php)](https://packagist.org/packages/initorm/dbal)

A small, dependency-free database abstraction layer for PHP. `initorm/dbal`
gives you a thin, lazily-connecting PDO wrapper and a fluent result mapper —
nothing more.

It is part of the [InitORM](https://github.com/InitORM) stack, but it has no
runtime dependencies and can be used on its own anywhere PDO can.

## Highlights

- **Lazy connections.** No socket is opened until the first query.
- **Driver-aware DSN building.** MySQL/MariaDB, PostgreSQL, SQLite.
- **Fluent result mapper.** `asAssoc()`, `asObject()`, `asClass()`,
  `asLazy()`, `asArray()` / `asBoth()`.
- **Type-aware binding.** `bool` → `PARAM_BOOL`, `int` → `PARAM_INT`,
  `null` → `PARAM_NULL`, everything else → `PARAM_STR`.
- **In-memory query log** for debugging hotspots.
- **PSR-3 friendly logging.** Pass any `LoggerInterface`, a callable, a file
  path, or anything with a `critical()` method.
- **Transparent PDO forwarding.** Unknown method calls are forwarded to the
  underlying `PDO` / `PDOStatement`, so `lastInsertId()`, `beginTransaction()`,
  `closeCursor()`, etc. all work directly on the wrapper.

## Installation

```bash
composer require initorm/dbal
```

Requirements: **PHP 8.0+** and the `pdo` extension, plus the driver
extension for the database you target (`pdo_mysql`, `pdo_pgsql`,
`pdo_sqlite`).

## 60-second quick start

```php
use InitORM\DBAL\Connection\Connection;

$db = new Connection([
    'driver'   => 'mysql',
    'host'     => '127.0.0.1',
    'port'     => 3306,
    'database' => 'shop',
    'username' => 'app',
    'password' => 'secret',
    'charset'  => 'utf8mb4',
]);

// Read
$user = $db->query('SELECT id, name FROM users WHERE id = :id', ['id' => 42])
           ->asAssoc()
           ->row();

// Write
$db->query(
    'INSERT INTO users (name, email) VALUES (:name, :email)',
    ['name' => 'Alice', 'email' => 'alice@example.com']
);
$newId = (int) $db->lastInsertId();      // forwarded to PDO
```

## Documentation

Full docs live in [`docs/`](docs/):

- [01 · Getting Started](docs/01-getting-started.md)
- [02 · Connection](docs/02-connection.md)
- [03 · Querying](docs/03-querying.md)
- [04 · DataMapper](docs/04-data-mapper.md)
- [05 · Transactions](docs/05-transactions.md)
- [06 · Logging](docs/06-logging.md)
- [07 · Factories & DI](docs/07-factories-and-di.md)
- [08 · Exceptions](docs/08-exceptions.md)
- [09 · Recipes](docs/09-recipes.md)

## Testing

```bash
composer install
composer test
```

The suite runs against SQLite in-memory and finishes in well under a
second.

## Upgrading from 1.x

See [`UPGRADE.md`](UPGRADE.md). The notable breaking changes are: PHP ≥ 8.0,
`src/` layout, `PDO::ATTR_PERSISTENT` defaults to `false`, `rows()` returns
`[]` instead of `null` when empty, and `bind()` returns `PARAM_BOOL` for
booleans.

## Contributing

Issues and pull requests are welcome. Please:

1. Open an issue first if you intend to make a non-trivial change.
2. Make sure `composer test` is green.
3. Cover new behaviour with a test under `tests/`.

See the organisation-wide [contribution guide](https://github.com/InitORM/.github/blob/master/CONTRIBUTING.md)
for the full process.

## License

[MIT](LICENSE) © Muhammet ŞAFAK
