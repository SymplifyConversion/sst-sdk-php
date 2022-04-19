<?php

declare(strict_types=1);

namespace Symplify\SSTSDK;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class ErrorLogLogger extends AbstractLogger
{

    /** @var bool true if stderr stream is a TTY */
    private bool $isTTY;

    public function __construct()
    {
        $stderr = fopen('php://stderr', 'w');
        $this->isTTY = stream_isatty($stderr);
        fclose($stderr);
    }

    public function log($level, $message, array $context = []): void // phpcs:ignore
    {
        $pre = $post = '';

        if ($this->isTTY) {
            $pre  = colorCode($level);
            $post = "\033[0m";
        }

        error_log($pre . sprintf("[%s] %s", strtoupper($level), interpolate($message, $context)) . $post);
    }

}

const ANSI_BOLD   = 1;
const ANSI_RED    = 31;
const ANSI_GREEN  = 32;
const ANSI_YELLOW = 33;

function colorCode(string $level): string
{
    switch ($level) {
        case LogLevel::EMERGENCY:
        case LogLevel::ALERT:
        case LogLevel::CRITICAL:
        case LogLevel::ERROR:
            return "\033[" . ANSI_RED . "m";

        case LogLevel::WARNING:
            return "\033[" . ANSI_YELLOW . "m";

        case LogLevel::NOTICE:
        case LogLevel::INFO:
            return "\033[" . ANSI_GREEN . "m";
    }

    return '';
}

/**
 * Interpolate {substitutions} in $message with values from $context.
 *
 * @param array<mixed> $context
 */
function interpolate(string $message, array $context): string
{
    $replace = [];

    foreach ($context as $key => $val) {
        if (is_array($val)) {
            continue;
        }

        if (is_object($val) && !method_exists($val, '__toString')) {
            continue;
        }

        $replace['{' . $key . '}'] = strval($val);
    }

    return strtr($message, $replace);
}
