<?php
/**
 * InitORM DBAL
 *
 * This file is part of InitORM DBAL.
 *
 * @author      Muhammet ŞAFAK <info@muhammetsafak.com.tr>
 * @copyright   Copyright © 2023 Muhammet ŞAFAK
 * @license     ./LICENSE  MIT
 * @version     1.0
 * @link        https://www.muhammetsafak.com.tr
 */

declare(strict_types=1);
namespace InitORM\DBAL\Connection;

use InitORM\DBAL\Connection\Exceptions\SQLExecuteException;
use InitORM\DBAL\Connection\Interfaces\ConnectionInterface;
use InitORM\DBAL\Connection\Exceptions\ValidConnectionAvailableException;
use InitORM\DBAL\Connection\Exceptions\ConnectionException;
use InitORM\DBAL\DataMapper\DataMapperFactory;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperFactoryInterface;
use InitORM\DBAL\DataMapper\Interfaces\DataMapperInterface;
use PDO;
use Throwable;

use const PHP_EOL;

class Connection implements ConnectionInterface
{

    /**
     * @var array
     */
    protected array $credentials = [
        'dsn'           => '',
        'username'      => null,
        'password'      => null,
        'charset'       => 'utf8mb4',
        'collation'     => 'utf8mb4_unicode_ci',
        'options'       => [],

        'driver'        => 'mysql',
        'host'          => '127.0.0.1',
        'port'          => 3306,
        'database'      => '',

        'queryOptions'  => [],

        'log'           => null,
        'debug'         => false,
        'queryLogs'     => false,
    ];

    /**
     * @var PDO|null
     */
    private ?PDO $pdo = null;

    /**
     * @var array
     */
    private array $queryLogs = [];

    /**
     * @var DataMapperFactoryInterface
     */
    private DataMapperFactoryInterface $dataMapperFactory;

    /**
     * @inheritDoc
     */
    public function __construct(array $credentials = [])
    {
        $this->credentials = array_merge($this->credentials, $credentials);

        $this->dataMapperFactory = new DataMapperFactory();
    }

    /**
     * @param $name
     * @param $arguments
     * @return Connection|mixed
     * @throws ConnectionException
     */
    public function __call($name, $arguments)
    {
        $res = $this->getPDO()->{$name}(...$arguments);

        return ($res instanceof PDO) ? $this : $res;
    }

    /**
     * @inheritDoc
     */
    public function clone(): self
    {
        return new self($this->credentials);
    }

    /**
     * @inheritDoc
     */
    public function getPDO(): PDO
    {
        !isset($this->pdo) && $this->connect();

        return $this->pdo;
    }

    /**
     * @inheritDoc
     */
    public function connect(): bool
    {
        try {
            $this->pdo = new PDO($this->getDsn(), $this->getUsername(), $this->getPassword(), $this->getOptions());

            if ($charset = $this->getCharset()) {
                if ($collation = $this->getCollation()) {
                    $this->pdo->exec("SET NAMES '" . $charset . "' COLLATE '" . $collation . "'");
                }
                $this->pdo->exec("SET CHARACTER SET '" . $charset . "'");
            }

            if (!isset($this->credentials['driver'])) {
                $this->credentials['driver'] = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
            }

            return true;
        } catch (Throwable $e) {
            throw new ConnectionException($e->getMessage(), (int)$e->getCode(), $e->getPrevious());
        }
    }

    /**
     * @inheritDoc
     */
    public function disconnect(): bool
    {
        $this->pdo = null;

        return true;
    }

