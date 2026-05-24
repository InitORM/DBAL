<?php

declare(strict_types=1);

namespace InitORM\DBAL\Connection\Support;

use Psr\Log\LoggerInterface;
use Stringable;

/**
 * Strategy-based log writer.
 *
 * Resolves the user-supplied `log` credential at write time and dispatches
 * to the right backend:
 *
 * 1. `Psr\Log\LoggerInterface`           — logs at `critical` level.
 * 2. `callable`                          — invoked with the formatted message.
 * 3. `object` exposing a `critical()` method (duck-typed, kept for legacy
 *    callers that did not pull `psr/log` in).
 * 4. `string`                            — treated as a `file_put_contents`
 *    path with date/time placeholders (`{date}`, `{datetime}`, `{timestamp}`,
 *    `{year}`, `{month}`, `{day}`, `{hour}`, `{minute}`, `{second}`).
 *
 * Returns `false` when no sink is configured. Messages are interpolated with
 * PSR-3 style `{key}` placeholders.
 */
final class Logger
{
    /** @var mixed */
    private $sink;

    /**
     * @param mixed $sink
     */
    public function __construct($sink = null)
    {
        $this->sink = $sink;
    }

    /**
     * @param mixed $sink
     */
    public function setSink($sink): void
    {
        $this->sink = $sink;
    }

    /**
     * @param array<string, scalar|Stringable|null>|null $context
     */
    public function write(string $message, ?array $context = null): bool
    {
        if ($this->sink === null || $this->sink === '' || $this->sink === false) {
            return false;
        }

        if ($this->sink instanceof LoggerInterface) {
            $this->sink->critical($message, $context ?? []);
            return true;
        }

        $formatted = $context !== null && $context !== []
            ? $this->interpolate($message, $context)
            : $message;

        if (is_callable($this->sink)) {
            ($this->sink)($formatted);
            return true;
        }

        if (is_object($this->sink) && method_exists($this->sink, 'critical')) {
            $this->sink->critical($formatted);
            return true;
        }

        if (is_string($this->sink)) {
            return $this->writeToFile($this->sink, $formatted);
        }

        return false;
    }

    /**
     * @param array<string, scalar|Stringable|null> $context
     */
    private function interpolate(string $message, array $context): string
    {
        $replacements = [];
        foreach ($context as $key => $value) {
            if (is_array($value) || (is_object($value) && !$value instanceof Stringable)) {
                continue;
            }
            $replacements['{' . $key . '}'] = $value === null ? '' : (string) $value;
        }

        return strtr($message, $replacements);
    }

    private function writeToFile(string $template, string $message): bool
    {
        $path = strtr($template, [
            '{timestamp}' => (string) time(),
            '{date}'      => date('Y-m-d'),
            '{datetime}'  => date('Y-m-d-H-i-s'),
            '{year}'      => date('Y'),
            '{month}'     => date('m'),
            '{day}'       => date('d'),
            '{hour}'      => date('H'),
            '{minute}'    => date('i'),
            '{second}'    => date('s'),
        ]);

        $bytes = file_put_contents($path, $message . PHP_EOL, FILE_APPEND);

        return $bytes !== false;
    }
}
