<?php
/**
 * Deprecated Functions.
 *
 * @package BuddyPress
 * @deprecated 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * The deprecated-loading constants no longer affect which files are loaded.
 *
 * @since 15.0.0
 */
foreach ( array( 'BP_LOAD_DEPRECATED', 'BP_IGNORE_DEPRECATED' ) as $deprecated_constant ) {
	if ( defined( $deprecated_constant ) ) {
		wp_trigger_error(
			'',
			sprintf(
				/* translators: %s: deprecated constant name. */
				__( 'The %s constant is deprecated and no longer affects deprecated function loading. You can remove it from your configuration.', 'buddypress' ),
				$deprecated_constant
			),
			E_USER_DEPRECATED
		);
	}
}

/**
 * Output whether signup is allowed.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 */
function bp_signup_allowed() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_signup_allowed()' );

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo bp_get_signup_allowed();
}

/**
 * Delete an activity item by activity id.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param array|string $args Optional. See BP_Activity_Activity::get for a
 *                           description of accepted arguments.
 * @return bool
 */
function bp_activity_delete_by_item_id( $args = '' ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	$r = bp_parse_args(
		$args,
		array(
			'item_id'           => false,
			'component'         => false,
			'type'              => false,
			'user_id'           => false,
			'secondary_item_id' => false,
		)
	);

	return bp_activity_delete( $r );
}

/**
 * Delete an activity item by its content.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param int    $user_id   The user id.
 * @param string $content   The activity id.
 * @param string $component The activity component.
 * @param string $type      The activity type.
 * @return bool
 */
function bp_activity_delete_by_content( $user_id, $content, $component, $type ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	return bp_activity_delete(
		array(
			'user_id'   => $user_id,
			'content'   => $content,
			'component' => $component,
			'type'      => $type
		)
	);
}

/**
 * Delete a user's activity for a component.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param int    $user_id   The user id.
 * @param string $component The activity component.
 * @return bool
 */
function bp_activity_delete_for_user_by_component( $user_id, $component ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	return bp_activity_delete(
		array(
			'user_id'   => $user_id,
			'component' => $component
		)
	);
}

/**
 * Delete an activity item by activity id.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 *
 * @param int $activity_id ID of the activity item to be deleted.
 * @return bool
 */
function bp_activity_delete_by_activity_id( $activity_id ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_delete()' );

	return bp_activity_delete( array( 'id' => $activity_id ) );
}

/**
 * Update the spam status of the member on multisite configs.
 *
 * @since 5.0.0
 * @deprecated 15.0.0
 *
 * @param int    $user_id Optional. The user ID to spam or ham.
 * @param string $value   Optional. '0' to mark the user as `ham`, '1' to mark as `spam`.
 * @return bool
 */
function bp_core_update_member_status( $user_id = 0, $value = 0 ) {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	if ( ! is_multisite() || ! $user_id ) {
		return false;
	}

	/**
	 * The `update_user_status()` function is deprecated since WordPress 5.3.0.
	 * Continue to use it if WordPress current major version is lower than 5.3.
	 */
	if ( bp_get_major_wp_version() < 5.3 ) {
		return update_user_status( $user_id, 'spam', $value );
	}

	if ( $value ) {
		$value = '1';
	}

	// Otherwise use the replacement function.
	$user = wp_update_user(
		array(
			'ID'   => $user_id,
			'spam' => $value,
		)
	);

	if ( is_wp_error( $user ) ) {
		return false;
	}

	return true;
}

/**
 * Copy BP options from a single site to multisite config.
 *
 * Run when switching from single to multisite and we need to copy blog options
 * to site options.
 *
 * This function is no longer used. It was previously marked deprecated in 1.6.0
 * without a runtime notice.
 *
 * @since 1.2.4
 * @deprecated 15.0.0
 *
 * @param array $keys Optional. Array of site options.
 * @return bool
 */
