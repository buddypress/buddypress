<?php
/**
 * BuddyPress Activity Classes.
 *
 * @package BuddyPress
 * @subpackage Embeds
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * oEmbed handler to respond and render single activity items.
 *
 * @since 2.6.0
 */
class BP_Activity_oEmbed_Extension extends BP_Core_oEmbed_Extension {
	/**
	 * Custom oEmbed slug endpoint.
	 *
	 * @since 2.6.0
	 *
	 * @var string
	 */
	public $slug_endpoint = 'activity';

	/**
	 * Custom hooks.
	 *
	 * @since 2.6.0
	 */
	protected function custom_hooks() {
		add_action( 'oembed_dataparse',   array( $this, 'use_custom_iframe_sandbox_attribute' ), 20, 3 );
		add_action( 'embed_content_meta', array( $this, 'embed_comments_button' ), 5 );
		add_action( 'get_template_part_assets/embeds/header', array( $this, 'on_activity_header' ), 10, 2 );

		add_filter( 'bp_activity_embed_html', array( $this, 'modify_iframe' ) );
	}

	/**
	 * Add custom endpoint arguments.
	 *
	 * Currently, includes 'hide_media'.
	 *
	 * @since 2.6.0
	 *
	 * @return array
	 */
	protected function set_route_args() {
		return array(
			'hide_media' => array(
				'default' => false,
				'sanitize_callback' => 'wp_validate_boolean'
			)
		);
	}

	/**
	 * Output our custom embed template part.
	 *
	 * @since 2.6.0
	 */
	protected function content() {
		bp_get_asset_template_part( 'embeds/activity' );
	}

	/**
	 * Check if we're on our single activity page.
	 *
	 * @since 2.6.0
	 *
	 * @return bool
	 */
	protected function is_page() {
		return bp_is_single_activity();
	}

	/**
	 * Validates the URL to determine if the activity item is valid.
	 *
	 * @since 2.6.0
	 *
	 * @param  string   $url The URL to check.
	 * @return int|bool Activity ID on success; boolean false on failure.
	 */
	protected function validate_url_to_item_id( $url ) {
		if ( bp_core_enable_root_profiles() ) {
			$domain = bp_get_root_url();
		} else {
			$domain = bp_get_members_directory_permalink();
		}

		// Check the URL to see if this is a single activity URL.
		if ( is_array( $url ) || 0 !== strpos( $url, $domain ) ) {
			return false;
		}

		if ( ! bp_has_pretty_urls() ) {
			$url_query = wp_parse_url( $url, PHP_URL_QUERY );

			if ( $url_query ) {
				$query_vars = bp_parse_args( $url_query, array() );

				if ( isset( $query_vars['bp_member_action'] ) ) {
					$activity_id = (int) $query_vars['bp_member_action'];
				}
			}

		} elseif ( false !== strpos( $url, '/' . bp_get_activity_slug() . '/' ) ) {
			// Do more checks.
			$url = trim( untrailingslashit( $url ) );

			// Grab the activity ID.
			$activity_id = (int) substr(
				$url,
				strrpos( $url, '/' ) + 1
			);
		}

		if ( ! empty( $activity_id ) ) {
			// Check if activity item still exists.
			$activity = new BP_Activity_Activity( $activity_id );

			// Okay, we're good to go!
			if ( ! empty( $activity->component ) && 0 === (int) $activity->is_spam ) {
				return $activity_id;
			}
		}

		return false;
	}

	/**
	 * Sets the oEmbed response data for our activity item.
	 *
	 * @since 2.6.0
	 *
	 * @param  int $item_id The activity ID.
	 * @return array
	 */
	protected function set_oembed_response_data( $item_id ) {
		$activity = new BP_Activity_Activity( $item_id );

		return array(
			'content'      => $activity->content,
			'title'        => __( 'Activity', 'buddypress' ),
			'author_name'  => bp_core_get_user_displayname( $activity->user_id ),
			'author_url'   => bp_members_get_user_url( $activity->user_id ),

			// Custom identifier.
			'x_buddypress' => 'activity'
		);
	}

	/**
	 * Sets a custom <blockquote> for our oEmbed fallback HTML.
	 *
	 * @since 2.6.0
	 *
	 * @global BP_Activity_Template $activities_template The Activity template loop.
	 *
	 * @param  int $item_id The activity ID.
	 * @return string
	 */
	protected function set_fallback_html( $item_id ) {
		$activity    = new BP_Activity_Activity( $item_id );
		$mentionname = bp_activity_do_mentions() ? ' (@' . bp_activity_get_user_mentionname( $activity->user_id ) . ')' : '';
		$date        = date_i18n( get_option( 'date_format' ), strtotime( $activity->date_recorded ) );

		// Make sure we can use some activity functions that depend on the loop.
		$GLOBALS['activities_template'] = new stdClass;
		$GLOBALS['activities_template']->activity = $activity;

		// 'wp-embedded-content' CSS class is necessary due to how the embed JS works.
		$blockquote = sprintf( '<blockquote class="wp-embedded-content bp-activity-item">%1$s%2$s %3$s</blockquote>',
			bp_activity_get_embed_excerpt( $activity->content ),
			'- ' . bp_core_get_user_displayname( $activity->user_id ) . $mentionname,
			'<a href="' . esc_url( bp_activity_get_permalink( $item_id ) ) . '">' . $date . '</a>'
		);

		// Clean up.
		unset( $GLOBALS['activities_template'] );

		/**
		 * Filters the fallback HTML used when embedding a BP activity item.
		 *
		 * @since 2.6.0
		 *
		 * @param string               $blockquote Current fallback HTML
		 * @param BP_Activity_Activity $activity   Activity object
		 */
		return apply_filters( 'bp_activity_embed_fallback_html', $blockquote, $activity );
	}

