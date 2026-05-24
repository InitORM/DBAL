# 05 · Transactions

DBAL does not add an explicit transaction API. The PDO methods are
forwarded directly:

```php
$db->beginTransaction();
try {
    $db->query(
        'INSERT INTO orders (user_id, total) VALUES (:user, :total)',
        ['user' => 1, 'total' => 100]
    );
    $db->query(
        'INSERT INTO order_items (order_id, sku, qty) VALUES (:id, :sku, :qty)',
        ['id' => $db->lastInsertId(), 'sku' => 'X-1', 'qty' => 2]
    );
    $db->commit();
} catch (\Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}
```

`beginTransaction`, `inTransaction`, `commit`, `rollBack`, `lastInsertId`,
and friends all work through `Connection::__call()` because they exist on
the underlying PDO instance.

## Nesting & savepoints

PDO itself does not implement savepoints portably. If you need them, run
the driver-specific SQL directly:

```php
$db->getPDO()->exec('SAVEPOINT sp1');
// ... work ...
$db->getPDO()->exec('ROLLBACK TO SAVEPOINT sp1');
```

## Persistent connections — read this once

If you set `PDO::ATTR_PERSISTENT => true`, several gotchas appear:

1. **Aborted transactions can leak.** A request that ends mid-transaction
   leaves the connection in an open-transaction state for the next request
   that picks it up.
2. **Prepared-statement caches survive across requests** on some drivers,
   which can confuse migrations.
3. `Connection::disconnect()` returns the connection to the pool rather
   than closing it.

The library default is `false` for these reasons. Opt in deliberately, and
always wrap every transaction with the `try/catch + rollBack` pattern
above.

## What's next

- [06 · Logging](06-logging.md) — capture every query and every failure.
