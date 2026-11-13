<?php
/**
 * Tests for deprecated functions.
 *
 * @group core
 * @group deprecated
 */
class BP_Tests_Core_Deprecated extends BP_UnitTestCase {
	protected $bp_initial_version;

	public function override_initial_version() {
		return $this->bp_initial_version;
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9210
	 */
	public function test_bp_get_deprecated_functions_versions_array_is_consistent_with_deprecated_files() {
		$files = glob( buddypress()->plugin_dir . 'bp-core/deprecated/*.php' );
		$versions = array_map(
			function ( $file ) {
				return (float) basename( $file, '.php' );
			},
			$files
		);
		$listed = bp_get_deprecated_functions_versions();
		sort( $versions );
		sort( $listed );
		$this->assertNotEmpty( $versions );
		$this->assertSame( $versions, $listed );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9210
	 */
	public function test_deprecated_versions_do_not_depend_on_initial_version() {
		$expected = bp_get_deprecated_functions_versions();
		add_filter( 'pre_option__bp_initial_major_version', array( $this, 'override_initial_version' ), 10, 0 );
		foreach ( array( 0, 12, (float) bp_get_version() ) as $version ) {
			$this->bp_initial_version = $version;
			$this->assertSame( $expected, bp_get_deprecated_functions_versions() );
		}
		remove_filter( 'pre_option__bp_initial_major_version', array( $this, 'override_initial_version' ) );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9210
	 */
	public function test_all_deprecated_files_are_loaded() {
		$files    = glob( buddypress()->plugin_dir . 'bp-core/deprecated/*.php' );
		$included = get_included_files();

		$this->assertNotEmpty( $files );
		foreach ( $files as $file ) {
			$this->assertContains( realpath( $file ), $included );
		}
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9210
	 */
	public function test_legacy_mention_subject_filter_accepts_missing_poster_name() {
		$email = new BP_Email( 'activity-at-message' );
		$email->set_subject( 'Subject' );
		$this->assertSame( 'Subject', $email->get_subject() );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9210
	 * @expectedDeprecated bp_signup_allowed
	 */
	public function test_bp_signup_allowed_outputs_signup_status() {
		$this->expectOutputString( (string) bp_get_signup_allowed() );

		bp_signup_allowed();
	}

	/**
	 * @expectedDeprecated bp_core_users_by_letter_sql
	 * @expectedDeprecated bp_core_users_by_letter_count_sql
	 */
	public function test_get_users_by_letter_legacy_sql_arguments_and_canonical_precedence() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$legacy_args = array();

		$legacy      = static function( $sql, ...$args ) use ( &$legacy_args ) {
			$legacy_args[] = $args;

			return str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql ) . ' /* legacy */';
		};

		$canonical = function( $sql, $type ) {
			$this->assertStringContainsString( '/* legacy */', $sql );

			return 'count' === $type ? 'SELECT 42' : $sql;
		};

		add_filter( 'bp_core_users_by_letter_sql', $legacy, 10, 1 );
		add_filter( 'bp_core_users_by_letter_count_sql', $legacy, 10, 1 );

		try {
			$found = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 0, $found['total'] );

			add_filter( 'bp_core_users_by_letter_get_sql', $canonical, 10, 2 );
			$found = BP_Core_User::get_users_by_letter( 'F', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 42, $found['total'] );
			$this->assertCount( 4, $legacy_args );
			$this->assertSame( array(), $legacy_args[0] );
			$this->assertSame( array(), $legacy_args[1] );
		} finally {
			remove_filter( 'bp_core_users_by_letter_get_sql', $canonical );
			remove_filter( 'bp_core_users_by_letter_sql', $legacy );
			remove_filter( 'bp_core_users_by_letter_count_sql', $legacy );
		}
	}

	/**
	 * @expectedDeprecated bp_core_search_users_sql
	 * @expectedDeprecated bp_core_search_users_count_sql
	 */
	public function test_search_users_legacy_sql_arguments_and_canonical_precedence() {
		$first  = self::factory()->user->create( array( 'display_name' => 'Formula Ada' ) );
		$second = self::factory()->user->create( array( 'display_name' => 'Formula Bea' ) );
		$legacy_args = array();

		$legacy      = static function( $sql, ...$args ) use ( &$legacy_args ) {
			$legacy_args[] = $args;

			return str_replace( 'WHERE', 'WHERE 1 = 0 AND', $sql ) . ' /* legacy */';
		};

		$canonical = function( $sql, $type ) {
			$this->assertStringContainsString( '/* legacy */', $sql );

			return 'count' === $type ? 'SELECT 42' : $sql;
		};

		add_filter( 'bp_core_search_users_sql', $legacy, 10, 3 );
		add_filter( 'bp_core_search_users_count_sql', $legacy, 10, 3 );

		try {
			$found = BP_Core_User::search_users( 'Formula', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 0, $found['total'] );

			add_filter( 'bp_core_users_search_get_sql', $canonical, 10, 2 );
			$found = BP_Core_User::search_users( 'Formula', 2, 1, false );

			$this->assertSame( array(), $found['users'] );
			$this->assertSame( 42, $found['total'] );
			$this->assertCount( 4, $legacy_args );
			$this->assertSame( array( 'Formula' ), $legacy_args[0] );
			$this->assertSame( array( 'Formula', ' LIMIT 0, 2' ), $legacy_args[1] );
		} finally {
			remove_filter( 'bp_core_users_search_get_sql', $canonical );
			remove_filter( 'bp_core_search_users_sql', $legacy );
			remove_filter( 'bp_core_search_users_count_sql', $legacy );
		}
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_exclude_querystring() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$exclude_qs = $u1 . ',junkstring,' . $u3;

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, false, false, true, $exclude_qs );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u2 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_exclude_array() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create();
		$u2 = self::factory()->user->create();
		$u3 = self::factory()->user->create();

		$exclude_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, false, false, true, $exclude_array );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u2 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_include_querystring() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );

