# 01 · Getting Started

`initorm/dbal` is a thin layer over PDO. It does **not** parse SQL, build
queries, or model tables — that is the job of higher layers in the InitORM
stack (`initorm/query-builder`, `initorm/database`, `initorm/orm`). What it
gives you is:

1. A `Connection` that lazily instantiates PDO from a credentials array.
2. A `DataMapper` that wraps each `PDOStatement` and exposes a small,
   fluent API for binding values and fetching results.

This page walks through the smallest useful program end-to-end. Every
snippet runs unmodified against SQLite in-memory.

## Install

```bash
composer require initorm/dbal
```

Requirements: PHP 8.0+, `ext-pdo`, and the driver extension for your
database (`pdo_mysql`, `pdo_pgsql`, or `pdo_sqlite`).

## Open a connection

```php
use InitORM\DBAL\Connection\Connection;

$db = new Connection([
    'driver'   => 'sqlite',
    'database' => ':memory:',
    'charset'  => '',  // sqlite has no charset concept
]);
```

No connection is opened yet — `Connection` only instantiates PDO when you
call `getPDO()`, `query()`, or any forwarded PDO method.

## Run a query

```php
$db->getPDO()->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');

$db->query(
    'INSERT INTO users (name) VALUES (:name)',
    ['name' => 'Alice']
);

$user = $db->query('SELECT * FROM users WHERE id = :id', ['id' => 1])
           ->asAssoc()
           ->row();

// ['id' => 1, 'name' => 'Alice']
```

Three things happen on the read:

1. `query()` prepares the statement and binds `:id` with `PARAM_INT`.
2. It returns a `DataMapper`.
3. `asAssoc()` sets the fetch mode; `row()` returns the next row (or `null`).

## What's next

- [02 · Connection](02-connection.md) — credentials, lifecycle, cloning.
- [03 · Querying](03-querying.md) — parameters, prepare options, errors.
- [04 · DataMapper](04-data-mapper.md) — fetch modes, binding, forwarding.
