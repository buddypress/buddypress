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

// Execute the CLI entry point directly: including its shebang breaks strict_types on PHP 7.4.
$command = array_merge( array( PHP_BINARY, $entry ), $args );
$command = implode( ' ', array_map( 'escapeshellarg', $command ) );

passthru( $command, $exit_code );
exit( $exit_code );
