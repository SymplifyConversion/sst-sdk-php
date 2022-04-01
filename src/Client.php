<?php

declare(strict_types = 1);

namespace Symplify\SSTSDK;

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

    function __construct(string $websiteID)
    {
        $this->websiteID = $websiteID;
    }

    public function hello(): string
    {
        return "Hello $this->websiteID World";
    }

}
