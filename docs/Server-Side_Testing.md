Symplify Server-Side Testing SDK
================================

https://symplify.com

For server-side testing, we provide SDKs for different platforms to be used in
your backend (web) servers to integrate A/B testing.

The server-side tests don't contain any customization code, only projects and
variations with IDs, names, and weights.

Architecture Overview
=====================

```mermaid
graph TD;
  Server -- poll SST config --> CDN;
  Dashboard -- update SST config --> CDN;
```

The SDK when initialized in your server keeps a small config in memory for the
active server-side tests on your website. You manage the tests in our dashboard,
and updates are published to our CDN.

The SDK periodically checks to ensure it has the current version of the config.

For each code path where you want to test different variations per visitor, you
need to ask the SDK for the variation allocation.

See the docs for each platform SDK for API specifics.

Visitor Allocation
==================

There is no need for any per-visitor storage, the allocation is idempotent.

It does depend on the variation weights in each project though, and distinct
visitor IDs. When you are running a test, know that adding or removing
variations or changing weights may change the variation a give visitor is
assigned. To ensure you don't have to keep track of visitor IDs in your code or
persistence, we integrate with HTTP cookies.

This is how we allocate a visitor ID:

1. Look at `sg_sst_vid` cookie, if it has a value: that is the visitor ID, and we are done.
2. If we didn't find an ID in our cookie, generate a new one and send it in the response cookie.

Variation Allocation
====================

This is how we allocate a variation within a visitor, for a given project:

1. If the project does not exist, return null
2. Iterate over all variations in the project:
   1. assign each variation a weight window from the current total weight to the same plus the variation weight
3. Use a "windowed" hash function to hash the string key "$visitor_id:$project_id" within the total weight of all variations
4. Get the variation assigned the window that matches the result form the hashing
