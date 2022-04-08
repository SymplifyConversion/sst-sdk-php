<?php

declare(strict_types = 1);

namespace Symplify\SSTSDK;

use Psr\Cache\CacheItemPoolInterface;

/**
 * A client SDK for Symplify Server-Side Testing.
 *
 * The client maintains configuration for server-side tests for a website. It
 * also provides functions for allocating variations and assigning visitor IDs.
 */
final class Client
{

    /** @var string $websiteID the ID of the website you run tests on */
    private string $websiteID;

    /** @var CacheItemPoolInterface a cache pool to keep SST configuration in */
    private CacheItemPoolInterface $cachePool;

    function __construct(string $websiteID, CacheItemPoolInterface $cachePool)
    {
        $this->websiteID = $websiteID;
        $this->cachePool = $cachePool;
    }

    public function hello(): string
    {
        $counter   = $this->cachePool->getItem('hello_counter');
        $prevCount = $counter->get();
        $nextCount = ($prevCount ?? 0) + 1;

        $counter->set($nextCount);

        if (!$this->cachePool->save($counter)) {
            return "could not persist cache update";
        }

        return "Hello $this->websiteID World ($nextCount)";
    }

}
