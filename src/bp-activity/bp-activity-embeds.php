<?php
/**
 * Functions related to embedding single activity items externally.
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
	buddypress()->activity->oembed = new BP_Activity_oEmbed_Extension;
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
	return preg_replace_callback( '/<a\s+[^>]*href=\"([^\"]*)\"/iU', 'bp_activity_embed_excerpt_onclick_location_filter_callback', $text );
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
 * @since  2.6.0
 */
function bp_activity_embed_add_inline_styles() {
	if ( false === bp_is_single_activity() ) {
		return;
	}

	$min = bp_core_get_minified_asset_suffix();

	if ( is_rtl() ) {
		$css = bp_locate_template_asset( "css/embeds-activity-rtl{$min}.css" );
	} else {
		$css = bp_locate_template_asset( "css/embeds-activity{$min}.css" );
	}

	// Bail if file wasn't found.
	if ( false === $css ) {
		return;
	}

	// Grab contents of CSS file and do some rudimentary CSS protection.
	$css = file_get_contents( $css['file'] );

	printf( '<style type="text/css">%s</style>', wp_kses( $css, array( "\'", '\"' ) ) );
}
add_action( 'embed_head', 'bp_activity_embed_add_inline_styles', 20 );

/**
 * Query for the activity item on the activity embed template.
 *
 * Basically a wrapper for {@link bp_has_activities()}, but allows us to
 * use the activity loop without requerying for it again.
 *
 * @since 2.6.0
 *
 * @global BP_Activity_Template $activities_template The Activity template loop.
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

		// No need to requery if we already got the embed activity.
		if ( (int) $activity_id === $activity->id ) {
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
	// Escaping is made in `bp-activity/bp-activity-filters.php`.
	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_activity_get_embed_excerpt( $content );
}

	/**
	 * Generates excerpt for an activity embed item.
	 *
	 * @since 2.6.0
	 * 
	 * @global BP_Activity_Template $activities_template The Activity template loop.
	 *
	 * @param  string $content The content to generate an excerpt for.
	 * @return string
	 */
	function bp_activity_get_embed_excerpt( $content = '' ) {
		if ( empty( $content ) && ! empty( $GLOBALS['activities_template']->in_the_loop ) ) {
			$content = $GLOBALS['activities_template']->activity->content;
		}

		/*
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

/**
 * Outputs the first embedded item in the activity oEmbed template.
 *
 * @since 2.6.0
 * 
 * @global BP_Activity_Template $activities_template The Activity template loop.
 * 
 */
function bp_activity_embed_media() {
	// Bail if oEmbed request explicitly hides media.
	if ( isset( $_GET['hide_media'] ) && true == wp_validate_boolean( $_GET['hide_media'] ) ) {
		/**
		 * Do something after media is rendered for an activity oEmbed item.
		 *
		 * @since 2.6.0
		 */
		do_action( 'bp_activity_embed_after_media' );

		return;
	}

	/**
	 * Should we display media in the oEmbed template?
	 *
	 * @since 2.6.0
	 *
	 * @param bool $retval Defaults to true.
	 */
	$allow_media = apply_filters( 'bp_activity_embed_display_media', true );

	// Find oEmbeds from only WP registered providers.
	bp_remove_all_filters( 'oembed_providers' );
	$media = bp_core_extract_media_from_content( $GLOBALS['activities_template']->activity->content, 'embeds' );
	bp_restore_all_filters( 'oembed_providers' );

	// oEmbeds have precedence over inline video / audio.
	if ( isset( $media['embeds'] ) && true === $allow_media ) {
		// Autoembed first URL.
		$oembed_defaults = wp_embed_defaults();
		$oembed_args = array(
			'width'    => $oembed_defaults['width'],
			'height'   => $oembed_defaults['height'],
			'discover' => true
		);
		$url      = $media['embeds'][0]['url'];
		$cachekey = '_oembed_response_' . md5( $url . serialize( $oembed_args ) );

		// Try to fetch oEmbed response from meta.
		$oembed = bp_activity_get_meta( bp_get_activity_id(), $cachekey );

		// No cache, so fetch full oEmbed response now!
		if ( '' === $oembed ) {
			$o = _wp_oembed_get_object();
			$oembed = $o->fetch( $o->get_provider( $url, $oembed_args ), $url, $oembed_args );

			// Cache oEmbed response.
			bp_activity_update_meta( bp_get_activity_id(), $cachekey, $oembed );
		}

		$content = '';

		/**
		 * Filters the default embed display max width.
		 *
		 * This is used if the oEmbed response does not return a thumbnail width.
		 *
		 * @since 2.6.0
		 *
		 * @param int $width.
		 */
		$width = (int) apply_filters( 'bp_activity_embed_display_media_width', 550 );

		// Set thumbnail.
		if ( 'photo' === $oembed->type ) {
			$thumbnail = $oembed->url;
		} elseif ( isset( $oembed->thumbnail_url ) ) {
			$thumbnail = $oembed->thumbnail_url;

		/* Non-oEmbed standard attributes */
		// Mixcloud.
		} elseif ( isset( $oembed->image ) ) {
			$thumbnail = $oembed->image;
		// ReverbNation.
		} elseif ( isset( $oembed->{'thumbnail-url'} ) ) {
			$thumbnail = $oembed->{'thumbnail-url'};
		}

		// Display thumb and related oEmbed meta.
		if ( true === isset ( $thumbnail ) ) {
			$play_icon = $caption = '';

			// Add play icon for non-photos.
			if ( 'photo' !== $oembed->type ) {
				/**
				 * ion-play icon from Ionicons.
				 *
				 * @link    http://ionicons.com/
				 * @license MIT
				 */
				$play_icon = <<<EOD
<svg id="Layer_1" style="enable-background:new 0 0 512 512;" version="1.1" viewBox="0 0 512 512" xml:space="preserve" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"><path d="M405.2,232.9L126.8,67.2c-3.4-2-6.9-3.2-10.9-3.2c-10.9,0-19.8,9-19.8,20H96v344h0.1c0,11,8.9,20,19.8,20  c4.1,0,7.5-1.4,11.2-3.4l278.1-165.5c6.6-5.5,10.8-13.8,10.8-23.1C416,246.7,411.8,238.5,405.2,232.9z"/></svg>
EOD;

				$play_icon = sprintf( '<a rel="nofollow" class="play-btn" href="%1$s" onclick="top.location.href=\'%1$s\'">%2$s</a>', esc_url( $url ), $play_icon );
			}

			// Thumb width.
			$thumb_width = isset( $oembed->thumbnail_width ) && 'photo' !== $oembed->type && (int) $oembed->thumbnail_width < 550 ? (int) $oembed->thumbnail_width : $width;

			$float_width = 350;

			// Set up thumb.
			$content = sprintf( '<div class="thumb" style="max-width:%1$spx">%2$s<a href="%3$s" rel="nofollow" onclick="top.location.href=\'%3$s\'"><img loading="lazy" src="%4$s" alt="" /></a></div>', $thumb_width, $play_icon, esc_url( $url ), esc_url( $thumbnail ) );

			// Show title.
			if ( isset( $oembed->title ) ) {
				$caption .= sprintf( '<p class="caption-title"><strong>%s</strong></p>', apply_filters( 'single_post_title', $oembed->title ) );
			}

			// Show description (non-oEmbed standard).
			if ( isset( $oembed->description ) ) {
				$caption .= sprintf( '<div class="caption-description">%s</div>', apply_filters( 'bp_activity_get_embed_excerpt', $oembed->description ) );
			}

			// Show author info.
			if ( isset( $oembed->provider_name ) && isset( $oembed->author_name ) ) {
				/* translators: 1: oEmbed author. 2: oEmbed provider. eg. By BuddyPress on YouTube. */
				$anchor_text = sprintf( __( 'By %1$s on %2$s', 'buddypress' ), $oembed->author_name, $oembed->provider_name );

			} elseif ( isset( $oembed->provider_name ) ) {
				/* translators: %s: oEmbed provider. */
				$anchor_text = sprintf( __( 'View on %s', 'buddypress' ), $oembed->provider_name );
			}

			if ( true === isset( $anchor_text ) )  {
				$caption .= sprintf( '<a rel="nofollow" href="%1$s" onclick="top.location.href=\'%1$s\'">%2$s</a>', esc_url( $url ), apply_filters( 'the_title', $anchor_text ) );
			}

			// Set up caption.
			if ( '' !== $caption ) {
				$css_class = isset( $oembed->provider_name ) ? sprintf( ' provider-%s', sanitize_html_class( strtolower( $oembed->provider_name ) ) ) : '';
				$caption = sprintf( '<div class="caption%1$s" style="width:%2$s">%3$s</div>',
					$css_class,
					$thumb_width > $float_width ? 100 . '%' : round( ( $width - (int) $thumb_width ) / $width * 100 ) . '%',
					$caption
				);

				$content .= $caption;
			}
		}

		// Print rich content.
		if ( '' !== $content ) {
			printf( '<div class="bp-activity-embed-display-media %s" style="max-width:%spx">%s</div>',
				$thumb_width < $float_width ? 'two-col' : 'one-col',
				$thumb_width < $float_width ? intval( $width ) : intval( $thumb_width ),
				// phpcs:ignore WordPress.Security.EscapeOutput
				$content
			);
		}

	// Video / audio.
	} elseif ( true === $allow_media ) {
		// Call BP_Embed if it hasn't already loaded.
		bp_embed_init();

		// Run shortcode and embed routine.
		$content = buddypress()->embed->run_shortcode( $GLOBALS['activities_template']->activity->content );
		$content = buddypress()->embed->autoembed( $content );

		// Try to find inline video / audio.
		$media = bp_core_extract_media_from_content( $content, 96 );

		// Video takes precedence. HTML5-only.
		if ( isset( $media['videos'] ) && 'shortcodes' === $media['videos'][0]['source'] ) {
			printf( '<video controls preload="metadata"><source src="%1$s"><p>%2$s</p></video>',
				esc_url( $media['videos'][0]['url'] ),
				esc_html__( 'Your browser does not support HTML5 video', 'buddypress' )
			);

		// No video? Try audio. HTML5-only.
		} elseif ( isset( $media['audio'] ) && 'shortcodes' === $media['audio'][0]['source'] ) {
			printf( '<audio controls preload="metadata"><source src="%1$s"><p>%2$s</p></audio>',
				esc_url( $media['audio'][0]['url'] ),
				esc_html__( 'Your browser does not support HTML5 audio', 'buddypress' )
			);
		}

	}

	/** This hook is documented in /bp-activity/bp-activity-embeds.php */
	do_action( 'bp_activity_embed_after_media' );
}

/**
 * Make sure the Activity embed template will be used if neded.
 *
 * @since 12.0.0
 *
 * @param WP_Query $query Required.
 */
function bp_activity_parse_embed_query( $query ) {
	if ( bp_is_single_activity() && $query->get( 'embed' ) ) {
		$query->is_embed = true;
	}
}
add_action( 'bp_members_parse_query', 'bp_activity_parse_embed_query', 10, 1 );
