# 09 · Recipes

Small, copy-pastable patterns for common situations.

## Multi-database routing

```php
use InitORM\DBAL\Connection\Connection;

$primary = new Connection($writeConfig);
$replica = $primary->clone()
    ->setHost('replica.internal')
    ->setUsername('readonly');

function dbFor(string $sql): Connection {
    global $primary, $replica;
    return preg_match('/^\s*SELECT\b/i', $sql) ? $replica : $primary;
}
```

## Runtime database switch

```php
$db->disconnect();
$db->setDatabase('reporting');
$db->getPDO();
```

`setDatabase()` throws while the connection is open — `disconnect()` first.

## Streaming a large result set

```php
$mapper = $db->query('SELECT * FROM events ORDER BY id');
$mapper->asAssoc();
while (($row = $mapper->row()) !== null) {
    handle($row);
}
$mapper->closeCursor();
```

`row()` calls `PDOStatement::fetch()` under the hood — no buffering in
PHP-land beyond what the driver does itself.

## Hydrating into a target class

```php
final class User
{
    public int $id = 0;
    public string $name = '';
}

$users = $db->query('SELECT id, name FROM users')
            ->asClass(User::class)
            ->rows();   // array<int, User>
```

Be aware that `FETCH_CLASS` writes properties *before* the constructor
runs, so any setup logic in `__construct` sees populated state.

## Testing with SQLite in-memory

```php
final class UserRepositoryTest extends \PHPUnit\Framework\TestCase
{
    private \InitORM\DBAL\Connection\Connection $db;

    protected function setUp(): void
    {
        $this->db = new \InitORM\DBAL\Connection\Connection([
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'charset'  => '',
        ]);
        $this->db->getPDO()->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
    }
}
```

## Capturing queries during a request

```php
$db->setQueryLogs(true);

// ... application code ...

register_shutdown_function(static function () use ($db): void {
    file_put_contents(
        '/var/log/app/queries.log',
        json_encode($db->getQueryLogs(), JSON_PRETTY_PRINT)
    );
});
```

## Replacing the DSN strategy

```php
use InitORM\DBAL\Connection\Connection;
use InitORM\DBAL\Connection\Support\DsnBuilder;

$builder = new class extends DsnBuilder {
    public function build(array $credentials): string
    {
        // Route writes through a single-writer proxy, reads direct.
        if (($credentials['_role'] ?? 'r') === 'w') {
            return 'mysql:host=writer-proxy;port=6033;dbname=' . $credentials['database'];
        }
        return parent::build($credentials);
    }
};

$write = new Connection(['_role' => 'w', /* ... */], null, $builder);
$read  = new Connection(['_role' => 'r', /* ... */], null, $builder);
```

## Composing with a PSR-3 logger

```php
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$logger = new Logger('db');
$logger->pushHandler(new StreamHandler('php://stderr'));

$db = new \InitORM\DBAL\Connection\Connection([
    'log' => $logger,
    // ...
]);
```

Failures are logged at `critical` level with PSR-3 placeholders intact.

## Forwarding to PDO without leaking the wrapper

Sometimes you need to hand a raw `PDO` to a third-party library:

```php
$thirdParty->setPdo($db->getPDO());
```

The wrapper does not own the PDO instance — there is no harm in passing
the raw handle around, as long as you do not also call `disconnect()` on
the wrapper while the third party still holds the handle.
