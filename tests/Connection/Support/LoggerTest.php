<?php

declare(strict_types=1);

namespace InitORM\DBAL\Tests\Connection\Support;

use InitORM\DBAL\Connection\Support\Logger;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class LoggerTest extends TestCase
{
    public function test_returns_false_when_no_sink_is_configured(): void
    {
        self::assertFalse((new Logger())->write('boom'));
        self::assertFalse((new Logger(null))->write('boom'));
        self::assertFalse((new Logger(''))->write('boom'));
        self::assertFalse((new Logger(false))->write('boom'));
    }

    public function test_invokes_callable_sink_with_interpolated_message(): void
    {
        $captured = null;
        $logger   = new Logger(static function (string $message) use (&$captured): void {
            $captured = $message;
        });

        $logger->write('user {id} failed', ['id' => 7]);

        self::assertSame('user 7 failed', $captured);
    }

    public function test_invokes_psr3_logger_at_critical_level(): void
    {
        $sink = new class implements LoggerInterface {
            public string $template  = '';
            /** @var array<string, mixed> */
            public array $context = [];

            public function emergency($message, array $context = []): void
            {
            }
            public function alert($message, array $context = []): void
            {
            }
            public function critical($message, array $context = []): void
            {
                $this->template = (string) $message;
                $this->context  = $context;
            }
            public function error($message, array $context = []): void
            {
            }
            public function warning($message, array $context = []): void
            {
            }
            public function notice($message, array $context = []): void
            {
            }
            public function info($message, array $context = []): void
            {
            }
            public function debug($message, array $context = []): void
            {
            }
            public function log($level, $message, array $context = []): void
            {
            }
        };

        (new Logger($sink))->write('user {id} failed', ['id' => 99]);

        self::assertSame('user {id} failed', $sink->template);
        self::assertSame(['id' => 99], $sink->context);
    }

    public function test_invokes_duck_typed_critical_method(): void
    {
        $sink = new class {
            public string $last = '';
            public function critical(string $message): void
            {
                $this->last = $message;
            }
        };

        (new Logger($sink))->write('user {id} failed', ['id' => 1]);

        self::assertSame('user 1 failed', $sink->last);
    }

    public function test_writes_to_a_file_path_with_placeholders(): void
    {
        $dir  = sys_get_temp_dir() . '/dbal-logger-' . bin2hex(random_bytes(4));
        @mkdir($dir);
        $path = $dir . '/log-{date}.log';

        (new Logger($path))->write('first');
        (new Logger($path))->write('second');

        $resolved = strtr($path, ['{date}' => date('Y-m-d')]);
        self::assertFileExists($resolved);
        $contents = file_get_contents($resolved);
        self::assertNotFalse($contents);
        self::assertStringContainsString('first', $contents);
        self::assertStringContainsString('second', $contents);

        @unlink($resolved);
        @rmdir($dir);
    }

    public function test_interpolation_skips_array_and_object_context_values(): void
    {
        $captured = null;
        $logger   = new Logger(static function (string $message) use (&$captured): void {
            $captured = $message;
        });

        // Arrays and non-stringable objects must be ignored, not crash.
        $logger->write('a={a} b={b} c={c}', ['a' => 'x', 'b' => ['list'], 'c' => new \stdClass()]);

        self::assertSame('a=x b={b} c={c}', $captured);
    }
}
