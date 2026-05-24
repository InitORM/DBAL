# 08 · Exceptions

DBAL throws four classes of exception, all in
`InitORM\DBAL\Connection\Exceptions` and `InitORM\DBAL\DataMapper\Exceptions`.

## Hierarchy

```
\Exception
└── ConnectionException
    ├── SQLExecuteException
    └── ValidConnectionAvailableException        [deprecated alias parent]
        └── ConnectionAlreadyEstablishedException  [preferred name]

\Exception
└── DataMapperException

\InvalidArgumentException
├── ConnectionInvalidArgumentException
└── DataMapperInvalidArgumentException
```

## When each is thrown

### `ConnectionException`

The base exception for the Connection layer. Raised by `connect()` when
PDO itself rejects the credentials. Always preserved as the `previous`
exception so the original PDO message is reachable.

```php
use InitORM\DBAL\Connection\Exceptions\ConnectionException;

try {
    $db->getPDO();
} catch (ConnectionException $e) {
    error_log($e->getMessage());
    error_log($e->getPrevious()?->getMessage() ?? '');
}
```

### `SQLExecuteException`

Raised by `Connection::query()` when `prepare()` returns `false` or
`execute()` returns `false`. In practice, when running under
`ERRMODE_EXCEPTION` (the default), PDO will normally raise a
`PDOException` *before* this gets a chance to fire — but the type is
useful when you opt out of exception mode.

### `ConnectionAlreadyEstablishedException`

Raised when a setter (`setHost`, `setDatabase`, `setCharset`, etc.) is
called after the underlying PDO has already been instantiated. The fix is
to `disconnect()` first or build a fresh Connection via `clone()`.

```php
use InitORM\DBAL\Connection\Exceptions\ConnectionAlreadyEstablishedException;
use InitORM\DBAL\Connection\Exceptions\ValidConnectionAvailableException;

try {
    $db->setDatabase('other');
} catch (ConnectionAlreadyEstablishedException $e) {
    // preferred — new name
}
// Or, for compatibility with code written against 1.x:
try {
    $db->setDatabase('other');
} catch (ValidConnectionAvailableException $e) {
    // also works — old name remains the parent class
}
```

### `ConnectionInvalidArgumentException`

Raised by `setCharset()`, `setDriver()`, and friends when the supplied
identifier contains characters outside `[A-Za-z0-9_]`. This guards the
`SET NAMES ... COLLATE ...` statement, which cannot use bound parameters.

### `DataMapperException` / `DataMapperInvalidArgumentException`

Reserved for future use; the current `DataMapper` implementation does not
raise them on its own (PDO exceptions bubble up unwrapped).

## Catching everything from DBAL

There is no single root class deliberately — `ConnectionException` and
`DataMapperException` are siblings under `\Exception`. For a blanket
catch, use `\Throwable`:

```php
try {
    $db->query('SELECT 1');
} catch (\Throwable $e) {
    // any DBAL or PDO failure lands here
}
```
