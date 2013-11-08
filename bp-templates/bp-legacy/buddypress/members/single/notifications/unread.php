<?php if ( bp_has_notifications() ) : ?>

	<div id="pag-top" class="pagination no-ajax">
		<div class="pag-count" id="notifications-count-top">
			<?php bp_notifications_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="notifications-pag-top">
			<?php bp_notifications_pagination_links(); ?>
		</div>
	</div>

	<table class="notifications">
		<thead>
			<tr>
				<th class="icon"></th>
				<th class="title"><?php _e( 'Notification', 'buddypress' ); ?></th>
				<th class="date"><?php _e( 'Date Received', 'buddypress' ); ?></th>
				<th class="actions"><?php _e( 'Actions',    'buddypress' ); ?></th>
			</tr>
		</thead>

		<tbody>
			<?php bp_get_template_part( 'members/single/notifications/notifications-loop' ); ?>
		</tbody>
	</table>

	<div id="pag-bottom" class="pagination no-ajax">
		<div class="pag-count" id="notifications-count-bottom">
			<?php bp_notifications_pagination_count(); ?>
		</div>

		<div class="pagination-links" id="notifications-pag-bottom">
			<?php bp_notifications_pagination_links(); ?>
		</div>
	</div>

<?php else : ?>

	<div id="message" class="info">
		<p><?php _e( 'You have no new notifications.', 'buddypress' ); ?></p>
	</div>

<?php endif;
