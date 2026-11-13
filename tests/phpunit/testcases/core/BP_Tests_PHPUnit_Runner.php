<?php
/**
 * @group core
 * @group phpunit
 */
class BP_Tests_PHPUnit_Runner extends BP_UnitTestCase {
	/** @dataProvider configuration_cases */
	public function test_configuration_selection( $major, $args, $multisite, $expected ) {
		require_once BP_TESTS_DIR . '/includes/class-phpunit-runner.php';
		$this->assertSame( $expected, BP_PHPUnit_Runner::get_arguments( $major, $args, $multisite ) );
	}

	public static function configuration_cases() {
		return array(
			'legacy single'       => array( 9, array(), false, array( '--configuration', 'phpunit.xml.dist' ) ),
			'modern single'       => array( 10, array(), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped' ) ),
			'legacy multisite'    => array( 9, array( '--multisite' ), false, array( '--configuration', 'tests/phpunit/multisite.xml' ) ),
			'modern multisite'    => array( 10, array( '--multisite' ), false, array( '--configuration', 'tests/phpunit/multisite10.xml', '--do-not-fail-on-skipped' ) ),
			'multisite env'       => array( 10, array(), true, array( '--configuration', 'tests/phpunit/multisite10.xml', '--do-not-fail-on-skipped' ) ),
			'legacy coverage'     => array( 9, array( '--coverage' ), false, array( '--configuration', 'tests/phpunit/codecoverage.xml' ) ),
			'modern coverage'     => array( 10, array( '--coverage' ), false, array( '--configuration', 'tests/phpunit/codecoverage10.xml', '--do-not-fail-on-skipped' ) ),
			'forward filters'     => array( 10, array( '--group', 'friends', '--filter', 'test_member' ), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped', '--group', 'friends', '--filter', 'test_member' ) ),
			'explicit config'     => array( 10, array( '--configuration', 'custom.xml', '--group', 'core' ), false, array( '--do-not-fail-on-skipped', '--configuration', 'custom.xml', '--group', 'core' ) ),
			'explicit equals'     => array( 10, array( '--configuration=custom.xml' ), false, array( '--do-not-fail-on-skipped', '--configuration=custom.xml' ) ),
			'explicit short'      => array( 9, array( '-c', 'custom.xml' ), false, array( '-c', 'custom.xml' ) ),
			'explicit short join' => array( 10, array( '-ccustom.xml' ), false, array( '--do-not-fail-on-skipped', '-ccustom.xml' ) ),
			'expected major'      => array( 10, array( '--phpunit-major=10', '--filter', 'test_one' ), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped', '--filter', 'test_one' ) ),
			'end of options'      => array( 9, array( '--', '--multisite' ), false, array( '--configuration', 'phpunit.xml.dist', '--', '--multisite' ) ),
			'caller skip policy'  => array( 10, array( '--fail-on-skipped' ), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped', '--fail-on-skipped' ) ),
			'colors flag'         => array( 10, array( '--colors=never' ), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped', '--colors=never' ) ),
			'cache flag'          => array( 10, array( '--cache-result' ), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped', '--cache-result' ) ),
			'filter value'        => array( 10, array( '--filter', '-cExpression' ), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped', '--filter', '-cExpression' ) ),
			'option-like value'   => array( 10, array( '--filter', '--multisite' ), false, array( '--configuration', 'phpunit10.xml.dist', '--do-not-fail-on-skipped', '--filter', '--multisite' ) ),
		);
	}

	public function test_absolute_default_configuration() {
		require_once BP_TESTS_DIR . '/includes/class-phpunit-runner.php';
		$this->assertSame( array( '--configuration', '/project/phpunit.xml.dist' ), BP_PHPUnit_Runner::get_arguments( 9, array(), false, '/project/' ) );
	}

	public function test_mismatched_ci_runner_fails() {
		require_once BP_TESTS_DIR . '/includes/class-phpunit-runner.php';
		$this->expectException( RuntimeException::class );
		BP_PHPUnit_Runner::get_arguments( 9, array( '--phpunit-major=10' ) );
	}

	public function test_unsupported_major_fails() {
		require_once BP_TESTS_DIR . '/includes/class-phpunit-runner.php';
		$this->expectException( RuntimeException::class );
		BP_PHPUnit_Runner::get_arguments( 11, array() );
	}
}
