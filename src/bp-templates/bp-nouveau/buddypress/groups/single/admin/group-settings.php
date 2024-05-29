<?php
/**
 * BP Nouveau Group's edit settings template.
 *
 * @since 3.0.0
 * @version 3.2.0
 */
?>

<?php if ( bp_is_group_create() ) : ?>

	<h3 class="bp-screen-title creation-step-name">
		<?php esc_html_e( 'Select Group Settings', 'buddypress' ); ?>
	</h3>

<?php else : ?>

	<h2 class="bp-screen-title">
		<?php esc_html_e( 'Change Group Settings', 'buddypress' ); ?>
	</h2>

<?php endif; ?>

<div class="group-settings-selections">

	<fieldset class="radio group-status-type">
		<legend><?php esc_html_e( 'Privacy Options', 'buddypress' ); ?></legend>

		<label for="group-status-public">
			<input type="radio" name="group-status" id="group-status-public" value="public"<?php if ( 'public' === bp_get_new_group_status() || ! bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> aria-describedby="public-group-description" /> <?php esc_html_e( 'This is a public group', 'buddypress' ); ?>
		</label>

		<ul id="public-group-description">
			<li><?php esc_html_e( 'Any site member can join this group.', 'buddypress' ); ?></li>
			<li><?php esc_html_e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ); ?></li>
			<li><?php esc_html_e( 'Group content and activity will be visible to any site member.', 'buddypress' ); ?></li>
		</ul>

		<label for="group-status-private">
			<input type="radio" name="group-status" id="group-status-private" value="private"<?php if ( 'private' === bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> aria-describedby="private-group-description" /> <?php esc_html_e( 'This is a private group', 'buddypress' ); ?>
		</label>

		<ul id="private-group-description">
			<li><?php esc_html_e( 'Only people who request membership and are accepted can join the group.', 'buddypress' ); ?></li>
			<li><?php esc_html_e( 'This group will be listed in the groups directory and in search results.', 'buddypress' ); ?></li>
			<li><?php esc_html_e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ); ?></li>
		</ul>

		<label for="group-status-hidden">
			<input type="radio" name="group-status" id="group-status-hidden" value="hidden"<?php if ( 'hidden' === bp_get_new_group_status() ) { ?> checked="checked"<?php } ?> aria-describedby="hidden-group-description" /> <?php esc_html_e( 'This is a hidden group', 'buddypress' ); ?>
		</label>

		<ul id="hidden-group-description">
			<li><?php esc_html_e( 'Only people who are invited can join the group.', 'buddypress' ); ?></li>
			<li><?php esc_html_e( 'This group will not be listed in the groups directory or search results.', 'buddypress' ); ?></li>
			<li><?php esc_html_e( 'Group content and activity will only be visible to members of the group.', 'buddypress' ); ?></li>
		</ul>

	</fieldset>

<?php
// Group type selection
$group_types = bp_groups_get_group_types( array( 'show_in_create_screen' => true ), 'objects' );
if ( $group_types ) : ?>

	<fieldset class="group-create-types">
		<legend><?php esc_html_e( 'Group Types', 'buddypress' ); ?></legend>

		<p tabindex="0"><?php esc_html_e( 'Select the types this group should be a part of.', 'buddypress' ); ?></p>

		<?php foreach ( $group_types as $type ) : ?>
			<div class="checkbox">
				<label for="<?php printf( 'group-type-%s', esc_attr( $type->name ) ); ?>">
					<input type="checkbox" name="group-types[]" id="<?php printf( 'group-type-%s', esc_attr( $type->name ) ); ?>" value="<?php echo esc_attr( $type->name ); ?>" <?php bp_nouveau_group_type_checked( $type ); ?>/> <?php echo esc_html( $type->labels['name'] ); ?>
					<?php
					if ( ! empty( $type->description ) ) {
						printf( '&ndash; %s', '<span class="bp-group-type-desc">' . esc_html( $type->description ) . '</span>' );
					}
					?>
				</label>
			</div>

		<?php endforeach; ?>

	</fieldset>

<?php endif; ?>

<?php if ( bp_is_active( 'groups', 'invitations' ) ): ?>

	<fieldset class="radio group-invitations">
		<legend><?php esc_html_e( 'Group Invitations', 'buddypress' ); ?></legend>

		<p tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to invite others?', 'buddypress' ); ?></p>

		<label for="group-invite-status-members">
			<input type="radio" name="group-invite-status" id="group-invite-status-members" value="members"<?php bp_group_show_invite_status_setting( 'members' ); ?> />
				<?php esc_html_e( 'All group members', 'buddypress' ); ?>
		</label>

		<label for="group-invite-status-mods">
			<input type="radio" name="group-invite-status" id="group-invite-status-mods" value="mods"<?php bp_group_show_invite_status_setting( 'mods' ); ?> />
				<?php esc_html_e( 'Group admins and mods only', 'buddypress' ); ?>
		</label>

		<label for="group-invite-status-admins">
			<input type="radio" name="group-invite-status" id="group-invite-status-admins" value="admins"<?php bp_group_show_invite_status_setting( 'admins' ); ?> />
				<?php esc_html_e( 'Group admins only', 'buddypress' ); ?>
		</label>

	</fieldset>

<?php endif; ?>

</div><!-- // .group-settings-selections -->
