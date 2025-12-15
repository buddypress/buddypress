<?php
/**
 * @group core
 * @group routing
 */
class BP_Tests_Routing_Core extends BP_UnitTestCase {
	protected $old_current_user = 0;
	protected $permalink_structure = '';

	public function set_up() {
		parent::set_up();

		$this->old_current_user = get_current_user_id();
		wp_set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		wp_set_current_user( $this->old_current_user );
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	public function test_wordpress_page() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( '/' );
		$this->assertEmpty( bp_current_component() );
	}

	/**
	 * @ticket BP9300
	 */
	public function test_buddypress_directory_is_home_false() {
		$this->set_permalink_structure( '/%postname%/' );

		$is_home_value = null;

		// Capture the is_home value during pre_get_posts for the main query.
		$callback = function ( $query ) use ( &$is_home_value ) {
			if ( $query->is_main_query() ) {
				$is_home_value = $query->is_home;
			}
		};

		add_action( 'pre_get_posts', $callback );

		$this->go_to( bp_get_members_directory_permalink() );

		remove_action( 'pre_get_posts', $callback );

		$this->assertFalse( $is_home_value, 'is_home should be false for a BuddyPress directory page on the main query.' );
	}
}
