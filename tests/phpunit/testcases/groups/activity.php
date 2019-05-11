<?php

/**
 * @group groups
 * @group activity
 */
class BP_Tests_Groups_Activity extends BP_UnitTestCase {
	protected $groups_post_update_args;

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_created_group
	 */
	public function test_bp_groups_format_activity_action_created_group() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'created_group',
			'user_id' => $u,
			'item_id' => $g,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$g_obj = groups_get_group( $g );

		$expected = sprintf( __( '%s created the group %s', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $g_obj ) . '">' . $g_obj->name . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_joined_group
	 */
	public function test_bp_groups_format_activity_action_joined_group() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'joined_group',
			'user_id' => $u,
			'item_id' => $g,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$g_obj = groups_get_group( $g );

		$expected = sprintf( __( '%s joined the group %s', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $g_obj ) . '">' . $g_obj->name . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_no_change() {
		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
				'group_id'       => $group->id,
				'name'           => $group->name,
				'slug'           => $group->slug,
				'description'    => $group->description,
				'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertTrue( empty( $a['activities'] ) );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_notify_members_false() {
		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => 'Foo',
			'slug'           => $group->slug,
			'description'    => $group->description,
			'notify_members' => false,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertTrue( empty( $a['activities'] ) );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_name() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => 'Foo',
			'slug'           => $group->slug,
			'description'    => $group->description,
			'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( esc_html__( '%s changed the name of the group %s from "%s" to "%s"', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">Foo</a>', $group->name, 'Foo' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_description() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => $group->name,
			'slug'           => $group->slug,
			'description'    => 'Bar',
			'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( esc_html__( '%s changed the description of the group %s from "%s" to "%s"', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">' . $group->name . '</a>', $group->description, 'Bar' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_slug() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => $group->name,
			'slug'           => 'flaxen',
			'description'    => $group->description,
			'notify_members' => true,
		) );
		$new_group_details = groups_get_group( $group->id );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the permalink of the group %s.', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $new_group_details ) . '">' . $group->name . '</a>' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_details_updated
	 */
	public function test_bp_groups_format_activity_action_group_details_updated_with_updated_name_and_description() {
		$old_user = get_current_user_id();
		$u = self::factory()->user->create();
		$this->set_current_user( $u );

		$group = self::factory()->group->create_and_get();
		groups_edit_base_group_details( array(
			'group_id'       => $group->id,
			'name'           => 'Foo',
			'slug'           => $group->slug,
			'description'    => 'Bar',
			'notify_members' => true,
		) );

		$a = bp_activity_get( array(
			'component' => buddypress()->groups->id,
			'action' => 'group_details_updated',
			'item_id' => $group->id,
		) );

		$this->assertNotEmpty( $a['activities'] );

		$expected = sprintf( __( '%s changed the name and description of the group %s', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . bp_get_group_permalink( $group ) . '">Foo</a>' );
		$this->assertSame( $expected, $a['activities'][0]->action );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group activity_action
	 * @group bp_groups_format_activity_action_group_activity_update
	 */
	public function test_bp_groups_format_activity_action_group_activity_update() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();
		$a = self::factory()->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'activity_update',
			'user_id' => $u,
			'item_id' => $g,
		) );

		$a_obj = new BP_Activity_Activity( $a );
		$g_obj = groups_get_group( $g );

		$expected = sprintf( esc_html__( '%1$s posted an update in the group %2$s', 'buddypress' ), bp_core_get_userlink( $u ),  '<a href="' . esc_url( bp_get_group_permalink( $g_obj ) ) . '">' . esc_html( $g_obj->name ) . '</a>' );

		$this->assertSame( $expected, $a_obj->action );
	}

	/**
	 * @group groups_post_update
	 */
	public function test_groups_post_update() {
		$u = self::factory()->user->create();
		$g = self::factory()->group->create();

		// The user is a group member.
		groups_join_group( $g, $u );

		$activity_args = array(
			'content'    => 'Test group_post_update',
			'user_id'    => $u,
			'group_id'   => $g,
			'error_type' => 'wp_error',
		);

		add_filter( 'bp_before_groups_record_activity_parse_args', array( $this, 'groups_post_update_args' ), 10, 1 );

		groups_post_update( $activity_args );

		remove_filter( 'bp_before_groups_record_activity_parse_args', array( $this, 'groups_post_update_args' ), 10, 1 );

		$expected = array_merge( $activity_args, array( 'item_id' => $g ) );
		unset( $expected['group_id'] );

		$this->assertEquals( $expected, $this->groups_post_update_args );
	}

	/**
	 * @group groups_post_update
	 */
	public function test_groups_post_update_in_group() {
		$bp = buddypress();
		$u  = self::factory()->user->create();
		$g  = self::factory()->group->create();

		// The user is a group member.
		groups_join_group( $g, $u );

		$bp->groups->current_group = groups_get_group( $g );

		$activity_args = array(
			'content' => 'Test group_post_update in a group',
			'user_id' => $u,
		);

		$a = groups_post_update( $activity_args );
		$a_obj = new BP_Activity_Activity( $a );

		$this->assertSame( $a_obj->item_id, $g );
		$this->assertSame( $a_obj->component, 'groups' );

		unset( $bp->groups->current_group );
	}

	/**
	 * @group bp_activity_can_comment
	 */
	public function test_groups_activity_can_comment() {
		$old_user = get_current_user_id();
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$g = self::factory()->group->create();

		// User 1 is a group member, while user 2 isn't.
		groups_join_group( $g, $u1 );

		$a = self::factory()->activity->create( array(
			'component' => buddypress()->groups->id,
			'type' => 'created_group',
			'user_id' => $u1,
			'item_id' => $g,
		) );

		$this->set_current_user( $u1 );
		if ( bp_has_activities( array( 'in' => $a ) ) ) {
			while ( bp_activities() ) : bp_the_activity();
				// User 1 should be able to comment.
				$this->assertTrue( bp_activity_can_comment() );
			endwhile;
		}

		$this->set_current_user( $u2 );
		if ( bp_has_activities( array( 'in' => $a ) ) ) {
			while ( bp_activities() ) : bp_the_activity();
				// User 2 should not be able to comment.
				$this->assertFalse( bp_activity_can_comment() );
			endwhile;
		}

		$this->set_current_user( $old_user );
	}

	public function groups_post_update_args( $args = array() ) {
		$this->groups_post_update_args = array_intersect_key( $args, array(
			'content'    => true,
			'user_id'    => true,
			'item_id'    => true,
			'error_type' => true,
		) );

		return $args;
	}
}
