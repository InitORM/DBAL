<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\Connection\Support;

use InitORM\DBAL\Connection\Support\QueryLogger;
use PHPUnit\Framework\TestCase;

final class QueryLoggerTest extends TestCase
{
    public function test_does_not_record_when_disabled(): void
    {
        $logger = new QueryLogger(false);
        $logger->add('SELECT 1', null, microtime(true));

        self::assertSame([], $logger->all());
    }

    public function test_records_when_enabled(): void
    {
        $logger = new QueryLogger(true);
        $logger->add('SELECT 1', ['id' => 7], microtime(true) - 0.01);

        $logs = $logger->all();
        self::assertCount(1, $logs);
        self::assertSame('SELECT 1', $logs[0]['query']);
        self::assertSame(['id' => 7], $logs[0]['args']);
        self::assertGreaterThan(0.0, $logs[0]['timer']);
    }

    public function test_enable_toggles_recording_at_runtime(): void
    {
        $logger = new QueryLogger(false);
        $logger->add('A', null, microtime(true));
        $logger->enable(true);
        $logger->add('B', null, microtime(true));

        $queries = array_column($logger->all(), 'query');
        self::assertSame(['B'], $queries);
    }

    public function test_clear_empties_the_buffer(): void
    {
        $logger = new QueryLogger(true);
        $logger->add('SELECT 1', null, microtime(true));
        $logger->clear();

        self::assertSame([], $logger->all());
    }

    public function test_isEnabled_reflects_constructor_argument(): void
    {
        self::assertFalse((new QueryLogger())->isEnabled());
        self::assertFalse((new QueryLogger(false))->isEnabled());
        self::assertTrue((new QueryLogger(true))->isEnabled());
    }

    public function test_add_with_null_start_time_records_zero_timer(): void
    {
        $logger = new QueryLogger(true);
        $logger->add('SELECT 1', null, null);

        self::assertSame(0.0, $logger->all()[0]['timer']);
    }
}
