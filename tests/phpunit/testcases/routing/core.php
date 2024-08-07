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
		self::set_current_user( self::factory()->user->create( array( 'role' => 'subscriber' ) ) );
		$this->permalink_structure = get_option( 'permalink_structure', '' );
	}

	public function tear_down() {
		self::set_current_user( $this->old_current_user );
		$this->set_permalink_structure( $this->permalink_structure );

		parent::tear_down();
	}

	function test_wordpress_page() {
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( '/' );
		$this->assertEmpty( bp_current_component() );
	}
}
