<?php
/**
 * BuddyPress - Members Profile Change Cover Image
 *
 * @since 3.0.0
 * @version 3.0.0
 */

?>

<h2 class="screen-heading change-cover-image-screen"><?php _e( 'Change Cover Image', 'buddypress' ); ?></h2>

<?php bp_nouveau_member_hook( 'before', 'edit_cover_image' ); ?>

<p class="info bp-feedback">
	<span class="bp-icon" aria-hidden="true"></span>
	<span class="bp-help-text"><?php _e( 'Your Cover Image will be used to customize the header of your profile.', 'buddypress' ); ?></span>
</p>

<?php
// Load the cover image UI
bp_attachments_get_template_part( 'cover-images/index' );

bp_nouveau_member_hook( 'after', 'edit_cover_image' );
