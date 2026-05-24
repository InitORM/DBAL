# 07 · Factories & DI

DBAL ships two small factories so you can build connections and mappers
through a container without hand-wiring `new`.

## `ConnectionFactory`

```php
use InitORM\DBAL\Connection\ConnectionFactory;
use InitORM\DBAL\Connection\Interfaces\ConnectionFactoryInterface;

$factory = new ConnectionFactory();
$db      = $factory->createConnection([
    'driver'   => 'mysql',
    'host'     => 'db',
    'database' => 'shop',
    // ...
]);
```

`ConnectionFactory` implements `ConnectionFactoryInterface`. Bind that
interface in your container and request it instead of the concrete class —
this lets you swap in a fake factory for tests:

```php
// PHP-DI / Symfony / etc.
$container->bind(ConnectionFactoryInterface::class, ConnectionFactory::class);
```

## `DataMapperFactory`

`Connection::query()` uses a `DataMapperFactory` internally to wrap each
prepared statement. You can replace it through the second constructor
argument — handy in tests:

```php
use InitORM\DBAL\Connection\Connection;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperFactoryInterface;

$fakeFactory = new class implements DataMapperFactoryInterface {
    public function createDataMapper(\PDOStatement $statement): \InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface
    {
        return new MyTracingDataMapper($statement);
    }
};

$db = new Connection($credentials, $fakeFactory);
```

## `DsnBuilder`

The DSN composer is also injectable via the third constructor argument.
Use it when you need to support a driver DBAL doesn't know about, or to
adapt the DSN shape for a connection-pooling proxy:

```php
use InitORM\DBAL\Connection\Support\DsnBuilder;

$customBuilder = new class extends DsnBuilder {
    public function build(array $credentials): string
    {
        if (($credentials['driver'] ?? '') === 'proxysql') {
            return sprintf('mysql:host=proxysql;port=6033;dbname=%s', $credentials['database']);
        }
        return parent::build($credentials);
    }
};

$db = new Connection($credentials, null, $customBuilder);
```

## Singleton vs per-request

Connections are cheap to *construct* (no socket opens until the first
query) but expensive to *open*. For long-running workers, build one
Connection per database and share it. For PHP-FPM, build one per request —
the OS connection is gone when the request ends anyway.

If you need both a shared and a fresh instance, call `clone()`:

```php
$primary  = $container->get(ConnectionInterface::class);
$transient = $primary->clone()->setHost('replica');
```

## What's next

- [08 · Exceptions](08-exceptions.md) — the exception hierarchy in full.
