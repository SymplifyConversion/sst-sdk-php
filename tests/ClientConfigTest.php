<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symplify\SSTSDK\Config\ClientConfig;
use function PHPUnit\Framework\assertEquals;
use function PHPUnit\Framework\assertNull;

final class ClientConfigTest extends TestCase
{

    public function testHasDefaults(): void
    {
        $cfg = new ClientConfig('goober');
        assertEquals('goober', $cfg->getWebsiteID());
        assertEquals('https://cdn-sitegainer.com', $cfg->getCdnBaseURL());
        assertEquals(1024 * 1024, $cfg->getMaxDownloadBytes());
        assertNull($cfg->getHttpClient());
        assertNull($cfg->getHttpRequests());
    }

    public function testIsImmutable(): void
    {
        $cfg = new ClientConfig('goober');
        $updated = $cfg->withCdnBaseURL('https://cdn.example.com');
        assertEquals('https://cdn.example.com', $updated->getCdnBaseURL());
        assertEquals('https://cdn-sitegainer.com', $cfg->getCdnBaseURL());
    }

}
