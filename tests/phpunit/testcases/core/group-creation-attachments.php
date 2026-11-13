<?php
/**
 * Tests for attachments during group creation.
 *
 * @group attachments
 * @see https://buddypress.trac.wordpress.org/ticket/9019
 * @group groups
 */
class BP_Tests_Group_Creation_Attachments extends BP_UnitTestCase {
	private $permalink_structure;

	public function set_up() {
		parent::set_up();
		$this->permalink_structure = get_option( 'permalink_structure' );
	}

	private function go_to_creation_step( $step ) {
		$user = self::factory()->user->create();
		wp_set_current_user( $user );
		$group = self::factory()->group->create( array( 'creator_id' => $user ) );
		$_COOKIE['bp_new_group_id'] = $group;
		$this->set_permalink_structure( '/%postname%/' );
		$this->go_to( bp_groups_get_create_url( array( $step ) ) );
		return $group;
	}

	public function tear_down() {
		$this->set_permalink_structure( $this->permalink_structure );
		unset( $_COOKIE['bp_new_group_id'], buddypress()->groups->new_group_id );
		buddypress()->groups->current_group = null;
		parent::tear_down();
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9019
	 */
	public function test_avatar_script_data_during_group_creation() {
		$group = $this->go_to_creation_step( 'group-avatar' );
		$data  = ( new BP_Attachment_Avatar() )->script_data();
		$this->assertSame( array( 'object' => 'group', 'item_id' => $group ), wp_array_slice_assoc( $data['bp_params'], array( 'object', 'item_id' ) ) );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9019
	 */
	public function test_cover_image_script_data_during_group_creation() {
		$group = $this->go_to_creation_step( 'group-cover-image' );
		$data  = ( new BP_Attachment_Cover_Image() )->script_data();
		$this->assertSame(
			array( 'object' => 'group', 'item_id' => $group ),
			wp_array_slice_assoc( $data['bp_params'], array( 'object', 'item_id' ) )
		);
	}

	public function filter_cover_settings( $settings ) {
		$settings['components']   = array( 'groups' );
		$settings['callback']     = array( $this, 'cover_css' );
		$settings['theme_handle'] = 'bp-tests-cover-image';
		return $settings;
	}

	public function cover_css( $params ) {
		return sprintf( '#header-cover-image { /* %1$s %2$d */ }', $params['component'], $params['object_id'] );
	}

	/**
	 * @see https://buddypress.trac.wordpress.org/ticket/9019
	 */
	public function test_cover_image_inline_css_during_group_creation() {
		add_filter( 'bp_is_groups_cover_image_active', '__return_true' );
		add_filter( 'bp_after_groups_cover_image_settings_parse_args', array( $this, 'filter_cover_settings' ) );
		wp_register_style( 'bp-tests-cover-image', false, array(), bp_get_version() );

		try {
			$group = $this->go_to_creation_step( 'group-cover-image' );
			bp_add_cover_image_inline_css();
			$css = wp_styles()->get_data( 'bp-tests-cover-image', 'after' );
			$this->assertSame( array( '#header-cover-image { /* groups ' . $group . ' */ }' ), $css );
		} finally {
			wp_deregister_style( 'bp-tests-cover-image' );
			remove_filter( 'bp_after_groups_cover_image_settings_parse_args', array( $this, 'filter_cover_settings' ) );
			remove_filter( 'bp_is_groups_cover_image_active', '__return_true' );
		}
	}
}
