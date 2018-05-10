<?php
/**
 * Notifications template tags
 *
 * @since 3.0.0
 * @version 3.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Display the notifications filter options.
 *
 * @since 3.0.0
 */
function bp_nouveau_notifications_filters() {
	echo bp_nouveau_get_notifications_filters();
}

	/**
	 * Get the notifications filter options.
	 *
	 * @since 3.0.0
	 *
	 * @return string HTML output.
	 */
	function bp_nouveau_get_notifications_filters() {
		$output   = '';
		$filters  = bp_nouveau_notifications_sort( bp_nouveau_notifications_get_filters() );
		$selected = 0;

		if ( ! empty( $_REQUEST['type'] ) ) {
			$selected = sanitize_key( $_REQUEST['type'] );
		}

		foreach ( $filters as $filter ) {
			if ( empty( $filter['id'] ) || empty( $filter['label'] ) ) {
				continue;
			}

			$output .= sprintf( '<option value="%1$s" %2$s>%3$s</option>',
				esc_attr( sanitize_key( $filter['id'] ) ),
				selected( $selected, $filter['id'], false ),
				esc_html( $filter['label'] )
			) . "\n";
		}

		if ( $output ) {
			$output = sprintf( '<option value="%1$s" %2$s>%3$s</option>',
				0,
				selected( $selected, 0, false ),
				esc_html__( '&mdash; Everything &mdash;', 'buddypress' )
			) . "\n" . $output;
		}

		/**
		 * Filter to edit the options output.
		 *
		 * @since 3.0.0
		 *
		 * @param string $output  The options output.
		 * @param array  $filters The sorted notifications filters.
		 */
		return apply_filters( 'bp_nouveau_get_notifications_filters', $output, $filters );
	}

/**
 * Outputs the order action links.
 *
 * @since 3.0.0
 */
function bp_nouveau_notifications_sort_order_links() {
	if ( 'unread' === bp_current_action() ) {
		$link = bp_get_notifications_unread_permalink( bp_displayed_user_id() );
	} else {
		$link = bp_get_notifications_read_permalink( bp_displayed_user_id() );
	}

	$desc = add_query_arg( 'sort_order', 'DESC', $link );
	$asc  = add_query_arg( 'sort_order', 'ASC', $link );
	?>

	<span class="notifications-order-actions">
		<a href="<?php echo esc_url( $desc ); ?>" class="bp-tooltip" data-bp-tooltip="<?php esc_attr_e( 'Newest First', 'buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Newest First', 'buddypress' ); ?>" data-bp-notifications-order="DESC"><span class="dashicons dashicons-arrow-down" aria-hidden="true"></span></a>
		<a href="<?php echo esc_url( $asc ); ?>" class="bp-tooltip" data-bp-tooltip="<?php esc_attr_e( 'Older First', 'buddypress' ); ?>" aria-label="<?php esc_attr_e( 'Older First', 'buddypress' ); ?>" data-bp-notifications-order="ASC"><span class="dashicons dashicons-arrow-up" aria-hidden="true"></span></a>
	</span>

	<?php
}

/**
 * Output the dropdown for bulk management of notifications.
 *
 * @since 3.0.0
 */
function bp_nouveau_notifications_bulk_management_dropdown() {
?>

	<div class="select-wrap">

		<label class="bp-screen-reader-text" for="notification-select"><?php
			/* translators: accessibility text */
			_e( 'Select Bulk Action', 'buddypress' );
		?></label>

		<select name="notification_bulk_action" id="notification-select">
			<option value="" selected="selected"><?php _e( 'Bulk Actions', 'buddypress' ); ?></option>

			<?php if ( bp_is_current_action( 'unread' ) ) : ?>
				<option value="read"><?php _e( 'Mark read', 'buddypress' ); ?></option>
			<?php elseif ( bp_is_current_action( 'read' ) ) : ?>
				<option value="unread"><?php _e( 'Mark unread', 'buddypress' ); ?></option>
			<?php endif; ?>
			<option value="delete"><?php _e( 'Delete', 'buddypress' ); ?></option>
		</select>

		<span class="select-arrow"></span>

	</div><!-- // .select-wrap -->

	<input type="submit" id="notification-bulk-manage" class="button action" value="<?php esc_attr_e( 'Apply', 'buddypress' ); ?>">
	<?php
}
