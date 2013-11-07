<div class="notification-pagination">
	<?php bp_notifications_pagination_links() ?>
</div>

<?php while ( bp_the_notifications() ) : bp_the_notification(); ?>

	<tr>
		<td></td>
		<td><?php bp_the_notification_component_action(); ?></td>
		<td><?php echo bp_core_get_userlink( bp_get_the_notification_item_id() ); ?></td>
		<td><?php echo bp_core_time_since( bp_get_the_notification_date_notified() ); ?></td>
	</tr>

<?php endwhile;
