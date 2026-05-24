# 06 · Logging

Two independent facilities:

| Facility          | Configured by    | Purpose                                              |
|-------------------|------------------|------------------------------------------------------|
| Query log buffer  | `queryLogs` flag | Every executed query is appended in memory.          |
| Critical logger   | `log` credential | Receives failure messages emitted by `createLog()`.  |

## Query log buffer

```php
$db = new Connection(['queryLogs' => true]);
// or at runtime:
$db->setQueryLogs(true);

$db->query('SELECT 1');
$db->getQueryLogs();
// [['query' => 'SELECT 1', 'args' => null, 'timer' => 0.000123]]
```

Each entry is `['query' => string, 'args' => ?array, 'timer' => float]`.
`timer` is the wall-clock time spent inside `Connection::query()`, in
seconds.

The buffer lives in process memory and never rotates — if you keep it on
for an entire long-running worker, drain it periodically.

## The `log` credential

`createLog()` accepts a message and an optional context array, then
dispatches to whichever of these your `log` credential happens to be:

### 1. A `Psr\Log\LoggerInterface`

```php
use Monolog\Logger;

$db = new Connection(['log' => new Logger('db')]);
```

Messages are sent at `critical` level with `{placeholder}` tokens left
intact for the PSR-3 interpolator.

### 2. A callable

```php
$db = new Connection([
    'log' => static function (string $message): void {
        error_log($message);
    },
]);
```

Placeholders are replaced before the callable runs.

### 3. A file path with placeholders

```php
$db = new Connection([
    'log' => __DIR__ . '/logs/db-{date}.log',
]);
```

Supported placeholders: `{timestamp}`, `{date}`, `{datetime}`, `{year}`,
`{month}`, `{day}`, `{hour}`, `{minute}`, `{second}`.

### 4. Any object with a `critical()` method

For codebases that ship their own logger but didn't pull in `psr/log`.

```php
$db = new Connection(['log' => $myLogger]);  // duck-typed
```

## Failure logging

`Connection::query()` calls `createLog()` automatically when the query
fails. The message is:

```
<exception message>
SQL : <the executed SQL>
```

If `debug` is `true`, a JSON dump of the bound parameters is appended:

```
SQL : SELECT * FROM users WHERE id = :id
PARAMS : {"id":1}
```

Set `debug` once at construction or via `setDebug(true)` at runtime.

## What's next

- [07 · Factories & DI](07-factories-and-di.md) for wiring connections in a
  container.
