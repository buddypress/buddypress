<?php if ( bp_has_notifications() ) : ?>

	<table class="notification-settings">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Notification', 'buddypress' ) ?></th>
				<th class="title"><?php _e( 'Member',       'buddypress' ) ?></th>
				<th class="date"><?php _e( 'Date Received', 'buddypress' ) ?></th>
			</tr>
		</thead>

		<tbody>
			<?php bp_get_template_part( 'members/single/notifications/notifications-loop' ); ?>
		</tbody>
	</table>

<?php else : ?>

	<div id="message" class="info">
		<p><?php _e( 'You have no notifications.', 'buddypress' ); ?></p>
	</div>

<?php endif;