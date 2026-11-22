<?php
/**
 * @group core
 */
class BP_Tests_Theme_Compatibility_Functions extends BP_UnitTestCase {

	protected $theme_features;

	public function set_up() {
		parent::set_up();

		// Negative cases require a theme without BuddyPress support.
		$this->theme_features = $GLOBALS['_wp_theme_features'];
		remove_theme_support( 'buddypress' );
	}

	public function tear_down() {
		$GLOBALS['_wp_theme_features'] = $this->theme_features;

		parent::tear_down();
	}

	public function test_bp_current_theme_doesnot_support() {
		$this->assertFalse( bp_current_theme_supports() );
	}

	public function test_bp_current_theme_does_support_buddypress() {
		add_theme_support( 'buddypress' );
		$this->assertTrue( bp_current_theme_supports() );
	}

	public function test_bp_current_theme_doesnot_support_buddypress_feature() {
		add_theme_support( 'buddypress' );
		$this->assertFalse( bp_current_theme_supports( array( 'activity' => 'feature' ) ) );
	}

	public function test_bp_current_theme_does_support_buddypress_feature() {
		add_theme_support(
			'buddypress',
			array(
				'activity'      => array( 'feature1', 'feature2' ),
			)
		);
		$this->assertTrue( bp_current_theme_supports( array( 'activity' => 'feature1' ) ) );
		$this->assertFalse( bp_current_theme_supports( array( 'activity' => 'feature3' ) ) );
		$this->assertFalse( bp_current_theme_supports( array( 'notifications' => '' ) ) );
		$this->assertTrue( bp_current_theme_supports() );
	}

	/**
	 * @expectedIncorrectUsage bp_current_theme_supports
	 */
	public function test_bp_current_theme_support_incorrect_usage() {
		add_theme_support(
			'buddypress',
			array(
				'activity'      => array( 'feature1', 'feature2' ),
				'notifications' => array( 'feature3' ),
			)
		);

		$this->assertFalse( bp_current_theme_supports( array( 'activity' => array( 'feature1', 'feature2' ) ) ) );
		$this->assertFalse(
			bp_current_theme_supports(
				array(
					'activity'      => 'feature1',
					'notifications' => 'feature3',
				)
			)
		);
	}
}
