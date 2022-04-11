#!/bin/sh

set -e
set -u
set -x

if ! which php > /dev/null
then
  echo This example needs the php command
  exit 1
fi

EXAMPLE_SERVER_ADDR=localhost:8910
EXAMPLE_CDN_ADDR=localhost:8911

# SSTSDK_* variables are not special, they just happen to be used by example scripts
export SSTSDK_WEBSITE_ID=4711
export SSTSDK_CDN_BASEURL="http://$EXAMPLE_CDN_ADDR"

trap cleanup INT TERM

php -S "$EXAMPLE_SERVER_ADDR" &
pid_server=$!

php -S "$EXAMPLE_CDN_ADDR" &
pid_cdn=$!

cleanup() {
    kill $pid_server
    kill $pid_cdn
}

wait
