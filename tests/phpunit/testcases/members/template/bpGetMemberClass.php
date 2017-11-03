<?php

/**
 * @group members
 */
class BP_Tests_Members_Template_BpGetMemberClass extends BP_UnitTestCase {
	/**
	 * @ticket BP6996
	 */
	public function test_should_contain_member_type_classes() {
		buddypress()->members->types = array();
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );

		$u = self::factory()->user->create();
		bp_set_member_type( $u, 'bar' );

		if ( bp_has_members( array( 'include' => array( $u ) ) ) ) {
			while ( bp_members() ) {
				bp_the_member();
				$found = bp_get_member_class();
			}
		}

		global $members_template;
		unset( $members_template );
		buddypress()->members->types = array();

		$this->assertContains( 'member-type-bar', $found );
		$this->assertNotContains( 'member-type-foo', $found );
	}
}
