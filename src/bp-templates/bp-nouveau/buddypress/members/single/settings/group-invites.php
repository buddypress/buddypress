<?php
/**
 * BuddyPress - Members Settings ( Group Invites )
 *
 * @since 3.0.0
 * @version 8.0.0
 */
?>

<h2 class="screen-heading group-invites-screen">
	<?php _e( 'Group Invites', 'buddypress' ); ?>
</h2>

<?php
if ( 1 === bp_nouveau_groups_get_group_invites_setting() ) {
	 bp_nouveau_user_feedback( 'member-group-invites-friends-only' );
} else {
	 bp_nouveau_user_feedback( 'member-group-invites-all' );
}
?>


<form action="<?php echo esc_url( bp_displayed_user_domain() . bp_nouveau_get_component_slug( 'settings' ) . '/invites/' ); ?>" name="account-group-invites-form" id="account-group-invites-form" class="standard-form" method="post">

	<label for="account-group-invites-preferences">
		<input type="checkbox" name="account-group-invites-preferences" id="account-group-invites-preferences" value="1" <?php checked( 1, bp_nouveau_groups_get_group_invites_setting() ); ?>/>
			<?php esc_html_e( 'I want to restrict Group invites to my friends only.', 'buddypress' ); ?>
	</label>

	<?php bp_nouveau_submit_button( 'member-group-invites' ); ?>

</form>
