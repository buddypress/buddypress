<?php
/**
 * @group groups
 * @group routing
 */
class BP_Tests_Routing_Groups extends BP_UnitTestCase {
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		buddypress()->members->types = array();
		$this->permalink_structure = get_option( 'permalink_structure', '' );
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->set_permalink_structure( '/%postname%/' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		unset( $_COOKIE['bp_new_group_id'], buddypress()->groups->new_group_id );

		buddypress()->groups->current_group = null;

		parent::tear_down();
	}

	/**
	 * @ticket BP9330
	 * @dataProvider groups_homepage_urls
	 */
	public function test_groups_directory_as_homepage( $query_string ) {
		$this->set_permalink_structure( '/%postname%/' );

		$pages            = bp_core_get_directory_pages();
		$show_on_front    = get_option( 'show_on_front' );
		$page_on_front    = get_option( 'page_on_front' );
		$groups_page_id   = (int) $pages->groups->id;

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $groups_page_id );

		try {
			$this->go_to( home_url( '/' ) . $query_string );

			$this->assertTrue( bp_is_groups_directory(), 'The homepage should load the Groups directory.' );
			$this->assertFalse( is_404() );
			$this->assertSame( $groups_page_id, get_queried_object_id() );
		} finally {
			update_option( 'show_on_front', $show_on_front );
			update_option( 'page_on_front', $page_on_front );
		}
	}

	public static function groups_homepage_urls() {
		return array(
			'without query string' => array( '' ),
			'with query string'    => array( '?anything=value' ),
			'with tracking parameters' => array( '?utm_source=newsletter&ref=test' ),
			'with encoded parameter' => array( '?anything=hello%20world' ),
		);
	}


	public function test_member_groups() {
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_groups', bp_get_groups_slug() ),
				)
			)
		);
		$this->assertTrue( bp_is_user_groups() );
	}

	public function test_member_groups_invitations() {
		$this->go_to(
			bp_members_get_user_url(
				bp_loggedin_user_id(),
				array(
					'single_item_component' => bp_rewrites_get_slug( 'members', 'member_groups', bp_get_groups_slug() ),
					'single_item_action'    => bp_rewrites_get_slug( 'members', 'member_friends_invites', 'invites' ),
				)
			)
		);
		$this->assertTrue( bp_is_user_groups() && bp_is_current_action( 'invites' ) );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_with_type() {
		bp_groups_register_group_type(
			'foo',
			array(
				'has_directory' => true,
			)
		);

		$url = bp_get_groups_directory_url(
			array(
				'directory_type' => 'foo',
			)
		);

		$this->go_to( $url );

		$this->assertTrue( bp_is_groups_component() && ! bp_is_group() && bp_is_current_action( bp_get_groups_group_type_base() ) && bp_is_action_variable( 'foo', 0 ) );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_with_type_that_has_custom_directory_slug() {
		bp_groups_register_group_type( 'bar', array( 'has_directory' => 'bars' ) );

		$url = bp_get_groups_directory_url(
			array(
				'directory_type' => 'bars',
			)
		);

		$this->go_to( $url );

		$this->assertTrue( bp_is_groups_component() && ! bp_is_group() && bp_is_current_action( bp_get_groups_group_type_base() ) && bp_is_action_variable( 'bars', 0 ) );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_should_404_for_group_types_that_have_no_directory() {
		bp_groups_register_group_type( 'taz', array( 'has_directory' => false ) );

		$this->go_to(
			bp_get_groups_directory_url(
				array(
					'directory_type' => 'taz',
				)
			)
		);

		$this->assertEmpty( bp_get_current_group_directory_type() );
	}

	/**
	 * @group group_types
	 */
	public function test_group_directory_should_404_for_invalid_group_types() {
		$this->go_to(
			bp_get_groups_directory_url(
				array(
					'directory_type' => 'zat',
				)
			)
		);
		$this->assertEmpty( bp_get_current_group_directory_type() );
	}

	/**
	 * @group group_previous_slug
	 */
	public function test_group_previous_slug_current_slug_should_resolve() {
		$g1 = self::factory()->group->create(
			array(
				'slug' => 'george',
			)
		);

		groups_edit_base_group_details(
			array(
				'group_id' => $g1,
				'slug'     => 'ralph',
			)
		);

		$this->go_to( bp_get_group_url( $g1 ) );
		$this->assertSame( groups_get_current_group()->slug, 'ralph' );
	}

	/**
	 * @group group_previous_slug
	 */
	public function test_group_previous_slug_should_resolve() {
		$g1 = self::factory()->group->create(
			array(
				'slug' => 'george',
			)
		);

		groups_edit_base_group_details(
			array(
				'group_id'       => $g1,
				'slug'           => 'sam!',
				'notify_members' => false,
			)
		);

		$url = bp_rewrites_get_url(
			array(
				'component_id' => 'groups',
				'single_item'  => 'george',
			)
		);

		$this->go_to( $url );
		$this->assertSame( $g1, bp_get_current_group_id() );
	}

	/**
	 * @group group_previous_slug
	 */
	public function test_group_previous_slug_most_recent_takes_precedence() {
		$g1 = self::factory()->group->create(
			array(
				'slug' => 'george',
			)
		);

		groups_edit_base_group_details(
			array(
				'group_id'       => $g1,
				'slug'           => 'ralph',
				'notify_members' => false,
			)
		);

		$g2 = self::factory()->group->create(
			array(
				'slug' => 'george',
			)
		);

		groups_edit_base_group_details(
			array(
				'group_id'       => $g2,
				'slug'           => 'sam',
				'notify_members' => false,
			)
		);

		$url = bp_rewrites_get_url(
			array(
				'component_id' => 'groups',
				'single_item'  => 'george',
			)
		);

		$this->go_to( $url );
		$this->assertSame( $g2, bp_get_current_group_id() );
	}

	/**
	 * @ticket BP9019
	 * @dataProvider group_creation_steps
	 */
	public function test_bp_is_group_during_group_creation( $step, $resumed ) {
		$group_id = 0;
		if ( $resumed ) {
			$group_id = self::factory()->group->create();
			$_COOKIE['bp_new_group_id'] = $group_id;
		}

		$creation_steps = buddypress()->groups->group_creation_steps;
		if ( 'custom-step' === $step ) {
			buddypress()->groups->group_creation_steps[ $step ] = array(
				'name'     => 'Custom step',
				'position' => 100,
			);
		}

		$is_group = null;
		$callback = function () use ( &$is_group ) {
			if ( bp_is_group_create() ) {
				$is_group = bp_is_group();
			}
		};

		// The create action sets the current group on `bp_actions` at priority 10.
		add_action( 'bp_actions', $callback, 11 );

		try {
			$this->go_to( bp_groups_get_create_url( array( $step ) ) );
		} finally {
			remove_action( 'bp_actions', $callback, 11 );
			buddypress()->groups->group_creation_steps = $creation_steps;
		}

		$this->assertTrue( bp_is_group_create() );
		$this->assertSame( $step, bp_get_groups_current_create_step() );
		$this->assertSame( $group_id, bp_get_current_group_id() );
		$this->assertFalse( $is_group );
		$this->assertFalse( bp_is_group() );
	}

	public static function group_creation_steps() {
		return array(
			'details'     => array( 'group-details', false ),
			'settings'    => array( 'group-settings', true ),
			'photo'       => array( 'group-avatar', true ),
			'cover image' => array( 'group-cover-image', true ),
			'invite'      => array( 'group-invites', true ),
			'custom step' => array( 'custom-step', true ),
		);
	}

	/**
	 * @ticket BP9019
	 * @dataProvider non_single_group_pages
	 */
	public function test_bp_is_group_on_directory_and_non_group_pages( $page ) {
		if ( 'directory' === $page ) {
			$url = bp_get_groups_directory_url();
		} elseif ( 'member' === $page ) {
			$url = bp_members_get_user_url( bp_loggedin_user_id() );
		} else {
			$url = home_url( '/' );
		}

		$this->go_to( $url );

		if ( 'directory' === $page ) {
			$this->assertTrue( bp_is_groups_directory() );
		} elseif ( 'member' === $page ) {
			$this->assertTrue( bp_is_user() );
		} else {
			$this->assertFalse( bp_is_groups_component() );
		}

		$this->assertFalse( bp_is_group() );
	}

	public static function non_single_group_pages() {
		return array(
			'groups directory' => array( 'directory' ),
			'home'             => array( 'home' ),
			'member'           => array( 'member' ),
		);
	}

	/**
	 * @ticket BP9019
	 */
	public function test_bp_is_group_when_groups_are_inactive() {
		$active_components = buddypress()->active_components;

		try {
			unset( buddypress()->active_components['groups'] );
			$this->assertFalse( bp_is_active( 'groups' ) );
			$this->assertFalse( bp_is_group() );
		} finally {
			buddypress()->active_components = $active_components;
		}
	}

	/**
	 * @ticket BP9019
	 */
	public function test_bp_is_group_on_single_group_pages() {
		$g = self::factory()->group->create();

		$this->go_to( bp_get_group_url( $g ) );
		$this->assertTrue( bp_is_group() );

		$this->go_to( bp_get_group_url( $g, bp_groups_get_path_chunks( array( 'members' ) ) ) );
		$this->assertTrue( bp_is_group_members() );
		$this->assertTrue( bp_is_group() );

		$this->go_to( bp_get_group_manage_url( $g, bp_groups_get_path_chunks( array( 'edit-details' ), 'manage' ) ) );
		$this->assertTrue( bp_is_group_admin_page() );
		$this->assertTrue( bp_is_group() );
	}
}
