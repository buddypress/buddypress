<?php

/**
 * @group avatars
 * @group groups
 */
class BP_Tests_Groups_Avatars extends BP_UnitTestCase {
	/**
	 * @group bp_get_group_has_avatar
	 */
	public function test_bp_get_group_has_avatar_no_avatar_uploaded() {
		$g = self::factory()->group->create();
		$this->assertFalse( bp_get_group_has_avatar( $g ) );
	}

	/**
	 * @group bp_get_group_has_avatar
	 */
	public function test_bp_get_group_has_avatar_has_avatar_uploaded() {
		$g = self::factory()->group->create();

		// Fake it
		add_filter( 'bp_core_fetch_avatar_url', array( $this, 'avatar_cb' ) );

		$this->assertTrue( bp_get_group_has_avatar( $g ) );

		remove_filter( 'bp_core_fetch_avatar_url', array( $this, 'avatar_cb' ) );
	}

	public function avatar_cb() {
		return 'foo';
	}
}
