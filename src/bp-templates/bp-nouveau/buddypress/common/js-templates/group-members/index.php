<?php
/**
 * BP Nouveau single group's membership management main template.
 *
 * This template is used to inject the BuddyPress Backbone views
 * dealing with a group's membership management.
 *
 * @since 5.0.0
 * @version 10.0.0
 */

// Backward Compatibility for plugins still needing the placeholders to be located into this file.
if ( ! did_action( '_bp_groups_print_manage_group_members_placeholders' ) ) {
	/**
	 * Placeholders to inject elements of the UI
	 * to manage Group members.
	 *
	 * @since 5.0.0
	 */
	bp_groups_print_manage_group_members_placeholders();
}
?>

<script type="text/html" id="tmpl-bp-manage-members-updating">
	<# if ( ! data.type ) { #>
		<small><?php echo esc_html_x( 'Updating role... Please wait.', 'group manage members update feedback', 'buddypress' ); ?></small>
	<# } else if ( 'ban' === data.type ) { #>
		<small><?php echo esc_html_x( 'Banning member... Please wait.', 'group manage members ban feedback', 'buddypress' ); ?></small>
	<# } else if ( 'unban' === data.type ) { #>
		<small><?php echo esc_html_x( 'Unbanning member... Please wait.', 'group manage members unban feedback', 'buddypress' ); ?></small>
	<# } else if ( 'remove' === data.type ) { #>
		<small><?php echo esc_html_x( 'Removing member... Please wait.', 'group manage members remove feedback', 'buddypress' ); ?></small>
	<# } #>
</script>

<script type="text/html" id="tmpl-bp-manage-members-error">
   <small>{{data.message}}</small>
</script>

<script type="text/html" id="tmpl-bp-manage-members-header">
	<tr>
		<th><?php echo esc_html_x( 'Group Members', 'group manage members table header', 'buddypress' ); ?></th>
		<th><?php echo esc_html_x( 'Roles', 'group manage members table header', 'buddypress' ); ?></th>
	</tr>
</script>

<script type="text/html" id="tmpl-bp-manage-members-empty-row">
	<td colspan="2">
		<div class="bp-feedback info">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php esc_html_e( 'No Group members were found for this request.', 'buddypress' ); ?></p>
		</div>
	</td>
</script>

<script type="text/html" id="tmpl-bp-manage-members-label">
	<# if ( data.type && 'filter' !== data.type ) { #>
		<?php echo esc_html_x( 'Change role for:', 'group manage members row edit', 'buddypress' ); ?>
	<# } else { #>
		<?php echo esc_html_x( 'Filter:', 'group manage members roles filter', 'buddypress' ); ?></small>
	<# } #>
</script>

<script type="text/html" id="tmpl-bp-manage-members-row">
	<td class="uname-column">
		<div class="group-member">
			<a href="{{{data.link}}}">
				<img src="{{{data.avatar_urls.thumb}}}" alt="{{data.name}}" class="avatar profile-photo alignleft"/>
				{{data.name}}
			</a>
		</div>
		<div class="group-member-actions row-actions">
			<# if ( ! data.editing && ! data.is_banned ) { #>
				<span class="edit"><a href="#edit-role" data-action="edit"><?php echo esc_html_x( 'Edit', 'group member edit role link', 'buddypress' ); ?></a> | </span>
			<# } #>
			<# if ( data.editing ) { #>
				<span><a href="#edit-role-abort" data-action="abort"><?php echo esc_html_x( 'Stop editing', 'group member edit role abort link', 'buddypress' ); ?></a> | </span>
			<# } #>
			<# if ( ! data.is_banned ) { #>
				<span class="spam"><a href="#ban" class="submitdelete" data-action="ban"><?php echo esc_html_x( 'Ban', 'group member ban link', 'buddypress' ); ?></a> | </span>
			<# } else { #>
				<span class="ham"><a href="#unban" data-action="unban"><?php echo esc_html_x( 'Unban', 'group member unban link', 'buddypress' ); ?></a> | </span>
			<# } #>
			<span class="delete"><a href="#remove" class="submitdelete" data-action="remove"><?php echo esc_html_x( 'Remove', 'group member ban link', 'buddypress' ); ?></a></span>
		</div>
	</td>
	<td class="urole-column">
		<# if ( ! data.editing  && ! data.managingBan && ! data.removing ) { #>
			{{data.role.name}}
		<# } else { #>
			<div id="edit-group-member-{{data.id}}" class="group-member-edit"><?php // Placeholder for the Edit Role Dropdown. ;?></div>
		<# } #>
	</td>
</script>

<script type="text/html" id="tmpl-bp-manage-members-search">
	<?php
		$button_classes = array( 'bp-button', 'bp-search' );
		$screen_reader_class = 'bp-screen-reader-text';

		if ( is_admin() ) {
			$button_classes[]    = 'button-secondary';
			$screen_reader_class = 'screen-reader-text';
		}
	?>
	<label for="manage-members-search" class="<?php echo sanitize_html_class( $screen_reader_class ); ?>">
		<?php esc_html_e( 'Search Members', 'buddypress' ); ?>
	</label>
	<input type="search" id="manage-members-search" class="small" placeholder="<?php echo esc_attr_x( 'Search', 'search placeholder text', 'buddypress' ); ?>"/>
	<button type="submit" id="manage-members-search-submit" class="<?php echo join( ' ', array_map( 'sanitize_html_class', $button_classes ) ); ?>">
		<span class="dashicons dashicons-search" aria-hidden="true"></span>
		<span class="<?php echo sanitize_html_class( $screen_reader_class ); ?>"><?php echo esc_html_x( 'Search', 'button', 'buddypress' ); ?></span>
	</button>
</script>

<script type="text/html" id="tmpl-bp-manage-members-paginate">
	<?php
		$button_classes = array( 'group-members-paginate-button' );
		$screen_reader_class = 'bp-screen-reader-text';

		if ( is_admin() ) {
			$button_classes[]    = 'button-secondary';
			$screen_reader_class = 'screen-reader-text';
		}
	?>
	<# if ( ! isNaN( data.currentPage ) && ! isNaN( data.totalPages ) ) { #>
		<# if ( 1 !== data.currentPage && data.totalPages ) { #>
			<button class="<?php echo join( ' ', array_map( 'sanitize_html_class', $button_classes ) ); ?>" data-page="{{data.prevPage}}">
				<span class="dashicons dashicons-arrow-left"></span>
				<span class="<?php echo sanitize_html_class( $screen_reader_class ); ?>"><?php echo esc_html_x( 'Prev.', 'link', 'buddypress' ); ?></span>
			</button>
		<# } #>
		<# if ( data.totalPages !== data.currentPage ) { #>
			<button class="<?php echo join( ' ', array_map( 'sanitize_html_class', $button_classes ) ); ?>" data-page="{{data.nextPage}}">
				<span class="<?php echo sanitize_html_class( $screen_reader_class ); ?>"><?php echo esc_html_x( 'Next', 'link', 'buddypress' ); ?></span>
				<span class="dashicons dashicons-arrow-right"></span>
			</button>
		<# } #>
	<# } #>
</script>
