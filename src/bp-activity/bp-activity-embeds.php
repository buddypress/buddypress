<?php
/**
 * Functions related to embedding single activity items externally.
 *
 * Relies on WordPress 4.5.
 *
 * @since 2.6.0
 *
 * @package BuddyPress
 * @subpackage ActivityEmbeds
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Loads our activity oEmbed component.
 *
 * @since 2.6.0
 */
function bp_activity_setup_oembed() {
	if ( bp_get_major_wp_version() >= 4.5 && bp_is_active( 'activity', 'embeds' ) ) {
		buddypress()->activity->oembed = new BP_Activity_oEmbed_Component;
	}

	add_filter( 'bp_activity_get_embed_excerpt', 'wptexturize' );
	add_filter( 'bp_activity_get_embed_excerpt', 'convert_chars' );
	add_filter( 'bp_activity_get_embed_excerpt', 'make_clickable', 9 );
	add_filter( 'bp_activity_get_embed_excerpt', 'bp_activity_embed_excerpt_onclick_location_filter' );
	add_filter( 'bp_activity_get_embed_excerpt', 'bp_activity_at_name_filter' );
	add_filter( 'bp_activity_get_embed_excerpt', 'convert_smilies', 20 );
	add_filter( 'bp_activity_get_embed_excerpt', 'wpautop', 30 );
}
add_action( 'bp_loaded', 'bp_activity_setup_oembed' );

/**
 * Catch links in embed excerpt so top.location.href can be added.
 *
 * Due to <iframe sandbox="allow-top-navigation">, links in embeds can only be
 * clicked if invoked with top.location.href via JS.
 *
 * @since 2.6.0
 *
 * @param  string $text Embed excerpt
 * @return string
 */
function bp_activity_embed_excerpt_onclick_location_filter( $text ) {
	return preg_replace_callback( '/<a href=\"([^\"]*)\"/iU', 'bp_activity_embed_excerpt_onclick_location_filter_callback', $text );
}
	/**
	 * Add onclick="top.location.href" to a link.
	 *
	 * @since 2.6.0
	 *
	 * @param  array $matches Items matched by bp_activity_embed_excerpt_onclick_location_filter().
	 * @return string
	 */
	function bp_activity_embed_excerpt_onclick_location_filter_callback( $matches ) {
		return sprintf( '<a href="%1$s" onclick="top.location.href=\'%1$s\'"', $matches[1] );
	}

/**
 * Add inline styles for BP activity embeds.
 *
 * This is subject to change or be removed entirely for a different system.
 * Potentially for BP_Legacy::locate_asset_in_stack().
 *
 * @since  2.6.0
 * @access private
 */
function _bp_activity_embed_add_inline_styles() {
	if ( false === bp_is_single_activity() ) {
		return;
	}

	ob_start();
	if ( is_rtl() ) {
		bp_get_asset_template_part( 'embeds/css-activity', 'rtl' );
	} else {
		bp_get_asset_template_part( 'embeds/css-activity' );
	}
	$css = ob_get_clean();

	// Rudimentary CSS protection.
	$css = wp_kses( $css, array( "\'", '\"' ) );

	printf( '<style type="text/css">%s</style>', $css );
}
add_action( 'embed_head', '_bp_activity_embed_add_inline_styles', 20 );

/**
 * Query for the activity item on the activity embed template.
 *
 * Basically a wrapper for {@link bp_has_activities()}, but allows us to
 * use the activity loop without requerying for it again.
 *
 * @since 2.6.0
 *
 * @param  int $activity_id The activity ID.
 * @return bool
 */
function bp_activity_embed_has_activity( $activity_id = 0 ) {
	global $activities_template;

	if ( empty( $activity_id ) ) {
		return false;
	}

	if ( ! empty( $activities_template->activities ) ) {
		$activity = (array) $activities_template->activities;
		$activity = reset( $activity );

		// No need to requery if we already got the embed activity
		if ( (int) $activity_id === (int) $activity->id ) {
			return $activities_template->has_activities();
		}
	}

	return bp_has_activities( array(
		'display_comments' => 'threaded',
		'show_hidden'      => true,
		'include'          => (int) $activity_id,
	) );
}

/**
 * Outputs excerpt for an activity embed item.
 *
 * @since 2.6.0
 */
function bp_activity_embed_excerpt( $content = '' ) {
	echo bp_activity_get_embed_excerpt( $content = '' );
}

	/**
	 * Generates excerpt for an activity embed item.
	 *
	 * @since 2.6.0
	 *
	 * @param  string $content The content to generate an excerpt for.
	 * @return string
	 */
	function bp_activity_get_embed_excerpt( $content = '' ) {
		if ( empty( $content ) && ! empty( $GLOBALS['activities_template']->in_the_loop ) ) {
			$content = $GLOBALS['activities_template']->activity->content;
		}

		/**
		 * bp_activity_truncate_entry() includes the 'Read More' link, which is why
		 * we're using this instead of bp_create_excerpt().
		 */
		$content = html_entity_decode( $content );
		$content = bp_activity_truncate_entry( $content, array(
			'html' => false,
			'filter_shortcodes' => true,
			'strip_tags'        => true,
			'force_truncate'    => true
		) );

		/**
		 * Filter the activity embed excerpt.
		 *
		 * @since 2.6.0
		 *
		 * @var string $content Embed Excerpt.
		 * @var string $unmodified_content Unmodified activity content.
		 */
		return apply_filters( 'bp_activity_get_embed_excerpt', $content, $GLOBALS['activities_template']->activity->content );
	}
