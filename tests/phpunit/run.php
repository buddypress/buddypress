<?php
/**
 * Composer entry point for PHPUnit 9 and 10.
 *
 * @since 15.0.0
 */
$root     = dirname( __DIR__, 2 );
$autoload = $root . '/vendor/autoload.php';
$entry    = $root . '/vendor/phpunit/phpunit/phpunit';

if ( ! is_file( $autoload ) || ! is_file( $entry ) ) {
	fwrite( STDERR, "Install BuddyPress's Composer development dependencies before running tests.\n" );
	exit( 1 );
}

require_once $autoload;
require_once __DIR__ . '/includes/class-phpunit-runner.php';

try {
	$major     = (int) PHPUnit\Runner\Version::id();
	$multisite = filter_var( getenv( 'WP_MULTISITE' ), FILTER_VALIDATE_BOOLEAN );
	$args      = BP_PHPUnit_Runner::get_arguments( $major, array_slice( $_SERVER['argv'], 1 ), $multisite, $root );
} catch ( RuntimeException $error ) {
	fwrite( STDERR, $error->getMessage() . "\n" );
	exit( 1 );
}

$_SERVER['argv'] = array_merge( array( $entry ), $args );
$_SERVER['argc'] = count( $_SERVER['argv'] );

// Keep PHPUnit's own CLI/bootstrap and exit-code handling.
require $entry;