    /**
     * @inheritDoc
     */
    public function setDatabase(string $database): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['database'] = $database;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDatabase(): ?string
    {
        return $this->credentials['database'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setHost(string $host): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['host'] = $host;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getHost(): ?string
    {
        return $this->credentials['host'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setPort($port): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['port'] = $port;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPort()
    {
        return $this->credentials['port'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setCharset(string $charset = 'utf8mb4', ?string $collation = null): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['charset'] = $charset;
        $this->credentials['collation'] = empty($collation) ? $charset . '_unicode_ci' : $collation;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getCharset(): string
    {
        return $this->credentials['charset'];
    }

    /**
     * @inheritDoc
     */
    public function getCollation(): string
    {
        return $this->credentials['collation'];
    }

    /**
     * @inheritDoc
     */
    public function setDsn(string $dsn): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['dsn'] = $dsn;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDsn(): string
    {
        if (!isset($this->credentials['dsn'])) {
            $dsn = $this->credentials['driver'] . ':host=' . $this->credentials['host']
                . ';port=' . $this->credentials['port'] . ';dbname=' . $this->credentials['charset']
                . ';charset=' . $this->credentials['charset'];

            $this->credentials['dsn'] = $dsn;
        }

        return $this->credentials['dsn'];
    }

    /**
     * @inheritDoc
     */
    public function setUsername(?string $username): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['username'] = $username;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getUsername(): ?string
    {
        return $this->credentials['username'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setPassword(?string $password = null): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['password'] = $password;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getPassword(): ?string
    {
        return $this->credentials['password'] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function setDriver(string $driver): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        $this->credentials['driver'] = $driver;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDriver(): string
    {
        return $this->credentials['driver'];
    }

    /**
     * @inheritDoc
     */
    public function setOptions(array $options = []): self
    {
        if (isset($this->pdo)) {
            throw new ValidConnectionAvailableException();
        }

        !empty($options) && $this->credentials['options'] = array_merge($this->credentials['options'], $options);

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getOptions(): array
    {
        return array_merge([
            PDO::ATTR_EMULATE_PREPARES      => false,
            PDO::ATTR_PERSISTENT            => true,
            PDO::ATTR_ERRMODE               => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE    => PDO::FETCH_CLASS,
        ], $this->credentials['options']);
    }

    /**
     * @inheritDoc
     */
    public function addQueryLog(string $query, ?array $args = null, $startTime = null): void
    {
        if (!$this->credentials['queryLogs']) {
            return;
        }
        $this->queryLogs[] = [
            'query'     => $query,
            'args'      => $args,
            'timer'     => round(microtime(true) - $startTime, 6),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getQueryLogs(): array
    {
        return $this->queryLogs;
    }

    /**
     * @inheritDoc
     */
    public function setQueryLogs(bool $status = false): self
    {
        $this->credentials['queryLogs'] = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function createLog(string $message, ?array $context = null): bool
    {
        if (empty($this->credentials['log'])) {
            return false;
        }

        !empty($context) && $message = strtr($message, $context);

        if (is_callable($this->credentials['log'])) {
            call_user_func_array($this->credentials['log'], [$message]);

            return true;
        }

        if (is_string($this->credentials['log'])) {
            $path = strtr($this->credentials['log'], [
                '{timestamp}'   => time(),
                '{date}'        => date("Y-m-d"),
                '{datetime}'    => date("Y-m-d-H-i-s"),
                '{year}'        => date("Y"),
                '{month}'       => date("m"),
                '{day}'         => date("d"),
                '{hour}'        => date("H"),
                '{minute}'      => date("i"),
                '{second}'      => date("s"),
            ]);

            return (bool)@file_put_contents($path, $message, FILE_APPEND);
        }

        if (is_object($this->credentials['log']) && method_exists($this->credentials['log'], 'critical')) {
            call_user_func_array([$this->credentials['log'], 'critical'], [$message]);

            return true;
        }

        return false;
    }

    /**
     * @inheritDoc
     */
    public function setDebug(bool $status = false): self
    {
        $this->credentials['debug'] = $status;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getDebug(): bool
    {
        return $this->credentials['debug'];
    }

    /**
     * @inheritDoc
     * @throws Throwable
     */
    public function query(string $sqlQuery, ?array $parameters = null, ?array $options = null): DataMapperInterface
    {
        $startTime = microtime(true);
        try {
            $stmt = $this->getPDO()->prepare($sqlQuery, array_merge($this->credentials['queryOptions'], $options ?? []));
            if (!$stmt) {
                throw new SQLExecuteException('The SQL query could not be prepared.');
            }
            $dataMapper = $this->dataMapperFactory->createDataMapper($stmt);

            !empty($parameters) && $dataMapper->bindValues($parameters);

            if (!$dataMapper->execute()) {
                throw new SQLExecuteException('The SQL query could not be executed.');
            }
            $this->addQueryLog($sqlQuery, $parameters, $startTime);

            $errorCode = $stmt->errorCode();
            if ($errorCode !== null && !empty(trim($errorCode, "0 \n\r\t\v\0"))) {
                $errorInfo = $stmt->errorInfo();
                if (isset($errorInfo[2])) {
                    $message = $errorCode . ' - ' . $errorInfo[2];
                    throw new SQLExecuteException($message);
                }
            }

            return $dataMapper;
        } catch (Throwable $e) {
            $this->addQueryLog($sqlQuery, $parameters, $startTime);
            $message = $e->getMessage() . PHP_EOL . 'SQL : "' . strtr($sqlQuery, $parameters ?? []) . '"';
            $this->createLog($message);
            throw $e;
        }
    }

}
