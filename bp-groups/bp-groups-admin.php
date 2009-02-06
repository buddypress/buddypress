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
	
		<form id="wpmu-search" method="post" action="<?php $_SERVER['PHP_SELF'] ?>">
			<input type="text" size="17" value="<?php echo $_REQUEST['s'] ?>" name="s" />
			<input id="post-query-submit" class="button" type="submit" value="Search Groups"/>
		</form>
		
		<?php if ( bp_has_groups('15') ) : ?>
			<form id="bp-group-admin-list" method="post" action="<?php $_SERVER['PHP_SELF'] ?>">
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php bp_group_pagination() ?>
					</div>
					<div class="alignleft">
						<input class="button-secondary delete" type="submit" name="groups_admin_delete" value="Delete" onclick="if ( !confirm('<?php _e( 'Are you sure?', 'buddypress' ) ?>') ) return false"/>
						<?php wp_nonce_field('bp-groups-admin') ?>
						<br class="clear"/>
					</div>
				</div>
				
				<br class="clear"/>
				
				<?php if ( isset( $_REQUEST['s'] ) && $_REQUEST['s'] != '' ) { ?>
					<p><?php echo sprintf( __( 'Groups matching: "%s"', 'buddypress' ), $_REQUEST['s'] ) ?></p>
				<?php } ?>
				
				<?php 
				if ( !isset($_REQUEST['order']) || 'ASC' == $_REQUEST['order'] ) {
					$order = 'DESC';
				} else {
					$order = 'ASC';
				}
				?>
				
				<table class="widefat" cellspacing="3" cellpadding="3">
					<thead>
						<tr>
							<th class="check-column" scope="col"/>
								<input id="group_check_all" type="checkbox" value="0" name="group_check_all" onclick="if ( jQuery(this).attr('checked') ) { jQuery('#group-list input[@type=checkbox]').attr('checked', 'checked'); } else { jQuery('#group-list input[@type=checkbox]').attr('checked', ''); }" />
							</th>
							<th scope="col">
							</th>
							<th scope="col">
								<a href="<?php echo site_url() . $_SERVER['SCRIPT_NAME'] ?>?page=groups_admin_settings&amp;sortby=id&amp;order=<?php echo $order ?><?php if ( isset( $_REQUEST['s'] ) ) { ?>&amp;s=<?php echo $_REQUEST['s'] ?> <?php } ?>">
									ID
								</a>
							</th>
							<th scope="col">
								<a href="<?php echo site_url() . $_SERVER['SCRIPT_NAME'] ?>?page=groups_admin_settings&amp;sortby=name&amp;order=<?php echo $order ?><?php if ( isset( $_REQUEST['s'] ) ) { ?>&amp;s=<?php echo $_REQUEST['s'] ?> <?php } ?>">
									<?php _e( 'Name', 'buddypress' ) ?>
								</a>
							</th>
							<th scope="col">
								<a href="<?php echo site_url() . $_SERVER['SCRIPT_NAME'] ?>?page=groups_admin_settings&amp;sortby=description&amp;order=<?php echo $order ?><?php if ( isset( $_REQUEST['s'] ) ) { ?>&amp;s=<?php echo $_REQUEST['s'] ?> <?php } ?>">
									<?php _e( 'Description', 'buddypress' ) ?>
								</a>
							</th>
							<th scope="col">
								<a href="<?php echo site_url() . $_SERVER['SCRIPT_NAME'] ?>?page=groups_admin_settings&amp;sortby=status&amp;order=<?php echo $order ?><?php if ( isset( $_REQUEST['s'] ) ) { ?>&amp;s=<?php echo $_REQUEST['s'] ?> <?php } ?>">
									<?php _e( 'Type', 'buddypress' ) ?>
								</a>
							</th>
							<th scope="col">
								<a href="<?php echo site_url() . $_SERVER['SCRIPT_NAME'] ?>?page=groups_admin_settings&amp;sortby=members&amp;order=<?php echo $order ?><?php if ( isset( $_REQUEST['s'] ) ) { ?>&amp;s=<?php echo $_REQUEST['s'] ?> <?php } ?>">
									<?php _e( 'Members', 'buddypress' ) ?>
								</a>
							</th>
							<th scope="col">
								<a href="<?php echo site_url() . $_SERVER['SCRIPT_NAME'] ?>?page=groups_admin_settings&amp;sortby=date_created&amp;order=<?php echo $order ?><?php if ( isset( $_REQUEST['s'] ) ) { ?>&amp;s=<?php echo $_REQUEST['s'] ?> <?php } ?>">
									<?php _e( 'Created', 'buddypress' ) ?></a>
							</th>
							<th scope="col">
								<a href="<?php echo site_url() . $_SERVER['SCRIPT_NAME'] ?>?page=groups_admin_settings&amp;sortby=last_active&amp;order=<?php echo $order ?><?php if ( isset( $_REQUEST['s'] ) ) { ?>&amp;s=<?php echo $_REQUEST['s'] ?> <?php } ?>">
									<?php _e( 'Last Active', 'buddypress' ) ?>
								</a>
							</th>
							<th scope="col">
									<?php _e( 'Admins', 'buddypress' ) ?>
							</th>
							<th scope="col">
							</th>
						</tr>
					</thead>
					<tbody id="group-list" class="list:groups group-list">
						<?php $counter = 0 ?>
						<?php while ( bp_groups() ) : bp_the_group(); ?>
							<tr<?php if ( 1 == $counter % 2 ) { ?> class="alternate"<?php }?>>
								<th class="check-column" scope="row">
									<input id="group_<?php bp_group_id() ?>" type="checkbox" value="<?php bp_group_id() ?>" name="allgroups[<?php bp_group_id() ?>]" />
								</th>
								<td><?php bp_group_avatar_mini() ?></td>
								<td><?php bp_group_id() ?></td>
								<td><a href="<?php bp_group_permalink() ?>"><?php bp_group_name() ?></a></td>
								<td><?php bp_group_description_excerpt() ?></td>
								<td><?php bp_group_type() ?></td>
								<td><?php bp_group_total_members() ?></td>
								<td><?php bp_group_date_created() ?></td>
								<td><?php bp_group_last_active() ?></td>
								<td><?php bp_group_list_admins(false) ?></td>
								<td><a href="<?php bp_group_permalink() ?>/admin"><?php _e( 'Edit', 'buddypress') ?></a></td>
							</tr>
							<?php $counter++ ?>
						<?php endwhile; ?>
					</tbody>
				</table>
				
			<?php else: ?>

				<div id="message" class="info">
					<p><?php _e( 'No groups to display', 'buddypress' ) ?></p>
				</div>

			<?php endif;?>
			</form>
	</div>
<?php 
}

?>