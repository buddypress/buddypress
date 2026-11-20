<?php

/**
 * @group xprofile
 * @group deprecated
 */
class BP_Tests_XProfile_Deprecated extends BP_UnitTestCase {
	/**
	 * @expectedDeprecated xprofile_field_additional_options
	 */
	public function test_render_admin_form_still_passes_the_field_to_the_legacy_action() {
		$field_id = self::factory()->xprofile_field->create(
			array( 'field_group_id' => self::factory()->xprofile_group->create() )
		);
		$field    = new BP_XProfile_Field( $field_id );
		$received = array();
		$record   = static function ( $rendered_field ) use ( &$received ) {
			$received[] = $rendered_field;
		};

		require_once buddypress()->plugin_dir . 'bp-xprofile/bp-xprofile-admin.php';

		$screen = get_current_screen();
		set_current_screen( 'users' );
		add_action( 'xprofile_field_additional_options', $record );
		ob_start();
		try {
			$field->render_admin_form();
		} finally {
			ob_end_clean();
			remove_action( 'xprofile_field_additional_options', $record );
			$GLOBALS['current_screen'] = $screen;
		}

		$this->assertSame( array( $field ), $received );
	}

}
