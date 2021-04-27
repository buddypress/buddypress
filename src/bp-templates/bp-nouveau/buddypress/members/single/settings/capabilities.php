<?php
/**
 * BuddyPress - Members Settings ( Capabilities )
 *
 * @since 3.0.0
 * @version 8.0.0
 */

bp_nouveau_member_hook( 'before', 'settings_template' ); ?>

<h2 class="screen-heading member-capabilities-screen">
	<?php esc_html_e( 'Members Capabilities', 'buddypress' ); ?>
</h2>

<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_nouveau_get_component_slug( 'settings' ) . '/capabilities/' ); ?>" name="account-capabilities-form" id="account-capabilities-form" class="standard-form" method="post">

	<label for="user-spammer">
		<input type="checkbox" name="user-spammer" id="user-spammer" value="1" <?php checked( bp_is_user_spammer( bp_displayed_user_id() ) ); ?> />
			<?php esc_html_e( 'This member is a spammer.', 'buddypress' ); ?>
	</label>

	<?php bp_nouveau_submit_button( 'member-capabilities' ); ?>

</form>

<?php
bp_nouveau_member_hook( 'after', 'settings_template' );
