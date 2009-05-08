<?php

function groups_admin_settings() { 
	
	if ( isset( $_POST['groups_admin_delete']) && isset( $_POST['allgroups'] ) ) {
		if ( !check_admin_referer('bp-groups-admin') )
			return false;
		
		$errors = false;
		foreach ( $_POST['allgroups'] as $group_id ) {
			$group = new BP_Groups_Group( $group_id );
			if ( !$group->delete() ) {
				$errors = true;
			}
		}
		
		if ( $errors ) {
			$message = __( 'There were errors when deleting groups, please try again', 'buddypress' );
			$type = 'error';
		} else {
			$message = __( 'Groups deleted successfully', 'buddypress' );
			$type = 'updated';
		}
	}
?>
	<?php if ( isset( $message ) ) { ?>
		<div id="message" class="<?php echo $type ?> fade">
			<p><?php echo $message ?></p>
		</div>
	<?php } ?>

	<div class="wrap" style="position: relative">
		<h2><?php _e( 'Groups', 'buddypress' ) ?></h2>
	
		<form id="wpmu-search" method="post" action="">
			<input type="text" size="17" value="<?php echo attribute_escape( stripslashes( $_REQUEST['s'] ) ); ?>" name="s" />
			<input id="post-query-submit" class="button" type="submit" value="<?php _e( 'Search Groups', 'buddypress' ) ?>" />
		</form>
		
		<?php if ( bp_has_site_groups( 'type=active&per_page=10' ) ) : ?>
			<form id="bp-group-admin-list" method="post" action="">
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php bp_site_groups_pagination_count() ?> <?php bp_site_groups_pagination_links() ?>
					</div>
					<div class="alignleft">
						<input class="button-secondary delete" type="submit" name="groups_admin_delete" value="<?php _e( 'Delete', 'buddypress' ) ?>" onclick="if ( !confirm('<?php _e( 'Are you sure?', 'buddypress' ) ?>') ) return false"/>
						<?php wp_nonce_field('bp-groups-admin') ?>
						<br class="clear"/>
					</div>
				</div>
				
				<br class="clear"/>
				
				<?php if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] != '' ) { ?>
					<p><?php echo sprintf( __( 'Groups matching: "%s"', 'buddypress' ), $_REQUEST['s'] ) ?></p>
				<?php } ?>


				<table class="widefat" cellspacing="3" cellpadding="3">
					<thead>
						<tr>
							<th class="check-column" scope="col">
								<input id="group_check_all" type="checkbox" value="0" name="group_check_all" onclick="if ( jQuery(this).attr('checked') ) { jQuery('#group-list input[@type=checkbox]').attr('checked', 'checked'); } else { jQuery('#group-list input[@type=checkbox]').attr('checked', ''); }" />
							</th>
							<th scope="col">
							</th>
							<th scope="col">
									ID
							</th>
							<th scope="col">
									<?php _e( 'Name', 'buddypress' ) ?>
							</th>
							<th scope="col">
									<?php _e( 'Description', 'buddypress' ) ?>
							</th>
							<th scope="col">
									<?php _e( 'Type', 'buddypress' ) ?>
							</th>
							<th scope="col">
									<?php _e( 'Members', 'buddypress' ) ?>
							</th>
							<th scope="col">
									<?php _e( 'Created', 'buddypress' ) ?>
							</th>
							<th scope="col">
									<?php _e( 'Last Active', 'buddypress' ) ?>
							</th>
							<th scope="col">
							</th>
						</tr>
					</thead>
					<tbody id="group-list" class="list:groups group-list">
					<?php $counter = 0 ?>
					<?php while ( bp_site_groups() ) : bp_the_site_group(); ?>
						<tr<?php if ( 1 == $counter % 2 ) { ?> class="alternate"<?php }?>>
							<th class="check-column" scope="row">
								<input id="group_<?php bp_the_site_group_id() ?>" type="checkbox" value="<?php bp_the_site_group_id() ?>" name="allgroups[<?php bp_the_site_group_id() ?>]" />
							</th>
							<td><?php bp_the_site_group_avatar_mini() ?></td>
							<td><?php bp_the_site_group_id() ?></td>
							<td><a href="<?php bp_the_site_group_link() ?>"><?php bp_the_site_group_name() ?></a></td>
							<td><?php bp_the_site_group_description_excerpt() ?></td>
							<td><?php bp_the_site_group_type() ?></td>
							<td><?php bp_the_site_group_member_count() ?></td>
							<td><?php bp_the_site_group_date_created() ?></td>
							<td><?php bp_the_site_group_last_active() ?></td>
							<td><a href="<?php bp_the_site_group_link() ?>/admin"><?php _e( 'Edit', 'buddypress') ?></a></td>
						</tr>
						<?php $counter++ ?>
					<?php endwhile; ?>
					</tbody>
				</table>	

		<?php else: ?>

			<div id="message" class="info">
				<p><?php _e( 'No groups found.', 'buddypress' ) ?></p>
			</div>

		<?php endif; ?>

		<?php bp_the_site_group_hidden_fields() ?>
		</form>
	</div>
<?php 
}

?>