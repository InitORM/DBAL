<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Exceptions;

/**
 * Thrown (historically) when the caller tries to mutate connection credentials
 * after the underlying PDO has already been instantiated.
 *
 * @deprecated since 2.0; prefer {@see ConnectionAlreadyEstablishedException},
 *             a more accurately named subclass. This class is kept as the
 *             parent so existing `catch` blocks continue to work — instances
 *             thrown by the library are now of the new (sub)type but still
 *             match `catch (ValidConnectionAvailableException $e)`.
 */
class ValidConnectionAvailableException extends ConnectionException
{
}
