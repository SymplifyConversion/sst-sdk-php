## Setup

1. Clone this repository
2. Install git hooks
3. Run composer install
4. Run the test suite to verify things are working

```shell
$ git clone git@github.com:SymplifyConversion/sst-sdk-php.git
$ cd sst-sdk-php
$ cp ci/hook-pre-push.sh .git/hooks/pre-push
$ cp ci/hook-commit-msg.sh .git/hooks/commit-msg
$ composer install
$ ./ci/test.sh
```

## Checklist for Changes

1. pull latest `main`
2. create a new branch for your changes
3. write code and tests
4. add the change to [the changelog](./CHANGELOG.md)
5. get the pull request reviewed
6. squash merge the changes
7. delete the new branch

## Running CI locally

You can use [act](https://github.com/nektos/act) to execute the GitHub workflow
locally. It requires Docker.

```shell
$ act -P ubuntu-latest=shivammathur/node:latest
```

## Local Testing

The `examples` directory contains example scripts to show how to use the SDK,
but they are also a nice way to test locally during development.
They expect symplify-demoapp.localhost.test and fake-cdn.localhost.test to be
names for 127.0.0.1 in your hosts file.

```
# this starts php, serving the contents of examples, with some setup for the SDK
$ (cd examples; ./example-server.sh) &
$ curl http://symplify-demoapp.localhost.test:8910/WithCustomHttpClient.php
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52273 Accepted
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52274 Accepted
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52274 [INFO] ExamplesCDN: GET /4711/sstConfig.json
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52274 Closing
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52273 [200]: GET /WithCustomHttpClient.php
[Wed Apr 13 18:51:56 2022] 127.0.0.1:52273 Closing
 * discount
   - assigned variation: original

$ curl http://symplify-demoapp.localhost.test:8910/WithCustomHttpClient.php
[Wed Apr 13 18:51:58 2022] 127.0.0.1:52275 Accepted
[Wed Apr 13 18:51:58 2022] 127.0.0.1:52275 [200]: GET /WithCustomHttpClient.php
[Wed Apr 13 18:51:58 2022] 127.0.0.1:52275 Closing
 * discount
   - assigned variation: original

```

You can get stable variation allocations by configuring curl for cookies e.g.
```
curl --cookie cookiejar.txt --cookie-jar cookiejar.txt http://symplify-demoapp.localhost.test:8910/Hello.php
```

## Troubleshooting

If you get errors about classes not found when running tests, you might have lost the autoloader setup.
Run `composer install` again.
