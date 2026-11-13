<?php
/**
 * Selects PHPUnit configuration without changing the test runner or its arguments.
 *
 * @since 15.0.0
 */
class BP_PHPUnit_Runner {
	/**
	 * Selects the configuration and permits intentional skips on PHPUnit 10.
	 *
	 * @since 15.0.0
	 *
	 * @param int    $major     Installed PHPUnit major version.
	 * @param array  $args      User-supplied command-line arguments.
	 * @param bool   $multisite Whether the environment requests multisite.
	 * @param string $root      Optional project root for default configuration paths.
	 * @return array Arguments for the official PHPUnit executable.
	 * @throws RuntimeException For an unsupported or unexpected runner version.
	 */
	public static function get_arguments( $major, array $args, $multisite = false, $root = '' ) {
		if ( ! in_array( $major, array( 9, 10 ), true ) ) {
			throw new RuntimeException( 'BuddyPress tests require PHPUnit 9 or 10.' );
		}

		$coverage      = false;
		$configuration = false;
		$forwarded     = array();
		$end_options   = false;
		$needs_value   = false;

		// Do not interpret a PHPUnit option's value as a BuddyPress option.
		$value_options = array(
			'-c', '-d', '--configuration', '--bootstrap', '--include-path',
			'--filter', '--group', '--exclude-group', '--testsuite', '--test-suffix',
			'--order-by', '--random-order-seed', '--repeat', '--printer', '--loader',
			'--cache-directory', '--cache-result-file', '--coverage-filter',
			'--coverage-clover', '--coverage-cobertura', '--coverage-crap4j',
			'--coverage-html', '--coverage-php', '--coverage-xml',
			'--log-junit', '--log-teamcity', '--testdox-html', '--testdox-text',
			'--testdox-xml', '--list-tests-xml', '--extensions', '--testdox-group',
			'--testdox-exclude-group', '--atleast-version',
		);

		foreach ( $args as $arg ) {
			if ( $needs_value ) {
				$forwarded[] = $arg;
				$needs_value = false;
				continue;
			}
			if ( '--' === $arg ) {
				$end_options = true;
			}

			if ( ! $end_options ) {
				if ( '--multisite' === $arg ) {
					$multisite = true;
					continue;
				}
				if ( '--coverage' === $arg ) {
					$coverage = true;
					continue;
				}
				if ( 0 === strpos( $arg, '--phpunit-major=' ) ) {
					$expected = substr( $arg, strlen( '--phpunit-major=' ) );
					if ( ! in_array( $expected, array( '9', '10' ), true ) || (int) $expected !== $major ) {
						throw new RuntimeException( 'The requested PHPUnit major does not match the installed version. Update Composer dependencies.' );
					}
					continue;
				}
				if ( '--configuration' === $arg || 0 === strpos( $arg, '--configuration=' ) || 0 === strpos( $arg, '-c' ) ) {
					$configuration = true;
				}
			}
			$forwarded[] = $arg;
			$needs_value = ! $end_options && in_array( $arg, $value_options, true );
		}

		$selected = array();
		if ( ! $configuration ) {
			if ( $coverage ) {
				$file = 10 === $major ? 'tests/phpunit/codecoverage10.xml' : 'tests/phpunit/codecoverage.xml';
			} elseif ( $multisite ) {
				$file = 10 === $major ? 'tests/phpunit/multisite10.xml' : 'tests/phpunit/multisite.xml';
			} else {
				$file = 10 === $major ? 'phpunit10.xml.dist' : 'phpunit.xml.dist';
			}
			$selected = array( '--configuration', $root ? rtrim( $root, '/\\' ) . '/' . $file : $file );
		}

		// XML failOnAllIssues includes skips; the CLI exception takes precedence.
		if ( 10 === $major ) {
			$selected[] = '--do-not-fail-on-skipped';
		}

		return array_merge( $selected, $forwarded );
	}
}
