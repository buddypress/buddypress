<?php

class BP_Dtheme_Options_Page {
	function init() {
		add_action( 'admin_menu', array( 'Bp_Dtheme_Options_Page', 'add_options_page' ) );
	}

	function add_options_page() {
  		add_theme_page( __( 'Theme Options', 'buddypress' ), __( 'Theme Options', 'buddypress' ), 8, 'theme-options-page', array( 'BP_Dtheme_Options_Page', 'page' ) );
	}

	function page() {
		$bp_dtheme_options = get_option( 'bp_dtheme_options' );

		if ( isset( $_POST[ 'submit' ] ) ) {
			check_admin_referer( 'bpdtheme_options' );

			$bp_dtheme_options['show_on_frontpage'] = $_POST[ 'show_on_frontpage' ];

			update_option( 'bp_dtheme_options', $bp_dtheme_options );
		?>
			<div class="updated"><p><strong><?php _e( 'Settings saved.', 'buddypress' ); ?></strong></p></div>
		<?php
    	} ?>

		<div class="wrap">
		    <?php echo "<h2>" . __( 'Theme Options', 'buddypress' ) . "</h2>"; ?>

			<form name="options" method="post" action="">

				<table class="form-table">
					<tbody>
						<tr valign="top">
							<th scope="row"><?php _e( 'On front page show:', 'buddypress' ) ?></th>
							<td>
								<label><input id="bpdtheme_frontpage_blog" type="radio" name="show_on_frontpage" <?php if ( $bp_dtheme_options['show_on_frontpage'] == 'blog' || empty($bp_dtheme_options['show_on_frontpage']) ) echo 'checked="checked"'; ?> value="blog" /> <?php _e( 'Blog Posts', 'buddypress' ) ?></label><br />
								<label><input id="bpdtheme_frontpage_activity" type="radio" name="show_on_frontpage" <?php if ( $bp_dtheme_options['show_on_frontpage'] == 'activity' ) echo 'checked="checked"'; ?> value="activity" /> <?php _e( 'Activity Stream', 'buddypress' ) ?></label>
							</td>
						</tr>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" value="<?php esc_attr_e( 'Update Settings', 'buddypress' ) ?>" />
				</p>

				<?php wp_nonce_field( 'bpdtheme_options' ) ?>

			</form>
		</div><?php
	}
}
add_action( 'init', array( 'BP_Dtheme_Options_Page', 'init' ) );
