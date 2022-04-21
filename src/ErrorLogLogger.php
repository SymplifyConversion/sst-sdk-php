<?php

declare(strict_types=1);

namespace SymplifyConversion\SSTSDK;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

final class ErrorLogLogger extends AbstractLogger
{

    private const ANSI_RED = 31;
    private const ANSI_GREEN = 32;
    private const ANSI_YELLOW = 33;

    /** @var bool true if stderr stream is a TTY */
    private bool $isTTY;

    public function __construct()
    {
        $stderr      = fopen('php://stderr', 'w');
        $this->isTTY = stream_isatty($stderr);
        fclose($stderr);
    }

    public function log($level, $message, array $context = []): void // phpcs:ignore
    {
        $pre = $post = '';

        if ($this->isTTY) {
            $pre  = self::colorCode($level);
            $post = "\033[0m";
        }

        error_log($pre . sprintf("[%s] %s", strtoupper($level), self::interpolate($message, $context)) . $post);
    }

    static function colorCode(string $level): string
    {
        switch ($level) {
            case LogLevel::EMERGENCY:
            case LogLevel::ALERT:
            case LogLevel::CRITICAL:
            case LogLevel::ERROR:
                return "\033[" . self::ANSI_RED . "m";

            case LogLevel::WARNING:
                return "\033[" . self::ANSI_YELLOW . "m";

            case LogLevel::NOTICE:
            case LogLevel::INFO:
                return "\033[" . self::ANSI_GREEN . "m";
        }

        return '';
    }

    /**
     * Interpolate {substitutions} in $message with values from $context.
     *
     * @param array<mixed> $context
     */
    static function interpolate(string $message, array $context): string
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

}
