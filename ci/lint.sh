#! /bin/sh

set -xe


find src -name '*.php' -print0 | xargs -0 -P4 -n1 php -l

composer phpcs

./vendor/bin/phpstan --memory-limit=256M

cd examples

find src -name '*.php' -print0 | xargs -0 -P4 -n1 php -l

# examples needs this because it's not in the default before_script
composer install

../vendor/bin/phpstan --memory-limit=256M