	/**
	 * Sets a custom <iframe> title for our oEmbed item.
	 *
	 * @since 2.6.0
	 *
	 * @param  int $item_id The activity ID
	 * @return string
	 */
	protected function set_iframe_title( $item_id ) {
		return __( 'Embedded Activity Item', 'buddypress' );
	}

	/**
	 * Use our custom <iframe> sandbox attribute in our oEmbed response.
	 *
	 * WordPress sets the <iframe> sandbox attribute to 'allow-scripts' regardless
	 * of whatever the oEmbed response is in {@link wp_filter_oembed_result()}. We
	 * need to add back our custom sandbox value so links will work.
	 *
	 * @since 2.6.0
	 *
	 * @see BP_Activity_Component::modify_iframe() where our custom sandbox value is set.
	 *
	 * @param string $result The oEmbed HTML result.
	 * @param object $data   A data object result from an oEmbed provider.
	 * @param string $url    The URL of the content to be embedded.
	 * @return string
	 */
	public function use_custom_iframe_sandbox_attribute( $result, $data, $url ) {
		// Make sure we are on a BuddyPress activity oEmbed request.
		if ( false === isset( $data->x_buddypress ) || 'activity' !== $data->x_buddypress ) {
			return $result;
		}

		// Get unfiltered sandbox attribute from our own oEmbed response.
		$sandbox_pos = strpos( $data->html, 'sandbox=' ) + 9;
		$sandbox = substr( $data->html, $sandbox_pos, strpos( $data->html, '"', $sandbox_pos ) - $sandbox_pos );

		// Replace only if our sandbox attribute contains 'allow-top-navigation'.
		if ( false !== strpos( $sandbox, 'allow-top-navigation' ) ) {
			$result = str_replace( ' sandbox="allow-scripts"', " sandbox=\"{$sandbox}\"", $result );

			// Also remove 'security' attribute; this is only used for IE < 10.
			$result = str_replace( 'security="restricted"', "", $result );
		}

		return $result;
	}

	/**
	 * Modify various IFRAME-related items if embeds are allowed.
	 *
	 * HTML modified:
	 *  - Add sandbox="allow-top-navigation" attribute. This allows links to work
	 *    within the iframe sandbox attribute.
	 *
	 * JS modified:
	 *  - Remove IFRAME height restriction of 1000px. Fixes long embed items being
	 *    truncated.
	 *
	 * @since 2.6.0
	 *
	 * @param  string $retval Current embed HTML.
	 * @return string
	 */
	public function modify_iframe( $retval ) {
		// Add 'allow-top-navigation' to allow links to be clicked.
		$retval = str_replace( 'sandbox="', 'sandbox="allow-top-navigation ', $retval );

		// See /wp-includes/js/wp-embed.js.
		if ( SCRIPT_DEBUG ) {
			// Removes WP's hardcoded IFRAME height restriction.
			$retval = str_replace( 'height = 1000;', 'height = height;', $retval );

		// This is for the WP build minified version.
		} else {
			$retval = str_replace( 'g=1e3', 'g=g', $retval );
		}

		return $retval;
	}

	/**
	 * Do stuff when our oEmbed activity header template part is loading.
	 *
	 * Currently, removes wpautop() from the bp_activity_action() function.
	 *
	 * @since 2.6.0
	 *
	 * @param string $slug Template part slug requested.
	 * @param string $name Template part name requested.
	 */
	public function on_activity_header( $slug, $name ) {
		if ( false === $this->is_page() || 'activity' !== $name ) {
			return;
		}

		remove_filter( 'bp_get_activity_action', 'wpautop' );
	}

	/**
	 * Prints the markup for the activity embed comments button.
	 *
	 * Basically a copy of {@link print_embed_comments_button()}, but modified for
	 * the BP activity component.
	 *
	 * @since 2.6.0
	 */
	public function embed_comments_button() {
		if ( ! did_action( 'bp_embed_content' ) || ! bp_is_single_activity() ) {
			return;
		}

		// Make sure our custom permalink shows up in the 'WordPress Embed' block.
		add_filter( 'the_permalink', array( $this, 'filter_embed_url' ) );

		// Only show comment bubble if we have some activity comments.
		$count = bp_activity_get_comment_count();
		if ( empty( $count ) ) {
			return;
		}
	?>

		<div class="wp-embed-comments">
			<a href="<?php bp_activity_thread_permalink(); ?>">
				<span class="dashicons dashicons-admin-comments"></span>
				<?php
				printf(
					wp_kses(
						_n(
							/* translators: accessibility text */
							'%s <span class="screen-reader-text">Comment</span>',
							/* translators: accessibility text */
							'%s <span class="screen-reader-text">Comments</span>',
							intval( $count ),
							'buddypress'
						),
						array(
							'span' => array(
								'class' => true,
							),
						)
					),
					esc_html( number_format_i18n( $count ) )
				);
				?>
			</a>
		</div>

	<?php
	}
}
