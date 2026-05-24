<?php

declare(strict_types=1);

namespace InitORM\DBAL\DataMapper\Exceptions;

use InvalidArgumentException;

/**
 * Thrown when an invalid argument is supplied to the DataMapper layer.
 */
final class DataMapperInvalidArgumentException extends InvalidArgumentException
{
}
