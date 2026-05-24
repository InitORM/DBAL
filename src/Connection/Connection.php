<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection;

use InitORM\DBAL\Connection\Exceptions\ConnectionAlreadyEstablishedException;
use InitORM\DBAL\Connection\Exceptions\ConnectionException;
use InitORM\DBAL\Connection\Exceptions\ConnectionInvalidArgumentException;
use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;
use InitORM\DBAL\Connection\Interfaces\ConnectionInterface;
use InitORM\DBAL\Connection\Support\DsnBuilder;
use InitORM\DBAL\Connection\Support\Logger;
use InitORM\DBAL\Connection\Support\QueryLogger;
use InitORM\DBAL\DataMapper\DataMapperFactory;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperFactoryInterface;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDO;
use PDOException;
use Throwable;

use function array_merge;
use function is_string;
use function microtime;
use function preg_match;

/**
 * Thin PDO wrapper that handles lazy connection, credentials lifecycle,
 * query execution, and result mapping.
 *
 * Unknown method calls are forwarded to the underlying `PDO` instance (see
 * {@see self::__call()}); the `@mixin` annotation lets IDEs autocomplete the
 * PDO surface through this class.
 *
 * @mixin PDO
 */
class Connection implements ConnectionInterface
{
    /**
     * @var array{
     *     dsn: string,
     *     username: string|null,
     *     password: string|null,
     *     charset: string,
     *     collation: string|null,
     *     options: array<int, mixed>,
     *     driver: string,
     *     host: string,
     *     port: int|string,
     *     database: string,
     *     queryOptions: array<int, mixed>,
     *     log: mixed,
     *     debug: bool,
     *     queryLogs: bool
     * }
     */
    protected array $credentials = [
        'dsn'          => '',
        'username'     => null,
        'password'     => null,
        'charset'      => 'utf8mb4',
        'collation'    => null,
        'options'      => [],

        'driver'       => 'mysql',
        'host'         => '127.0.0.1',
        'port'         => 3306,
        'database'     => '',

        'queryOptions' => [],

        'log'          => null,
        'debug'        => false,
        'queryLogs'    => false,
    ];

    private ?PDO $pdo = null;

    private DataMapperFactoryInterface $dataMapperFactory;

    private DsnBuilder $dsnBuilder;

    private QueryLogger $queryLogger;

    private Logger $logger;

    /**
     * Identifiers (charset, collation, driver) must match this pattern to be
     * safe for direct interpolation into a `SET NAMES ... COLLATE ...`
     * statement — they cannot be bound via prepared statements.
     */
    private const SAFE_IDENTIFIER = '/^[A-Za-z0-9_]+$/';

    /**
     * @param array<string, mixed> $credentials Merged on top of the defaults.
     */
    public function __construct(
        array $credentials = [],
        ?DataMapperFactoryInterface $dataMapperFactory = null,
        ?DsnBuilder $dsnBuilder = null
    ) {
        $this->credentials = array_merge($this->credentials, $credentials);

        $this->dataMapperFactory = $dataMapperFactory ?? new DataMapperFactory();
        $this->dsnBuilder        = $dsnBuilder ?? new DsnBuilder();
        $this->queryLogger       = new QueryLogger((bool) $this->credentials['queryLogs']);
        $this->logger            = new Logger($this->credentials['log']);
    }

    /**
     * Forward unknown method calls to the underlying PDO instance.
     *
     * When PDO returns itself (chainable methods) the call is re-wrapped to
     * return this Connection, preserving fluent chains across the wrapper
     * boundary.
     *
     * @param array<int, mixed> $arguments
     * @return mixed
     * @throws ConnectionException
     */
    public function __call(string $name, array $arguments)
    {
        $result = $this->getPDO()->{$name}(...$arguments);

        return $result instanceof PDO ? $this : $result;
    }

    public function clone(): self
    {
        return new self($this->credentials, $this->dataMapperFactory, $this->dsnBuilder);
    }

