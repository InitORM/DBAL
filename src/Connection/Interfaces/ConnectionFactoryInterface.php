<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Interfaces;

interface ConnectionFactoryInterface
{
    /**
     * @param array<string, mixed> $credentials Merged on top of the default
     *        credentials inside the Connection constructor.
     */
    public function createConnection(array $credentials = []): ConnectionInterface;
}
