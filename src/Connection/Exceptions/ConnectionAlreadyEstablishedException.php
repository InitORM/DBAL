<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Exceptions;

/**
 * Thrown when the caller tries to mutate connection credentials after the
 * underlying PDO has already been instantiated.
 *
 * To change credentials, call `disconnect()` first or `clone()` to obtain a
 * fresh instance.
 *
 * Extends {@see ValidConnectionAvailableException} (the historical name) so
 * that callers using either `catch` clause keep working.
 */
class ConnectionAlreadyEstablishedException extends ValidConnectionAvailableException
{
    public function __construct(
        string $message = 'A live PDO connection already exists; disconnect before mutating credentials.',
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
