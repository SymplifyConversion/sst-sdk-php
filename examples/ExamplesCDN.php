<?php

// This is just a simple HTTP file server.
// We need some control over headers when serving config JSON to our example
// servers, and the PHP builtin webserver does not give us that.
//
// No effort has been taken to make it secure, it just supports local testing.

declare(strict_types=1);

function log_message(string $level, string $message): void
{
    $remoteAddr = $_SERVER['REMOTE_ADDR'] . ':' . $_SERVER['REMOTE_PORT'];
    error_log("$remoteAddr [ExamplesCDN $level] $message");
}

function exitWith(int $status, string $message): void
{
    http_response_code($status);
    log_message('warn', $message, $status);
    echo $message;

    exit;
}

$requestMethod = $_SERVER["REQUEST_METHOD"];
$requestURI    = $_SERVER["REQUEST_URI"];

if (!in_array($requestMethod, ['GET', 'HEAD'])) {
    // we might need to support OPTIONS eventually though
    exitWith(405, "method '$requestMethod' not allowed");
}

// just trying to prevent mistakes in testing
if (strstr($requestURI, '..')) {
    exitWith(400, 'no directory traversal please');
}

$fileName = ltrim($requestURI, '/');

if (!file_exists($fileName)) {
    exitWith(404, "file not found: $fileName");
}

log_message('info', "$requestMethod $requestURI");

$contentType = mime_content_type($fileName);

header("Cache-Control: public, max-age=60");
header("Content-Type: $contentType");

echo file_get_contents($fileName);
