<?php
/**
 * @group members
 * @group routing
 * @group root_profiles
 */
class BP_Tests_Routing_Members_Root_Profiles extends BP_UnitTestCase {
	protected $u;
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		add_filter( 'bp_core_enable_root_profiles', '__return_true' );

		$uid = self::factory()->user->create( array(
			'user_login' => 'boone',
			'user_nicename' => 'boone',
		) );

		$this->u = new WP_User( $uid );

		wp_set_current_user( $uid );

		$this->permalink_structure = get_option( 'permalink_structure', '' );
		$this->set_permalink_structure( '/%postname%/' );
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );

		remove_filter( 'bp_core_enable_root_profiles', '__return_true' );

		parent::tear_down();
	}

	public function test_members_directory() {
		$this->go_to( home_url( bp_get_members_root_slug() ) );

		$pages        = bp_core_get_directory_pages();
		$component_id = bp_current_component();

		$this->assertSame( bp_get_members_root_slug(), $pages->{$component_id}->slug );
	}

	public function test_member_permalink() {
		$domain = home_url( $this->u->user_nicename );
		$this->go_to( $domain );

		$this->assertTrue( bp_is_user() );
		$this->assertTrue( bp_is_my_profile() );
		$this->assertSame( $this->u->ID, bp_displayed_user_id() );
	}

	/**
	 * @ticket BP6475
	 */
	public function test_member_permalink_when_members_page_is_nested_under_wp_page() {
		$p = self::factory()->post->create( array(
			'post_type' => 'page',
			'post_name' => 'foo',
		) );

		$members_page_id = bp_core_get_directory_page_id( 'members' );
		$original_parent = wp_get_post_parent_id( $members_page_id );
		$wp_rewrite      = $GLOBALS['wp_rewrite'];
		$original_rules  = array(
			'extra_rules'        => $wp_rewrite->extra_rules,
			'extra_rules_top'    => $wp_rewrite->extra_rules_top,
			'extra_permastructs' => $wp_rewrite->extra_permastructs,
		);

		try {
			wp_update_post( array(
				'ID'          => $members_page_id,
				'post_parent' => $p,
			) );

			// Rebuild routing after changing the hierarchy, before flushing rewrite rules.
			buddypress()->pages = bp_core_get_directory_pages();
			do_action( 'bp_setup_globals' );
			do_action( 'bp_add_rewrite_rules' );
			do_action( 'bp_add_permastructs' );
			$this->set_permalink_structure( '/%postname%/' );

			$nested_slug = get_post_field( 'post_name', $p ) . '/' . get_post_field( 'post_name', $members_page_id );
			$this->assertSame( $p, wp_get_post_parent_id( $members_page_id ) );
			$this->assertSame( $nested_slug, bp_core_get_directory_pages()->members->slug );
			$this->assertSame( $nested_slug, bp_get_members_root_slug() );

			$directory_url = bp_rewrites_get_url( array( 'component_id' => 'members' ) );
			$this->assertSame( user_trailingslashit( home_url( $nested_slug ) ), $directory_url );

			$this->go_to( $directory_url );
			$this->assertTrue( bp_is_members_directory() );

			$url = bp_members_get_user_url( $this->u->ID );
			$this->assertSame( user_trailingslashit( home_url( $this->u->user_nicename ) ), $url );

			$this->go_to( $url );
			$this->assertTrue( bp_is_user() );
			$this->assertTrue( bp_is_my_profile() );
			$this->assertSame( $this->u->ID, bp_displayed_user_id() );
		} finally {
			wp_update_post( array(
				'ID'          => $members_page_id,
				'post_parent' => $original_parent,
			) );
			buddypress()->pages = bp_core_get_directory_pages();
			do_action( 'bp_setup_globals' );

			foreach ( $original_rules as $property => $value ) {
				$wp_rewrite->{$property} = $value;
			}
		}
	}

	public function test_member_activity_page() {
		$url = home_url( $this->u->user_nicename ) . '/' . bp_get_activity_slug();
		$this->go_to( $url );

		$this->assertTrue( bp_is_user() );
		$this->assertTrue( bp_is_my_profile() );
		$this->assertSame( $this->u->ID, bp_displayed_user_id() );
		$this->assertTrue( bp_is_activity_component() );
	}
}
