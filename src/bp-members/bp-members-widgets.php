<?php
/**
 * BuddyPress Members Widgets.
 *
 * @package BuddyPress
 * @subpackage MembersWidgets
 * @since 2.2.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

require dirname( __FILE__ ) . '/classes/class-bp-core-members-widget.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-whos-online-widget.php';
require dirname( __FILE__ ) . '/classes/class-bp-core-recently-active-widget.php';

/**
 * Register bp-members widgets.
 *
 * Previously, these widgets were registered in bp-core.
 *
 * @since 2.2.0
 */
function bp_members_register_widgets() {
	add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Core_Members_Widget");'         ) );
	add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Core_Whos_Online_Widget");'     ) );
	add_action( 'widgets_init', create_function( '', 'return register_widget("BP_Core_Recently_Active_Widget");' ) );
}
add_action( 'bp_register_widgets', 'bp_members_register_widgets' );

/**
 * AJAX request handler for Members widgets.
 *
 * @since 1.0.0
 *
 * @see BP_Core_Members_Widget
 */
function bp_core_ajax_widget_members() {

	check_ajax_referer( 'bp_core_widget_members' );

	// Setup some variables to check.
	$filter      = ! empty( $_POST['filter']      ) ? $_POST['filter']                : 'recently-active-members';
	$max_members = ! empty( $_POST['max-members'] ) ? absint( $_POST['max-members'] ) : 5;

	// Determine the type of members query to perform.
	switch ( $filter ) {

		// Newest activated.
		case 'newest-members' :
			$type = 'newest';
			break;

		// Popular by friends.
		case 'popular-members' :
			if ( bp_is_active( 'friends' ) ) {
				$type = 'popular';
			} else {
				$type = 'active';
			}
			break;

		// Default.
		case 'recently-active-members' :
		default :
			$type = 'active';
			break;
	}

	// Setup args for querying members.
	$members_args = array(
		'user_id'         => 0,
		'type'            => $type,
		'per_page'        => $max_members,
		'max'             => $max_members,
		'populate_extras' => true,
		'search_terms'    => false,
	);

	// Query for members.
	if ( bp_has_members( $members_args ) ) : ?>
		<?php echo '0[[SPLIT]]'; // Return valid result. TODO: remove this. ?>
		<?php while ( bp_members() ) : bp_the_member(); ?>
			<li class="vcard">
				<div class="item-avatar">
					<a href="<?php bp_member_permalink(); ?>"><?php bp_member_avatar(); ?></a>
				</div>

				<div class="item">
					<div class="item-title fn"><a href="<?php bp_member_permalink(); ?>" title="<?php bp_member_name(); ?>"><?php bp_member_name(); ?></a></div>
					<?php if ( 'active' === $type ) : ?>
						<div class="item-meta"><span class="activity"><?php bp_member_last_active(); ?></span></div>
					<?php elseif ( 'newest' === $type ) : ?>
						<div class="item-meta"><span class="activity"><?php bp_member_registered(); ?></span></div>
					<?php elseif ( bp_is_active( 'friends' ) ) : ?>
						<div class="item-meta"><span class="activity"><?php bp_member_total_friend_count(); ?></span></div>
					<?php endif; ?>
				</div>
			</li>

		<?php endwhile; ?>

	<?php else: ?>
		<?php echo "-1[[SPLIT]]<li>"; ?>
		<?php esc_html_e( 'There were no members found, please try another filter.', 'buddypress' ) ?>
		<?php echo "</li>"; ?>
	<?php endif;
}
add_action( 'wp_ajax_widget_members',        'bp_core_ajax_widget_members' );
add_action( 'wp_ajax_nopriv_widget_members', 'bp_core_ajax_widget_members' );
