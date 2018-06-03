<?php
/**
 * BuddyPress Avatars camera template.
 *
 * This template is used to create the camera Backbone views.
 *
 * @since 2.3.0
 * @version 3.1.0
 */

?>
<script id="tmpl-bp-avatar-webcam" type="text/html">
	<# if ( ! data.user_media ) { #>
		<div id="bp-webcam-message">
			<p class="warning"><?php esc_html_e( 'Your browser does not support this feature.', 'buddypress' ); ?></p>
		</div>
	<# } else { #>
		<div id="avatar-to-crop"></div>
		<div class="avatar-crop-management">
			<div id="avatar-crop-pane" class="avatar" style="width:{{data.w}}px; height:{{data.h}}px"></div>
			<div id="avatar-crop-actions">
				<button type="button" class="button avatar-webcam-capture"><?php echo esc_html_x( 'Capture', 'button', 'buddypress' ); ?></button>
				<button type="button" class="button avatar-webcam-save"><?php echo esc_html_x( 'Save', 'button', 'buddypress' ); ?></button>
			</div>
		</div>
	<# } #>
</script>
