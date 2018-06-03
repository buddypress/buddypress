<?php
/**
 * BuddyPress Avatars crop template.
 *
 * This template is used to create the crop Backbone views.
 *
 * @since 2.3.0
 * @version 3.1.0
 */

?>
<script id="tmpl-bp-avatar-item" type="text/html">
	<div id="avatar-to-crop">
		<img src="{{data.url}}"/>
	</div>
	<div class="avatar-crop-management">
		<div id="avatar-crop-pane" class="avatar" style="width:{{data.full_w}}px; height:{{data.full_h}}px">
			<img src="{{data.url}}" id="avatar-crop-preview"/>
		</div>
		<div id="avatar-crop-actions">
			<button type="button" class="button avatar-crop-submit"><?php echo esc_html_x( 'Crop Image', 'button', 'buddypress' ); ?></button>
		</div>
	</div>
</script>
