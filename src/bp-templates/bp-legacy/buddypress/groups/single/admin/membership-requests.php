<?php
/**
 * BuddyPress - Groups Admin - Membership Requests
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<h2 class="bp-screen-reader-text"><?php _e( 'Manage Membership Requests', 'buddypress' ); ?></h2>

<?php

/**
 * Fires before the display of group membership requests admin.
 *
 * @since 1.1.0
 */
do_action( 'bp_before_group_membership_requests_admin' ); ?>

	<div class="requests">

		<?php bp_get_template_part( 'groups/single/requests-loop' ); ?>

	</div>

<?php

/**
 * Fires after the display of group membership requests admin.
 *
 * @since 1.1.0
 */
do_action( 'bp_after_group_membership_requests_admin' );
