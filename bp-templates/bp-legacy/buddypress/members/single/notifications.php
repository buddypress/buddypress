<?php

/**
 * BuddyPress - Users Notifications
 *
 * @package BuddyPress
 * @subpackage bp-legacy
 */

?>

<div class="item-list-tabs no-ajax" id="subnav" role="navigation">
	<ul>
		<?php bp_get_options_nav(); ?>

		<li id="members-order-select" class="last filter">

			<label for="members-friends"><?php _e( 'Order By:', 'buddypress' ); ?></label>
			<select id="members-friends">
				<option value="newest"><?php _e( 'Newest First', 'buddypress' ); ?></option>
				<option value="oldest"><?php _e( 'Oldest First', 'buddypress' ); ?></option>
			</select>
		</li>
	</ul>
</div>

<?php
switch ( bp_current_action() ) :

	// Unread
	case 'unread' :
		bp_get_template_part( 'members/single/notifications/unread' );
		break;

	// Read
	case 'read' :
		bp_get_template_part( 'members/single/notifications/read' );
		break;

	// Any other
	default :
		bp_get_template_part( 'members/single/plugins' );
		break;
endswitch;