		$include_qs = $u1 . ',junkstring,' . $u3;

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, $include_qs );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u1, $u3 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_get_users_with_include_array() {
		add_filter( 'bp_use_legacy_user_query', '__return_true' );

		$u1 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s' ),
		) );
		self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 1000 ),
		) );
		$u3 = self::factory()->user->create( array(
			'last_activity' => gmdate( 'Y-m-d H:i:s', time() - 50 ),
		) );


		$include_array = array(
			$u1,
			'junkstring',
			$u3,
		);

		$users = BP_Core_User::get_users( 'active', 0, 1, 0, $include_array );
		$user_ids = wp_list_pluck( $users['users'], 'id' );

		remove_filter( 'bp_use_legacy_user_query', '__return_true' );

		$this->assertSame( array( $u1, $u3 ), $user_ids );
	}

	/**
	 * @expectedDeprecated BP_Core_User::get_users
	 */
	public function test_type_alphabetical() {
		$u1 = self::factory()->user->create( array(
			'display_name' => 'foo',
		) );
		$u2 = self::factory()->user->create( array(
			'display_name' => 'bar',
		) );

		global $wpdb;

		$q = BP_Core_User::get_users( 'alphabetical' );
		$found = wp_list_pluck( $q['users'], 'id' );

		$this->assertSame( array( $u2, $u1 ), $found );
	}

	/**
	 * @expectedDeprecated bp_invitations_get_paged_invitations_sql
	 */
	public function test_get_invitations_should_apply_deprecated_paged_sql_filter() {
		$user_id       = self::factory()->user->create();
		$invites_class = new BPTest_Invitation_Manager_Extension();
		$invitation_id = $invites_class->add_invitation(
			array(
				'user_id'     => $user_id,
				'inviter_id'  => self::factory()->user->create(),
				'item_id'     => 1,
				'send_invite' => 'sent',
			)
		);

		$filter = static function ( $sql ) use ( $invitation_id ) {
			return str_replace( 'WHERE ', "WHERE id = {$invitation_id} AND ", $sql );
		};

		add_filter( 'bp_invitations_get_paged_invitations_sql', $filter );

		try {
			$invitation_ids = BP_Invitation::get(
				array(
					'cache_results' => false,
					'fields'        => 'ids',
				)
			);
		} finally {
			remove_filter( 'bp_invitations_get_paged_invitations_sql', $filter );
		}

		$this->assertSame( array( $invitation_id ), $invitation_ids );
	}

	/**
	 * @expectedDeprecated bp_optouts_get_paged_optouts_sql
	 */
	public function test_get_optouts_should_apply_deprecated_paged_sql_filter() {
		$optout_id = self::factory()->optout->create(
			array(
				'email_address' => 'one@wp.org',
				'user_id'       => self::factory()->user->create(),
			)
		);

		$filter = static function ( $sql ) use ( $optout_id ) {
			return str_replace( 'WHERE ', "WHERE id = {$optout_id} AND ", $sql );
		};

		add_filter( 'bp_optouts_get_paged_optouts_sql', $filter );

		try {
			$optout_ids = BP_Optout::get(
				array(
					'cache_results' => false,
					'fields'        => 'ids',
				)
			);
		} finally {
			remove_filter( 'bp_optouts_get_paged_optouts_sql', $filter );
		}

		$this->assertSame( array( $optout_id ), $optout_ids );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/7226
	 */
	public function test_bp_button_deprecated_args_should_still_render() {
		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => false,
			'must_be_logged_in' => false,
			'wrapper' => 'section',
			'wrapper_class' => 'section-class',
			'wrapper_id' => 'section-id',
			'link_href' => 'http://example.com',
			'link_class' => 'link-class',
			'link_id' => 'link-id',
			'link_rel' => 'nofollow',
			'link_title' => 'link-title'
		) );

		$this->assertStringContainsString( '<section ', $b->contents );
		$this->assertStringContainsString( 'class="section-class ', $b->contents );
		$this->assertStringContainsString( 'id="section-id"', $b->contents );
		$this->assertStringContainsString( 'href="http://example.com"', $b->contents );
		$this->assertStringContainsString( 'class="link-class"', $b->contents );
		$this->assertStringContainsString( 'id="link-id"', $b->contents );
		$this->assertStringContainsString( 'rel="nofollow"', $b->contents );
		$this->assertStringContainsString( 'title="link-title"', $b->contents );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/7226
	 */
	public function test_bp_button_new_element_attrs_have_precedence_over_deprecated_element_attrs() {
		$b = new BP_Button( array(
			'id' => 'foo',
			'component' => 'members',
			'block_self' => false,
			'must_be_logged_in' => false,
			'button_element' => 'button',
			'button_attr' => array(
				'class' => 'new-class',
			),
			'link_class' => 'old-class'
		) );

		$this->assertStringContainsString( '<button class="new-class"', $b->contents );
	}
}
