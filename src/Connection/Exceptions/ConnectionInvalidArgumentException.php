<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when an invalid argument is supplied to the Connection layer.
 *
 * Distinct from `ConnectionException` because it represents a *programmer*
 * error (bad input), not a runtime failure of the database connection.
 */
final class ConnectionInvalidArgumentException extends InvalidArgumentException
{
}
