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

# Nightly archives can lag behind trunk test helpers. Use one checkout for both.
install_core_tests="${WP_INSTALL_CORE_TEST_SUITE:-true}"
if [[ "$WP_VERSION" == trunk || "$WP_VERSION" == nightly ]]; then
	wordpress_source="${RUNNER_TEMP:?}/wordpress-develop"
	git clone --depth=1 --single-branch --branch trunk \
		https://github.com/WordPress/wordpress-develop.git "$wordpress_source"
	printf 'WordPress core and test revision: %s\n' "$(git -C "$wordpress_source" rev-parse HEAD)"

	# Reject existing installations rather than mixing snapshots.
	test ! -e "$WP_CORE_DIR"
	test ! -e "$WP_TESTS_DIR"
	mkdir -p "$WP_CORE_DIR" "$WP_TESTS_DIR"
	cp -R "$wordpress_source/src/." "$WP_CORE_DIR/"
	cp -R "$wordpress_source/tests/phpunit/includes" "$WP_TESTS_DIR/"
	cp -R "$wordpress_source/tests/phpunit/data" "$WP_TESTS_DIR/"

	# Retain the installer's database/cache setup, but skip its second test checkout.
	install_core_tests=false
fi

# Use the same installer as the retired workflow, without VIP-specific plugins.
installer="${RUNNER_TEMP:?}/install-wp-tests.sh"
curl --fail --silent --show-error --location \
	'https://raw.githubusercontent.com/alleyinteractive/mantle-ci/5fd59131c942c192b2c7652de15a95f814ba2776/install-wp-tests.sh' \
	--output "$installer"
WP_INSTALL_CORE_TEST_SUITE="$install_core_tests" bash "$installer" \
	wordpress_unit_tests "$WP_DB_USER" "$WP_DB_PASSWORD" \
	"$WP_DB_HOST" "$WP_VERSION" false false "${OBJECT_CACHE:-false}"

if [[ "$WP_VERSION" == trunk || "$WP_VERSION" == nightly ]]; then
	# Use the config sample from the same snapshot, with safely quoted PHP values.
	php /dev/stdin "$wordpress_source/wp-tests-config-sample.php" <<'PHP'
<?php
$sample = file_get_contents( $argv[1] );
$config = str_replace(
	array(
		"dirname( __FILE__ ) . '/src/'",
		"'youremptytestdbnamehere'",
		"'yourusernamehere'",
		"'yourpasswordhere'",
		"'localhost'",
	),
	array(
		var_export( getenv( 'WP_CORE_DIR' ) . '/', true ),
		"'wordpress_unit_tests'",
		var_export( getenv( 'WP_DB_USER' ), true ),
		var_export( getenv( 'WP_DB_PASSWORD' ), true ),
		var_export( getenv( 'WP_DB_HOST' ), true ),
	),
	$sample
);
foreach ( array( getenv( 'WP_TESTS_DIR' ), getenv( 'WP_CORE_DIR' ) ) as $directory ) {
	if ( false === file_put_contents( $directory . '/wp-tests-config.php', $config ) ) {
		exit( 1 );
	}
}
PHP
fi

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
