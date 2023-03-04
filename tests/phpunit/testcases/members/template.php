<?php
/**
 * @group members
 */
class BP_Tests_Members_Template extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	public function test_bp_has_members_include_on_user_page() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to( bp_members_get_user_url( $u1 ) );

		global $members_template;
		bp_has_members( array(
			'include' => array( $u1, $u2 ),
		) );

		$users = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$user_ids = wp_list_pluck( $users, 'ID' );
		sort( $user_ids );

		$shouldbe = array( $u1, $u2 );
		sort( $shouldbe );

		$this->assertEquals( $user_ids, $shouldbe );
	}

	/**
	 * @ticket BP5898
	 * @group bp_has_members
	 */
	public function test_bp_has_members_search_pagination_with_spaces() {
		$u1 = self::factory()->user->create( array( 'display_name' => '~ tilde u1' ) );
		$u2 = self::factory()->user->create( array( 'display_name' => '~ tilde u2' ) );

		$template_args = array(
			'search_terms' => '~ tilde',
			'per_page'     => 1,
		);

		global $members_template;
		$reset_members_template = $members_template;

		bp_has_members( $template_args );

		preg_match( '/&#038;members_search=(.*)(\'|\")/', $members_template->pag_links, $matches );

		$this->assertEquals( urldecode( $matches[1] ), urldecode( $template_args['search_terms'] ) );

		// reset the members template global
		$members_template = $reset_members_template;
	}

	public function test_bp_has_members_friendship_requests() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		friends_add_friend( $u1, $u2 );

		$old_user = get_current_user_id();
		$this->set_current_user( $u2 );
		$this->set_permalink_structure( '/%postname%/' );

		$this->go_to(
			bp_members_get_user_url(
				$u2,
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_friends', bp_get_friends_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_friends_requests', 'requests' ),
				)
			)
		);
		$this->restore_admins();

		global $members_template;
		bp_has_members( array(
			'include' => bp_get_friendship_requests( $u2 ),
		) );

		$requests = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$request_ids = wp_list_pluck( $requests, 'ID' );
		$this->assertEquals( $request_ids, array( $u1 ) );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_has_members
	 * @group friends
	 * @ticket BP5071
	 */
	public function test_bp_has_members_friendship_requests_with_no_requests() {
		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();

		$old_user = get_current_user_id();
		$this->set_current_user( $u2 );
		$this->set_permalink_structure( '/%postname%/' );

		// For some reason, in all the user switching, the cache gets
		// confused. Never comes up when BP runs normally, because the
		// loggedin_user doesn't change on a pageload. @todo Fix for
		// real in BP
		wp_cache_delete( 'bp_user_domain_' . $u2, 'bp' );

		$this->go_to(
			bp_members_get_user_url(
				$u2,
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_friends', bp_get_friends_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_friends_requests', 'requests' ),
				)
			)
		);
		$this->restore_admins();

		global $members_template;
		bp_has_members( array(
			'include' => bp_get_friendship_requests( $u2 ),
		) );

		$requests = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$request_ids = wp_list_pluck( $requests, 'ID' );
		$this->assertEquals( array(), $request_ids );

		$this->set_current_user( $old_user );
	}

	/**
	 * @group bp_has_members
	 */
	public function test_bp_has_members_should_pass_member_type_param_to_query() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = self::factory()->user->create_many( 3 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );

		global $members_template;
		$old_members_template = $members_template;

		bp_has_members( array(
			'member_type' => 'bar',
		) );

		$members = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$member_ids = wp_list_pluck( $members, 'ID' );
		$this->assertEquals( array( $users[1]), $member_ids );

		$GLOBALS['members_template'] = $old_members_template;
	}

	/**
	 * @group bp_has_members
	 * @ticket BP6286
	 */
	public function test_bp_has_members_should_infer_member_type_from_get_param() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		$users = self::factory()->user->create_many( 3 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );

		global $members_template;
		$old_members_template = $members_template;

		$old_get = $_GET;
		$_GET['member_type'] = 'bar';

		bp_has_members();

		$members = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$member_ids = wp_list_pluck( $members, 'ID' );
		$this->assertEquals( array( $users[1] ), $member_ids );

		$GLOBALS['members_template'] = $old_members_template;
	}

	/**
	 * @group bp_has_members
	 * @ticket BP6286
	 */
	public function test_bp_has_members_should_infer_member_type_from_get_param_comma_sep() {
		bp_register_member_type( 'foo' );
		bp_register_member_type( 'bar' );
		bp_register_member_type( 'baz' );
		$users = self::factory()->user->create_many( 4 );
		bp_set_member_type( $users[0], 'foo' );
		bp_set_member_type( $users[1], 'bar' );
		bp_set_member_type( $users[2], 'baz' );

		global $members_template;
		$old_members_template = $members_template;

		$old_get = $_GET;
		$_GET['member_type'] = 'foo,bar';

		bp_has_members();

		$members = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$member_ids = wp_list_pluck( $members, 'ID' );
		$this->assertEqualSets( array( $users[0], $users[1] ), $member_ids );

		$GLOBALS['members_template'] = $old_members_template;
	}

	/**
	 * @group bp_has_members
	 * @ticket BP8309
	 */
	public function test_bp_has_members_xprofile_query() {
		$group_id = self::factory()->xprofile_group->create();
		$field_id = self::factory()->xprofile_field->create( array(
			'field_group_id' => $group_id,
			'type'           => 'textbox',
		) );
		$users = self::factory()->user->create_many( 2 );

		xprofile_set_field_data( $field_id, $users[0], 'foo' );
		xprofile_set_field_data( $field_id, $users[1], 'bar' );

		global $members_template;
		$old_members_template = $members_template;

		$args = array(
			'xprofile_query' => array(
				array(
					'field' => $field_id,
					'value' => 'foo',
				),
			),
		);

		bp_has_members( $args );

		$members    = is_array( $members_template->members ) ? array_values( $members_template->members ) : array();
		$member_ids = wp_list_pluck( $members, 'ID' );
		$this->assertEqualSets( array( $users[0] ), $member_ids );

		$GLOBALS['members_template'] = $old_members_template;
	}


	/**
	 * @group bp_get_member_last_active
	 */
	public function test_bp_get_member_last_active_default_params() {
		// Fake the global
		global $members_template;

		$time = date( 'Y-m-d H:i:s', time() - 24*60*60 );
		$members_template = new stdClass;
		$members_template->member = new stdClass;
		$members_template->member->last_activity = $time;

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'Active %s', 'buddypress' ) ), bp_get_member_last_active() );
	}

	/**
	 * @group bp_get_member_last_active
	 */
	public function test_bp_get_member_last_active_active_format_true() {
		// Fake the global
		global $members_template;

		$time = date( 'Y-m-d H:i:s', time() - 24*60*60 );
		$members_template = new stdClass;
		$members_template->member = new stdClass;
		$members_template->member->last_activity = $time;

		$this->assertEquals( bp_core_get_last_activity( $time, __( 'Active %s', 'buddypress' ) ), bp_get_member_last_active( array( 'active_format' => true, ) ) );
	}

	/**
	 * @group bp_get_member_last_active
	 */
	public function test_bp_get_member_last_active_active_format_false() {
		// Fake the global
		global $members_template;

		$time = date( 'Y-m-d H:i:s', time() - 24*60*60 );
		$members_template = new stdClass;
		$members_template->member = new stdClass;
		$members_template->member->last_activity = $time;

		$this->assertEquals( bp_core_time_since( $time ), bp_get_member_last_active( array( 'active_format' => false, ) ) );
	}

	/**
	 * @group pagination
	 * @group BP_Core_Members_Template
	 */
	public function test_bp_groups_template_should_give_precedence_to_upage_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['upage'] = '5';

		$r = array(
			'type'            => 'active',
			'page'            => 8,
			'per_page'        => 20,
			'max'             => false,
			'page_arg'        => 'upage',
			'include'         => false,
			'exclude'         => false,
			'user_id'         => 0,
			'member_type'     => '',
			'search_terms'    => null,
			'meta_key'        => false,
			'meta_value'	  => false,
			'populate_extras' => true,
			'search_terms'    => ''
		);

		$at = new BP_Core_Members_Template( $r );

		$this->assertEquals( 5, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Core_Members_Template
	 */
	public function test_bp_members_template_should_reset_0_pag_page_URL_param_to_default_pag_page_value() {
		$request = $_REQUEST;
		$_REQUEST['upage'] = '0';

		$r = array(
			'type'            => 'active',
			'page'            => 8,
			'per_page'        => 20,
			'max'             => false,
			'page_arg'        => 'upage',
			'include'         => false,
			'exclude'         => false,
			'user_id'         => 0,
			'member_type'     => '',
			'search_terms'    => null,
			'meta_key'        => false,
			'meta_value'	  => false,
			'populate_extras' => true,
			'search_terms'    => ''
		);

		$at = new BP_Core_Members_Template( $r );

		$this->assertEquals( 8, $at->pag_page );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Core_Members_Template
	 */
	public function test_bp_members_template_should_give_precedence_to_num_URL_param() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '14';

		$r = array(
			'type'            => 'active',
			'page'            => 1,
			'per_page'        => 13,
			'max'             => false,
			'page_arg'        => 'upage',
			'include'         => false,
			'exclude'         => false,
			'user_id'         => 0,
			'member_type'     => '',
			'search_terms'    => null,
			'meta_key'        => false,
			'meta_value'	  => false,
			'populate_extras' => true,
			'search_terms'    => ''
		);

		$at = new BP_Core_Members_Template( $r );

		$this->assertEquals( 14, $at->pag_num );

		$_REQUEST = $request;
	}

	/**
	 * @group pagination
	 * @group BP_Core_Members_Template
	 */
	public function test_bp_members_template_should_reset_0_pag_num_URL_param_to_default_pag_num_value() {
		$request = $_REQUEST;
		$_REQUEST['num'] = '0';

		$r = array(
			'type'            => 'active',
			'page'            => 1,
			'per_page'        => 13,
			'max'             => false,
			'page_arg'        => 'upage',
			'include'         => false,
			'exclude'         => false,
			'user_id'         => 0,
			'member_type'     => '',
			'search_terms'    => null,
			'meta_key'        => false,
			'meta_value'	  => false,
			'populate_extras' => true,
			'search_terms'    => ''
		);

		$at = new BP_Core_Members_Template( $r );

		$this->assertEquals( 13, $at->pag_num );

		$_REQUEST = $request;
	}
}
