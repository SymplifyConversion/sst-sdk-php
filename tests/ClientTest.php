<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Symplify\SSTSDK\Client;
use Symplify\SSTSDK\Config\ClientConfig;
use function PHPUnit\Framework\assertEquals;

final class ClientTest extends TestCase
{

    public function testCreateInvalidCDNBaseURL(): void
    {
        $this->expectException('InvalidArgumentException');

        $cfg = (new ClientConfig('4711'))->withCdnBaseURL('cdn.example.com');
        new Client($cfg);
    }

    public function testGetConfigURLDefaultCDN(): void
    {
        $client = Client::withDefaults('4711');

        assertEquals('https://cdn-sitegainer.com/4711/sstConfig.json', $client->getConfigURL());
    }

    public function testGetConfigURLOverrideCDN(): void
    {
        $cfg    = (new ClientConfig('1337'))->withCdnBaseURL('https://cdn.example.com');
        $client = new Client($cfg);

        assertEquals('https://cdn.example.com/1337/sstConfig.json', $client->getConfigURL());
    }

    public function testGetConfigURLLocalhostCDN(): void
    {
        $cfg    = (new ClientConfig('42'))->withCdnBaseURL('http://localhost:9000');
        $client = new Client($cfg);

        assertEquals('http://localhost:9000/42/sstConfig.json', $client->getConfigURL());
    }

}