    public function getPDO(): PDO
    {
        if ($this->pdo === null) {
            $this->connect();
        }

        return $this->pdo;
    }

    public function connect(): bool
    {
        try {
            $this->pdo = new PDO(
                $this->getDsn(),
                $this->getUsername(),
                $this->getPassword(),
                $this->getOptions()
            );

            $this->applyCharsetAndCollation();

            if (empty($this->credentials['driver'])) {
                $this->credentials['driver'] = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            }

            return true;
        } catch (Throwable $e) {
            throw new ConnectionException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function disconnect(): bool
    {
        $this->pdo = null;

        return true;
    }

    public function setDatabase(string $database): self
    {
        $this->guardNotConnected();
        $this->credentials['database'] = $database;

        return $this;
    }

    public function getDatabase(): ?string
    {
        $database = $this->credentials['database'] ?? null;

        return $database === '' ? null : $database;
    }

    public function setHost(string $host): self
    {
        $this->guardNotConnected();
        $this->credentials['host'] = $host;

        return $this;
    }

    public function getHost(): ?string
    {
        return $this->credentials['host'] ?? null;
    }

    public function setPort($port): self
    {
        $this->guardNotConnected();
        $this->credentials['port'] = $port;

        return $this;
    }

    /**
     * @return int|string|null
     */
    public function getPort()
    {
        return $this->credentials['port'] ?? null;
    }

    public function setCharset(string $charset = 'utf8mb4', ?string $collation = null): self
    {
        $this->guardNotConnected();
        $this->assertSafeIdentifier($charset, 'charset');
        if ($collation !== null) {
            $this->assertSafeIdentifier($collation, 'collation');
        }

        $this->credentials['charset']   = $charset;
        $this->credentials['collation'] = $collation;

        return $this;
    }

    public function getCharset(): string
    {
        return $this->credentials['charset'];
    }

    public function getCollation(): ?string
    {
        return $this->credentials['collation'];
    }

    public function setDsn(string $dsn): self
    {
        $this->guardNotConnected();
        $this->credentials['dsn'] = $dsn;

        return $this;
    }

    public function getDsn(): string
    {
        if (empty($this->credentials['dsn'])) {
            $this->credentials['dsn'] = $this->dsnBuilder->build($this->credentials);
        }

        return $this->credentials['dsn'];
    }

    public function setUsername(?string $username): self
    {
        $this->guardNotConnected();
        $this->credentials['username'] = $username;

        return $this;
    }

    public function getUsername(): ?string
    {
        return $this->credentials['username'];
    }

    public function setPassword(?string $password = null): self
    {
        $this->guardNotConnected();
        $this->credentials['password'] = $password;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->credentials['password'];
    }

    public function setDriver(string $driver): self
    {
        $this->guardNotConnected();
        $this->assertSafeIdentifier($driver, 'driver');
        $this->credentials['driver'] = $driver;

        return $this;
    }

    public function getDriver(): string
    {
        return $this->credentials['driver'];
    }

    public function setOptions(array $options = []): self
    {
        $this->guardNotConnected();
        if ($options !== []) {
            $this->credentials['options'] = $options + $this->credentials['options'];
        }

        return $this;
    }

    /**
     * @return array<int, mixed>
     */
    public function getOptions(): array
    {
        $defaults = [
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_PERSISTENT         => false,
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ];

        return $this->credentials['options'] + $defaults;
    }

    public function addQueryLog(string $query, ?array $args = null, ?float $startTime = null): void
    {
        $this->queryLogger->add($query, $args, $startTime);
    }

    public function getQueryLogs(): array
    {
        return $this->queryLogger->all();
    }

    public function setQueryLogs(bool $status = false): self
    {
        $this->credentials['queryLogs'] = $status;
        $this->queryLogger->enable($status);

        return $this;
    }

    public function createLog(string $message, ?array $context = null): bool
    {
        // Pick up the latest user-supplied sink in case it was set after
        // construction (e.g. setOptions-style late binding by frameworks).
        $this->logger->setSink($this->credentials['log']);

        return $this->logger->write($message, $context);
    }

    public function setDebug(bool $status = false): self
    {
        $this->credentials['debug'] = $status;

        return $this;
    }

    public function getDebug(): bool
    {
        return $this->credentials['debug'];
    }

    /**
     * @throws ConnectionException
     * @throws SQLExecuteException
     */
    public function query(string $sqlQuery, ?array $parameters = null, ?array $options = null): DataMapperInterface
    {
        $startTime = microtime(true);

        // `+` keeps integer keys intact; `array_merge` would renumber them
        // and break PDO::ATTR_* options (BUG-4).
        $prepareOptions = ($options ?? []) + $this->credentials['queryOptions'];

        try {
            $stmt = $this->getPDO()->prepare($sqlQuery, $prepareOptions);
            if ($stmt === false) {
                throw new SQLExecuteException('The SQL query could not be prepared: ' . $sqlQuery);
            }

            $dataMapper = $this->dataMapperFactory->createDataMapper($stmt);

            if (!empty($parameters)) {
                $dataMapper->bindValues($parameters);
            }

            if (!$dataMapper->execute()) {
                throw new SQLExecuteException('The SQL query could not be executed: ' . $sqlQuery);
            }

            $this->addQueryLog($sqlQuery, $parameters, $startTime);

            return $dataMapper;
        } catch (Throwable $e) {
            $this->addQueryLog($sqlQuery, $parameters, $startTime);
            $this->createLog($this->formatFailureMessage($e, $sqlQuery, $parameters));
            throw $e;
        }
    }

    /**
     * @throws ConnectionAlreadyEstablishedException
     */
    private function guardNotConnected(): void
    {
        if ($this->pdo !== null) {
            throw new ConnectionAlreadyEstablishedException();
        }
    }

    private function applyCharsetAndCollation(): void
    {
        if ($this->credentials['driver'] !== 'mysql') {
            // SET NAMES / SET CHARACTER SET are MySQL-isms. PostgreSQL uses
            // `client_encoding` (which we leave to the DSN), and SQLite has
            // no character-set concept. Apply them only for MySQL.
            return;
        }

        $charset   = $this->credentials['charset'];
        $collation = $this->credentials['collation'];
        if ($charset === '') {
            return;
        }

        // Charset values come from configuration but we still defend against
        // accidental injection with a strict whitelist (BUG-7).
        if (preg_match(self::SAFE_IDENTIFIER, $charset) !== 1) {
            return;
        }

        $pdo = $this->pdo;
        if ($pdo === null) {
            return;
        }

        if (is_string($collation) && preg_match(self::SAFE_IDENTIFIER, $collation) === 1) {
            $pdo->exec("SET NAMES '{$charset}' COLLATE '{$collation}'");
        } else {
            $pdo->exec("SET NAMES '{$charset}'");
        }

        $pdo->exec("SET CHARACTER SET '{$charset}'");
    }

    /**
     * @param array<string, mixed>|null $parameters
     */
    private function formatFailureMessage(Throwable $e, string $sqlQuery, ?array $parameters): string
    {
        $message = $e->getMessage() . PHP_EOL . 'SQL : ' . $sqlQuery;

        if ($this->credentials['debug'] && !empty($parameters)) {
            $encoded = json_encode($parameters, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            if ($encoded !== false) {
                $message .= PHP_EOL . 'PARAMS : ' . $encoded;
            }
        }

        return $message;
    }

    /**
     * @throws ConnectionInvalidArgumentException
     */
    private function assertSafeIdentifier(string $value, string $label): void
    {
        if (preg_match(self::SAFE_IDENTIFIER, $value) !== 1) {
            throw new ConnectionInvalidArgumentException(sprintf(
                'Invalid %s "%s"; must match %s.',
                $label,
                $value,
                self::SAFE_IDENTIFIER
            ));
        }
    }
}
