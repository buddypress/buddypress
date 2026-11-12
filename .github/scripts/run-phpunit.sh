#!/usr/bin/env bash

# Run only in the disposable GitHub Actions database and WordPress installation.
set -euo pipefail

: "${WP_CORE_DIR:?}"
: "${WP_TESTS_DIR:?}"
: "${WP_VERSION:?}"
: "${WP_DB_HOST:?}"
: "${WP_DB_USER:?}"
: "${WP_DB_PASSWORD?}"
: "${TEST_SCRIPT:?}"

case "${OBJECT_CACHE:-}" in
	''|redis|memcached) ;;
	*) echo 'Unsupported object cache' >&2; exit 1 ;;
esac

case "$TEST_SCRIPT" in
	test|test_multi) ;;
	*) echo 'Unsupported Composer test script' >&2; exit 1 ;;
esac

# Use the same installer as the retired workflow, without VIP-specific plugins.
installer="${RUNNER_TEMP:?}/install-wp-tests.sh"
curl --fail --silent --show-error --location \
	'https://raw.githubusercontent.com/alleyinteractive/mantle-ci/5fd59131c942c192b2c7652de15a95f814ba2776/install-wp-tests.sh' \
	--output "$installer"
bash "$installer" wordpress_unit_tests "$WP_DB_USER" "$WP_DB_PASSWORD" \
	"$WP_DB_HOST" "$WP_VERSION" false false "${OBJECT_CACHE:-false}"

# Fail before running tests if installation silently loses core or cache coverage.
test -f "$WP_TESTS_DIR/includes/bootstrap.php"
if [[ -n "${OBJECT_CACHE:-}" ]]; then
	test -f "$WP_CORE_DIR/wp-content/object-cache.php"
else
	test ! -e "$WP_CORE_DIR/wp-content/object-cache.php"
fi

# The action's built-in setup assumes a wp-content project. BuddyPress is a plugin.
package_directory="$WP_CORE_DIR/wp-content/plugins/buddypress"
mkdir -p "$package_directory"
rsync -aWq --no-compress --exclude '.npm' --exclude '.git' \
	--exclude 'node_modules' ./ "$package_directory/"

cd "$package_directory"
test -f vendor/bin/phpunit
composer run "$TEST_SCRIPT"
