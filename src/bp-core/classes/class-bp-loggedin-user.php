<?php
/**
 * Core component classes.
 *
 * @package BuddyPress
 * @subpackage Core
 * @since 15.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Fetch data about a BuddyPress logged-in user.
 *
 * The values are dynamic--they refer to the WordPress current user,
 * which may change as actions and hooks are run.
 * For example: The user may look logged in at the beginning of a poorly
 * authenticated WP REST API request, but will be de-authenticated
 * at `rest_cookie_check_errors()` and then would no longer be considered to
 * be logged in.
 *
 * @property-read int    $id             The logged-in user's ID.
 * @property-read array  $userdata       The logged-in user's userdata.
 * @property-read bool   $is_super_admin Is the logged-in user a super admin?
 * @property-read bool   $is_site_admin  Is the logged-in user a site admin?
 * @property-read string $fullname       The logged-in user's display name.
 * @property-read string $domain         The logged-in user's profile url.
 */
class BP_LoggedIn_User {

	/**
	 * The ID of the logged-in user.
	 *
	 * @since 15.0.0
	 * @var int
	 */
	protected $id;

	/**
	 * The logged-in user's data from the matching wp_users row.
	 *
	 * @since 15.0.0
	 * @var array
	 */
	protected $userdata = array();

	/**
	 * Whether the logged-in user is a network admin or not.
	 *
	 * @since 15.0.0
	 * @var bool
	 */
	protected $is_super_admin = false;

	/**
	 * Whether the logged-in user is a site admin or not.
	 *
	 * @since 15.0.0
	 * @var bool
	 */
	protected $is_site_admin = false;

	/**
	 * The logged-in user's display name.
	 *
	 * @since 15.0.0
	 * @var string
	 */
	protected $fullname = '';

	/**
	 * The logged-in user's profile URL.
	 *
	 * @since 15.0.0
	 * @var string
	 */
	protected $domain = '';

	/**
	 * Constructor.
	 *
	 * Retrieves data for the currently logged-in user.
	 *
	 * @since 15.0.0
	 */
	public function __construct() {
		$this->id = get_current_user_id();
	}

	/**
	 * Magic getter.
	 *
	 * Provides custom logic for getting protected properties.
	 *
	 * @since 15.0.0
	 *
	 * @param string $key Property name.
	 * @return mixed
	 */
	public function __get( $key ) {
		switch ( $key ) {
			case 'id':
				return get_current_user_id();

			case 'userdata':
				return WP_User::get_data_by( 'id', get_current_user_id() );

			case 'fullname':
				$current_user_id = get_current_user_id();

				/**
				* When profile sync is disabled, display_name may diverge from the xprofile
				* fullname field value, and the xprofile field should take precedence.
				*/
				$retval = '';
				if ( bp_disable_profile_sync() ) {
					$retval = xprofile_get_field_data( bp_xprofile_fullname_field_name(), $current_user_id );
				}

				/**
				 * Common case: If BP profile and WP profiles are synced,
				 * then we use the WP value.
				 * This is also used if the xprofile field data is preferred, but empty.
				 */
				if ( ! bp_disable_profile_sync() || ! $retval ) {
					$retval = bp_core_get_user_displayname( $current_user_id );
				}

				return $retval;

			case 'is_super_admin':
			case 'is_site_admin':
				return is_super_admin( get_current_user_id() );

			case 'domain':
				return bp_members_get_user_url( get_current_user_id() );

			default:
				return isset( $this->{$key} ) ? $this->{$key} : null;
		 }
	}

	/**
	 * Magic issetter.
	 *
	 * Used to maintain backward compatibility for properties that are now
	 * accessible only via magic method.
	 *
	 * @since 15.0.0
	 *
	 * @param string $key Property name.
	 * @return bool
	 */
	public function __isset( $key ) {
		switch ( $key ) {
			case 'id':
			case 'userdata':
			case 'fullname':
			case 'is_super_admin':
			case 'is_site_admin':
			case 'domain':
				return true;

			default:
				return isset( $this->{$key} );
		}
	}
}
