<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK;

use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

/**
 * Wraps any logger to prefix each message.
 */
final class PrefixedLogger extends AbstractLogger
{

    /** @var string concat this string before all log messages */
    private string $prefix;

    /** @var LoggerInterface the actual logger */
    private LoggerInterface $wrapped;

    public function __construct(string $prefix, LoggerInterface $wrapped)
    {
        $this->prefix = $prefix;
        $this->wrapped = $wrapped;
    }

    public function log($level, $message, array $context = []): void // phpcs:ignore
    {
        $this->wrapped->log($level, $this->prefix . $message, $context);
    }

}
