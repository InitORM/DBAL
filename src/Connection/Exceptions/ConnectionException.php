<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Exceptions;

use Exception;

/**
 * Base exception for every error that originates from the Connection layer.
 *
 * All other Connection exceptions extend this class, so a single
 * `catch (ConnectionException $e)` is enough to handle them all.
 */
class ConnectionException extends Exception
{
}
