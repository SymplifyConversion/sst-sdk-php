#! /bin/sh

set -xe

# git hooks run in the repository root unless the repository is bare

./ci/lint_repo.sh
./ci/lint_code.sh
./ci/test.sh
