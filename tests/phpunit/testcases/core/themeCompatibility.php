<?php
/**
 * @group core
 * @group core-theme-compatibility
 */
class BP_Tests_Theme_Compatibility_Functions extends BP_UnitTestCase {

	/**
	 * @group bp_current_theme_supports
	 */
	public function test_bp_current_theme_doesnot_support() {
		$this->assertFalse( bp_current_theme_supports() );
	}

	/**
	 * @group bp_current_theme_supports
	 */
	public function test_bp_current_theme_does_support_buddypress() {
		add_theme_support( 'buddypress' );
		$this->assertTrue( bp_current_theme_supports() );
		remove_theme_support( 'buddypress' );
	}

	/**
	 * @group bp_current_theme_supports
	 */
	public function test_bp_current_theme_doesnot_support_buddypress_feature() {
		add_theme_support( 'buddypress' );
		$this->assertFalse( bp_current_theme_supports( array( 'activity' => 'feature' ) ) );
		remove_theme_support( 'buddypress' );
	}

	/**
	 * @group bp_current_theme_supports
	 */
	public function test_bp_current_theme_does_support_buddypress_feature() {
		add_theme_support(
			'buddypress',
			array(
				'activity' => array( 'feature1', 'feature2' ),
			)
		);
		$this->assertTrue( bp_current_theme_supports( array( 'activity' => 'feature1' ) ) );
		$this->assertFalse( bp_current_theme_supports( array( 'activity' => 'feature3' ) ) );
		$this->assertFalse( bp_current_theme_supports( array( 'notifications' => '' ) ) );
		$this->assertTrue( bp_current_theme_supports() );

		remove_theme_support( 'buddypress' );
	}
}
