<div class="notification-pagination">
	<?php bp_notifications_pagination_links() ?>
</div>

<?php while ( bp_the_notifications() ) : bp_the_notification(); ?>

	<tr>
		<td></td>
		<td><?php bp_the_notification_description(); ?></td>
		<td><?php bp_the_notification_time_since(); ?></td>
		<td><?php bp_the_notification_action_links(); ?></td>
	</tr>

<?php endwhile;
