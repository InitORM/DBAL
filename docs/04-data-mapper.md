# 04 · DataMapper

`InitORM\DBAL\DataMapper\DataMapper` wraps a single `PDOStatement` and adds
a fluent fetch-mode + result-extraction surface.

## Lifecycle

You never instantiate `DataMapper` directly under normal use — `Connection::query()`
hands you one already prepared and executed.

If you need to drive a raw `PDOStatement` (e.g. when integrating with code
that already holds one), use the factory:

```php
use InitORM\DBAL\DataMapper\DataMapperFactory;

$mapper = (new DataMapperFactory())->createDataMapper($pdoStatement);
```

## Fetch modes

| Method        | PDO mode          | Returns                              |
|---------------|-------------------|--------------------------------------|
| `asAssoc()`   | `FETCH_ASSOC`     | Associative array per row.           |
| `asObject()`  | `FETCH_OBJ`       | `stdClass` per row.                  |
| `asObject($t)`| `FETCH_INTO`      | Populates the supplied object.       |
| `asClass($c)` | `FETCH_CLASS`     | Instance of `$c` per row.            |
| `asArray()`   | `FETCH_BOTH`      | Both numeric and named keys.         |
| `asBoth()`    | alias of `asArray`|                                      |
| `asLazy()`    | `FETCH_LAZY`      | Row object lazily populating fields. |

Fetch mode is sticky on the underlying statement — set it once, then call
`row()` / `rows()` as many times as you need.

## Fetching results

```php
$mapper = $db->query('SELECT id, name FROM users');

$first = $mapper->asAssoc()->row();     // first row or null
$all   = $mapper->asAssoc()->rows();    // every remaining row (possibly [])
$count = $mapper->numRows();            // driver-specific; see below
```

> **`rows()` returns `[]`, not `null`, when there are no rows.** This is a
> 2.0 behaviour change. Existing code that compared against `null` should
> compare against `[]` instead.

> **`numRows()`** forwards to `PDOStatement::rowCount()`. For SELECT
> statements this is only reliable on drivers that buffer the entire
> result set (MySQL with the default attributes; PostgreSQL too). For
> SQLite, prefer running a separate `SELECT COUNT(*)`.

## Binding values manually

```php
$mapper = $db->query('SELECT id FROM users WHERE id = :id');  // no params yet
// ...

$mapper->bindValue('id', 7);          // single
$mapper->bindValues(['id' => 7]);     // bulk
$mapper->execute();
```

The leading colon on the key is optional.

## Forwarding to PDOStatement

Anything you call on the mapper that isn't defined above is forwarded to
the underlying `PDOStatement`:

```php
$mapper->closeCursor();    // PDOStatement::closeCursor()
$mapper->columnCount();    // PDOStatement::columnCount()
$mapper->getColumnMeta(0); // PDOStatement::getColumnMeta()
```

Property access is forwarded too:

```php
$mapper->queryString;   // == PDOStatement::$queryString
```

## When you really need the raw statement

```php
$stmt = $mapper->getStatement();
```

Useful for libraries that expect a `PDOStatement` rather than a wrapper.
