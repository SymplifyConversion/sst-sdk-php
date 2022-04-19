<?php

declare(strict_types=1);

use Http\Mock\Client as MockClient;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Stream;
use PHPUnit\Framework\TestCase;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Symplify\SSTSDK\Client;
use Symplify\SSTSDK\Config\ClientConfig;
use function PHPUnit\Framework\assertEmpty;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNotCount;

final class LoggingTest extends TestCase
{

    public function testErrorLog(): void
    {
        $logSpy = new LogSpy(LogLevel::ERROR);

        $cfg = (new ClientConfig('4711'))
            ->withLogger($logSpy)
            ->withCdnBaseURL('https://localhost:10000');

        $sdk = new Client($cfg);

        $projects = $sdk->listProjects();

        assertEmpty($projects);

        assertNotCount(0, $logSpy->seenMessages);
        assertEquals('SSTSDK: listProjects called before config is available', $logSpy->seenMessages[0]);
    }

    public function testWarningLog(): void
    {
        $logSpy = new LogSpy(LogLevel::WARNING);

        $messageFactory = new Psr17Factory();

        $httpClient   = new MockClient();
        $jsonResponse = $messageFactory->createResponse(200)
            ->withBody(Stream::create('{"updated":0,"projects":[]}'));
        $httpClient->setDefaultResponse($jsonResponse);

        $cfg = (new ClientConfig('4711'))
            ->withLogger($logSpy)
            ->withHttpClient($httpClient)
            ->withHttpRequests($messageFactory)
            ->withCdnBaseURL('https://localhost:10000');

        $sdk = new Client($cfg);

        $sdk->loadConfig();

        $noVariation = $sdk->findVariation("goober");

        assertEmpty($noVariation);

        assertNotCount(0, $logSpy->seenMessages);
        assertEquals("SSTSDK: project does not exist: 'goober'", $logSpy->seenMessages[0]);
    }

}

final class LogSpy extends AbstractLogger
{

    /** @var array<string> */
    public array $seenMessages;

    private string $spyLevel;

    public function __construct(string $spyLevel)
    {
        $this->spyLevel     = $spyLevel;
        $this->seenMessages = [];
    }

    public function log($level, $message, array $context = array()) // phpcs:ignore
    {
        if ($level !== $this->spyLevel) {
            return;
        }

        $this->seenMessages[] = $message;
    }

}
