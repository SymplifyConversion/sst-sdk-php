<?php

declare(strict_types=1);

use Cache\Adapter\PHPArray\ArrayCachePool;
use PHPUnit\Framework\TestCase;
use Symplify\SSTSDK\Client;
use function PHPUnit\Framework\assertEquals;

final class ClientTest extends TestCase
{

    public function testCreateInvalidCDNBaseURL(): void
    {
        $this->expectException('InvalidArgumentException');

        $cachePool = new ArrayCachePool();
        new Client('4711', $cachePool, 'goober');
    }

    public function testGetConfigURLDefaultCDN(): void
    {
        $cachePool = new ArrayCachePool();
        $client    = new Client('4711', $cachePool);

        assertEquals('https://cdn-sitegainer.com/4711/sstConfig.json', $client->getConfigURL());
    }

    public function testGetConfigURLOverrideCDN(): void
    {
        $cachePool = new ArrayCachePool();
        $client    = new Client('1337', $cachePool, 'https://cdn.example.com');

        assertEquals('https://cdn.example.com/1337/sstConfig.json', $client->getConfigURL());
    }

    public function testGetConfigURLLocalhostCDN(): void
    {
        $cachePool = new ArrayCachePool();
        $client    = new Client('42', $cachePool, 'http://localhost:9000');

        assertEquals('http://localhost:9000/42/sstConfig.json', $client->getConfigURL());
    }

}
