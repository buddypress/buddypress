<?php
/**
 * BuddyPress Avatars main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with avatars.
 *
 * It's also used to create the common Backbone views.
 *
 * @since 2.3.0
 * @version 3.0.0
 */

/**
 * This action is for internal use, please do not use it
 */
do_action( 'bp_attachments_avatar_check_template' );
?>
<div class="bp-avatar-nav"></div>
<div class="bp-avatar"></div>
<div class="bp-avatar-status"></div>

<script type="text/html" id="tmpl-bp-avatar-nav">
	<a href="{{data.href}}" class="bp-avatar-nav-item" data-nav="{{data.id}}">{{data.name}}</a>
</script>

<?php bp_attachments_get_template_part( 'uploader' ); ?>

<?php bp_attachments_get_template_part( 'avatars/crop' ); ?>

<?php bp_attachments_get_template_part( 'avatars/camera' ); ?>

<script id="tmpl-bp-avatar-delete" type="text/html">
	<# if ( 'user' === data.object ) { #>
		<p><?php _e( "If you'd like to delete your current profile photo but not upload a new one, please use the delete profile photo button.", 'buddypress' ); ?></p>
		<button type="button" class="button edit" id="bp-delete-avatar"><?php esc_html_e( 'Delete My Profile Photo', 'buddypress' ); ?></button>
	<# } else if ( 'group' === data.object ) { #>
		<?php bp_nouveau_user_feedback( 'group-avatar-delete-info' ); ?>
		<button type="button" class="button edit" id="bp-delete-avatar"><?php esc_html_e( 'Delete Group Profile Photo', 'buddypress' ); ?></button>
	<# } else { #>
		<?php
			/**
			 * Fires inside the avatar delete frontend template markup if no other data.object condition is met.
			 *
			 * @since 3.0.0
			 */
			do_action( 'bp_attachments_avatar_delete_template' ); ?>
	<# } #>
</script>

<?php
	/**
	 * Fires after the avatar main frontend template markup.
	 *
	 * @since 3.0.0
	 */
	do_action( 'bp_attachments_avatar_main_template' ); ?>
