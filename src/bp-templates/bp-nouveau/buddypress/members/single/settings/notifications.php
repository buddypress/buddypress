<?php
/**
 * BuddyPress - Members Settings ( Notifications )
 *
 * @since 3.0.0
 * @version 12.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading email-settings-screen">
	<?php _e( 'Email Notifications', 'buddypress' ); ?>
</h2>

<p class="bp-help-text email-notifications-info">
	<?php _e( 'Set your email notification preferences.', 'buddypress' ); ?>
</p>

<form action="<?php bp_displayed_user_link( array( bp_nouveau_get_component_slug( 'settings' ), 'notifications' ) ); ?>" method="post" class="standard-form" id="settings-form">

	<?php bp_nouveau_member_email_notice_settings(); ?>

	<?php bp_nouveau_submit_button( 'member-notifications-settings' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
