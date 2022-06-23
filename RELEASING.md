## Checklist for Releases

We practice [trunk based development](https://trunkbaseddevelopment.com) and
`main` is the branch we release from.

1. pull latest `main`
2. review "Unreleased" in [the changelog](./CHANGELOG.md) to decide if
   the release is a major, minor, or patch release `vX.Y.Z`
3. create a new branch `release/vX.Y.Z` matching the version name
4. update links and headings in [the changelog](./CHANGELOG.md) to reflect the new version
5. get the pull request reviewed
6. squash merge the changes
7. delete the new branch
8. tag the merge commit in `main`: `vX.Y.Z`
9. [create a matching GitHub release](https://github.com/SymplifyConversion/sst-sdk-php/releases/new)
