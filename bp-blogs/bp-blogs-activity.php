<?php
/******************************************************************************
 * These functions handle the recording, deleting and formatting of activity and
 * notifications for the user and for this specific component.
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


function bp_blogs_register_activity_actions() {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	bp_activity_set_action( $bp->blogs->id, 'new_blog',         __( 'New site created',        'buddypress' ) );
	bp_activity_set_action( $bp->blogs->id, 'new_blog_post',    __( 'New post published',      'buddypress' ) );
	bp_activity_set_action( $bp->blogs->id, 'new_blog_comment', __( 'New post comment posted', 'buddypress' ) );

	do_action( 'bp_blogs_register_activity_actions' );
}
add_action( 'bp_register_activity_actions', 'bp_blogs_register_activity_actions' );

function bp_blogs_record_activity( $args = '' ) {
	global $bp;

	if ( !bp_is_active( 'activity' ) )
		return false;

	$defaults = array(
		'user_id'           => $bp->loggedin_user->id,
		'action'            => '',
		'content'           => '',
		'primary_link'      => '',
		'component'         => $bp->blogs->id,
		'type'              => false,
		'item_id'           => false,
		'secondary_item_id' => false,
		'recorded_time'     => bp_core_current_time(),
		'hide_sitewide'     => false
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r, EXTR_SKIP );

	// Remove large images and replace them with just one image thumbnail
 	if ( bp_is_active( 'activity' ) && !empty( $content ) )
		$content = bp_activity_thumbnail_content_images( $content, $primary_link );

	if ( !empty( $action ) )
		$action = apply_filters( 'bp_blogs_record_activity_action', $action );

	if ( !empty( $content ) )
		$content = apply_filters( 'bp_blogs_record_activity_content', bp_create_excerpt( $content ), $content );

	// Check for an existing entry and update if one exists.
	$id = bp_activity_get_activity_id( array(
		'user_id'           => $user_id,
		'component'         => $component,
		'type'              => $type,
		'item_id'           => $item_id,
		'secondary_item_id' => $secondary_item_id
	) );

	return bp_activity_add( array( 'id' => $id, 'user_id' => $user_id, 'action' => $action, 'content' => $content, 'primary_link' => $primary_link, 'component' => $component, 'type' => $type, 'item_id' => $item_id, 'secondary_item_id' => $secondary_item_id, 'recorded_time' => $recorded_time, 'hide_sitewide' => $hide_sitewide ) );
}

function bp_blogs_delete_activity( $args = true ) {
	global $bp;

	if ( bp_is_active( 'activity' ) ) {
		$defaults = array(
			'item_id'           => false,
			'component'         => $bp->blogs->id,
			'type'              => false,
			'user_id'           => false,
			'secondary_item_id' => false
		);

		$params = wp_parse_args( $args, $defaults );
		extract( $params, EXTR_SKIP );

		bp_activity_delete_by_item_id( array(
			'item_id'           => $item_id,
			'component'         => $component,
			'type'              => $type,
			'user_id'           => $user_id,
			'secondary_item_id' => $secondary_item_id
		) );
	}
}

?>