function bp_core_activate_site_options( $keys = array() ) {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	if ( ! empty( $keys ) && is_array( $keys ) ) {
		$bp = buddypress();

		$errors = false;

		foreach ( $keys as $key => $default ) {
			if ( empty( $bp->site_options[ $key ] ) ) {
				$bp->site_options[ $key ] = bp_get_option( $key, $default );

				if ( ! bp_update_option( $key, $bp->site_options[ $key ] ) ) {
					$errors = true;
				}
			}
		}

		if ( empty( $errors ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Output the activity type.
 *
 * Previously marked deprecated in 1.5.0 without a runtime notice.
 *
 * @since 1.2.0
 * @deprecated 15.0.0 Use `bp_activity_type()` instead.
 */
function bp_activity_action_name() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_activity_type()' );

	bp_activity_type();
}

/**
 * Return the activity type.
 *
 * Previously marked deprecated in 1.5.0 without a runtime notice.
 *
 * @since 1.2.0
 * @deprecated 15.0.0 Use `bp_get_activity_type()` instead.
 *
 * @return string The activity type.
 */
function bp_get_activity_action_name() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_activity_type()' );

	return bp_get_activity_type();
}

/**
 * Return the member activity feed link.
 *
 * Previously marked deprecated in 1.2.0 without a runtime notice.
 *
 * @since 1.0.0
 * @deprecated 15.0.0 Use `bp_get_member_activity_feed_link()` instead.
 *
 * @return string The member activity feed link.
 */
function bp_get_activities_member_rss_link() {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_get_member_activity_feed_link()' );

	return bp_get_member_activity_feed_link();
}

/**
 * Output the combined activity action and body.
 *
 * Previously marked deprecated in 1.5.0 without a runtime notice.
 *
 * @since 1.0.0
 * @deprecated 15.0.0 Use `bp_activity_action()` and `bp_activity_content_body()` for new templates.
 */
function bp_activity_content() {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo _bp_get_legacy_activity_content();
}

/**
 * Return the combined activity action and body.
 *
 * Previously marked deprecated in 1.5.0 without a runtime notice.
 *
 * @since 1.0.0
 * @deprecated 15.0.0 Use `bp_get_activity_action()` and `bp_get_activity_content_body()` for new templates.
 *
 * @return string The filtered activity action and body.
 */
function bp_get_activity_content() {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	return _bp_get_legacy_activity_content();
}

/**
 * Build the combined content for the deprecated activity template tags.
 *
 * @since 15.0.0
 *
 * @return string The filtered activity action and body.
 */
function _bp_get_legacy_activity_content() {
	$content = bp_get_activity_action() . ' ' . bp_get_activity_content_body();

	/**
	 * Filters activity or activity comment content before display.
	 *
	 * @since 1.0.0
	 *
	 * @param string $content The activity content including its action, or comment content.
	 */
	return apply_filters( 'bp_get_activity_content', $content );
}

/**
 * Retrieve a client friendly version of the root blog name.
 *
 * The blogname option is escaped with esc_html on the way into the database in
 * sanitize_option, we want to reverse this for the plain text arena of emails.
 *
 * @since 1.7.0
 * @since 2.5.0 BuddyPress switched to the email API; this helper was kept for plugins.
 * @deprecated 15.0.0
 *
 * @link https://buddypress.trac.wordpress.org/ticket/4401
 *
 * @param array $args {
 *     Array of optional parameters.
 *     @type string $before  String to appear before the site name in the
 *                           email subject. Default: '['.
 *     @type string $after   String to appear after the site name in the
 *                           email subject. Default: ']'.
 *     @type string $default The default site name, to be used when none is
 *                           found in the database. Default: 'Community'.
 *     @type string $text    Text to append to the site name (ie, the main text of
 *                           the email subject).
 * }
 * @return string Sanitized email subject.
 */
function bp_get_email_subject( $args = array() ) {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	$r = bp_parse_args(
		$args,
		array(
			'before'  => '[',
			'after'   => ']',
			'default' => __( 'Community', 'buddypress' ),
			'text'    => '',
		),
		'get_email_subject'
	);

	$subject = $r['before'] . wp_specialchars_decode( bp_get_option( 'blogname', $r['default'] ), ENT_QUOTES ) . $r['after'] . ' ' . $r['text'];

	/**
	 * Filters a client friendly version of the root blog name.
	 *
	 * @since 1.7.0
	 *
	 * @param string $subject Client friendly version of the root blog name.
	 * @param array  $r       Array of arguments for the email subject.
	 */
	return apply_filters( 'bp_get_email_subject', $subject, $r );
}

/**
 * Render the navigation markup for the logged-in user.
 *
 * Each component adds to this navigation array within its own
 * [component_name]setup_nav() function.
 *
 * This navigation array is the top level navigation, so it contains items such as:
 *      [Blog, Profile, Messages, Groups, Friends] ...
 *
 * The function will also analyze the current component the user is in, to
 * determine whether or not to highlight a particular nav item.
 *
 * The dynamic portion of the hook name, `$nav_item->css_id`, refers to the registered navigation
 * item's CSS ID.
 *
 * @since 1.1.0
 * @deprecated 15.0.0
 */
function bp_get_loggedin_user_nav() {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	$bp = buddypress();

	// Loop through each navigation item.
	foreach ( (array) $bp->members->nav->get_primary() as $nav_item ) {

		$selected = '';

		// If the current component matches the nav item id, then add a highlight CSS class.
		if ( ! bp_is_directory() && ! empty( $bp->active_components[ bp_current_component() ] ) && $bp->active_components[ bp_current_component() ] === $nav_item->css_id ) {
			$selected = ' class="current selected"';
		}

		// If we are viewing another person (current_userid does not equal
		// loggedin_user->id then check to see if the two users are friends.
		// if they are, add a highlight CSS class to the friends nav item
		// if it exists.
		if ( ! bp_is_my_profile() && bp_displayed_user_id() ) {
			$selected = '';

			if ( bp_is_active( 'friends' ) ) {
				if ( $nav_item->css_id === $bp->friends->id ) {
					if ( friends_check_friendship( bp_loggedin_user_id(), bp_displayed_user_id() ) ) {
						$selected = ' class="current selected"';
					}
				}
			}
		}

		// phpcs:ignore WordPress.Security.EscapeOutput
		echo apply_filters_ref_array( 'bp_get_loggedin_user_nav_' . $nav_item->css_id, array( '<li id="li-nav-' . esc_attr( $nav_item->css_id ) . '" ' . $selected . '><a id="my-' . esc_attr( $nav_item->css_id ) . '" href="' . esc_url( $nav_item->link ) . '">' . esc_html( $nav_item->name ) . '</a></li>', &$nav_item ) );
	}

	// Always add a log out list item to the end of the navigation.
	$logout_link = '<li><a id="wp-logout" href="' . esc_url( wp_logout_url( bp_get_root_url() ) ) . '">' . esc_html__( 'Log Out', 'buddypress' ) . '</a></li>';

	// phpcs:ignore WordPress.Security.EscapeOutput
	echo apply_filters( 'bp_logout_nav_link', $logout_link );
}

/**
 * Adds the BuddyPress block category for third-party blocks.
 *
 * BuddyPress stopped using this category for its own blocks in 12.0.0.
 * The `block_categories_all` callback is now `bp_add_block_category` at priority 1.
 *
 * @since 5.0.0
 * @deprecated 15.0.0 Use `bp_add_block_category()` instead.
 *
 * @param array[]             $categories          Optional. Block categories.
 * @param string|WP_Post|null $editor_name_or_post Optional. Editor name or post being loaded.
 * @return array[] Block categories, including the BuddyPress category.
 */
function bp_block_category( $categories = array(), $editor_name_or_post = null ) {
	_deprecated_function( __FUNCTION__, '15.0.0', 'bp_add_block_category()' );

	return bp_add_block_category( $categories, $editor_name_or_post );
}

/**
 * Output the Profile Updates option for the activity filter dropdown.
 *
 * @since 2.0.0
 * @deprecated 15.0.0
 */
function xprofile_activity_filter_options() {
	_deprecated_function( __FUNCTION__, '15.0.0' );

	?>

	<option value="updated_profile"><?php esc_html_e( 'Profile Updates', 'buddypress' ); ?></option>

	<?php
}
