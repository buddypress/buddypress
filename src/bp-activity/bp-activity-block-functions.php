<?php
/**
 * BuddyPress Activity Block functions.
 *
 * @package buddypress\bp-activity-block-functions
 * @since 11.0.0
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Determine whether an activity or its content string has blocks.
 *
 * @since 11.0.0
 * @see parse_blocks()
 *
 * @param string|int|BP_Activity_Activity|null $activity Activity content, Activity ID, or Activity object.
 * @return bool Whether the activity content has blocks.
 */
function bp_activity_has_blocks( $activity = null ) {
	if ( is_null( $activity ) ) {
		return false;
	}

	if ( ! is_string( $activity ) ) {
		if ( is_int( $activity ) ) {
			$bp_activity = new BP_Activity_Activity( $activity );
		} else {
			$bp_activity = $activity;
		}

		if ( $bp_activity instanceof BP_Activity_Activity ) {
			$activity = $bp_activity->content;
		}
	}

	return has_blocks( $activity );
}

/**
 * If `bp_activity_do_blocks()` needs to remove `wpautop()` from the `bp_get_activity_content_body` filter, this re-adds it afterwards,
 * for subsequent `bp_get_activity_content_body` usage.
 *
 * @since 11.0.0
 *
 * @param string $content The activity content running through this filter.
 * @return string The unmodified activity content.
 */
function bp_activity_restore_wpautop_hook( $content ) {
	$current_priority = has_filter( 'bp_get_activity_content_body', 'bp_activity_restore_wpautop_hook' );

	add_filter( 'bp_get_activity_content_body', 'wpautop', $current_priority - 1 );
	remove_filter( 'bp_get_activity_content_body', 'bp_activity_restore_wpautop_hook', $current_priority );

	return $content;
}

/**
 * Parses dynamic blocks out of the activity content and re-renders them.
 *
 * @since 11.0.0
 *
 * @param string $content Activity content.
 * @return string The block based activity content.
 */
function bp_activity_do_blocks( $content ) {
	$blocks = parse_blocks( $content );
	$output = '';

	foreach ( $blocks as $block ) {
		$output .= render_block( $block );
	}

	// If there are blocks in this content, we shouldn't run wpautop() on it later.
	$priority = has_filter( 'bp_get_activity_content_body', 'wpautop' );
	if ( false !== $priority && doing_filter( 'bp_get_activity_content_body' ) && bp_activity_has_blocks( $content ) ) {
		remove_filter( 'bp_get_activity_content_body', 'wpautop', $priority );
		add_filter( 'bp_get_activity_content_body', 'bp_activity_restore_wpautop_hook', $priority + 1 );
	}

	return $output;
}
add_filter( 'bp_get_activity_content_body', 'bp_activity_do_blocks', 9 );

/**
 * Make sure only Emoji chars are saved into the DB.
 *
 * @since 11.0.0
 *
 * @param string $activity_content The activity content.
 * @return string The sanitized activity content.
 */
function bp_activity_blocks_preserve_emoji_chars( $activity_content ) {
	preg_match_all( '/\<img[^>]*alt=\"([^"]*)\".?\>/', $activity_content, $matches );

	if ( isset( $matches[0][0] ) && isset( $matches[1][0] ) ) {
		foreach ( $matches[0] as $key => $match ) {
			if ( false !== strpos( $matches[0][ $key ], 's.w.org/images/core/emoji' ) && isset( $matches[1][ $key ] ) ) {
				$activity_content = str_replace( $matches[0][ $key ], $matches[1][ $key ], $activity_content );
			}
		}
	}

	return $activity_content;
}
add_filter( 'bp_activity_content_before_save', 'bp_activity_blocks_preserve_emoji_chars', 2 );

/**
 * Allow usage of the paragraph tag and the link’s target attribute into Activity content.
 *
 * @since 11.0.0
 *
 * @param array $tags The activity allowed tags.
 * @return array The block based activity allowed tags.
 */
function bp_activity_blocks_allowed_tags( $tags = array() ) {
	if ( isset( $tags['a'] ) && ! isset( $tags['a']['target'] ) ) {
		$tags['a']['target'] = true;
	}

	return array_merge( $tags, array( 'p' => true ) );
}
add_filter( 'bp_activity_allowed_tags', 'bp_activity_blocks_allowed_tags' );
