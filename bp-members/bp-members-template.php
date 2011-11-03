<?php
/**
 * BuddyPress Member Template Tags
 *
 * Functions that are safe to use inside your template files and themes
 *
 * @package BuddyPress
 * @subpackage Members
 */

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/**
 * Output the members component slug
 *
 * @package BuddyPress
 * @subpackage Members Template
 * @since 1.5
 *
 * @uses bp_get_members_slug()
 */
function bp_members_slug() {
	echo bp_get_members_slug();
}
	/**
	 * Return the members component slug
	 *
	 * @package BuddyPress
	 * @subpackage Members Template
	 * @since 1.5
	 */
	function bp_get_members_slug() {
		global $bp;
		return apply_filters( 'bp_get_members_slug', $bp->members->slug );
	}

/**
 * Output the members component root slug
 *
 * @package BuddyPress
 * @subpackage Members Template
 * @since 1.5
 *
 * @uses bp_get_members_root_slug()
 */
function bp_members_root_slug() {
	echo bp_get_members_root_slug();
}
	/**
	 * Return the members component root slug
	 *
	 * @package BuddyPress
	 * @subpackage Members Template
	 * @since 1.5
	 */
	function bp_get_members_root_slug() {
		global $bp;
		return apply_filters( 'bp_get_members_root_slug', $bp->members->root_slug );
	}

/**
 * Output member directory permalink
 *
 * @package BuddyPress
 * @subpackage Members Template
 * @since 1.5
 * @uses bp_get_members_directory_permalink()
 */
function bp_members_directory_permalink() {
	echo bp_get_members_directory_permalink();
}
	/**
	 * Return member directory permalink
	 *
	 * @package BuddyPress
	 * @subpackage Members Template
	 * @since 1.5
	 * @uses apply_filters()
	 * @uses traisingslashit()
	 * @uses bp_get_root_domain()
	 * @uses bp_get_members_root_slug()
	 * @return string
	 */
	function bp_get_members_directory_permalink() {
		return apply_filters( 'bp_get_members_directory_permalink', trailingslashit( bp_get_root_domain() . '/' . bp_get_members_root_slug() ) );
	}

/**
 * Output the sign-up slug
 *
 * @package BuddyPress
 * @subpackage Members Template
 * @since 1.5
 *
 * @uses bp_get_signup_slug()
 */
function bp_signup_slug() {
	echo bp_get_signup_slug();
}
	/**
	 * Return the sign-up slug
	 *
	 * @package BuddyPress
	 * @subpackage Members Template
	 * @since 1.5
	 */
	function bp_get_signup_slug() {
		global $bp;

		if ( !empty( $bp->pages->register->slug ) )
			$slug = $bp->pages->register->slug;
		elseif ( defined( 'BP_REGISTER_SLUG' ) )
			$slug = BP_REGISTER_SLUG;
		else
			$slug = 'register';

		return apply_filters( 'bp_get_signup_slug', $slug );
	}

/**
 * Output the activation slug
 *
 * @package BuddyPress
 * @subpackage Members Template
 * @since 1.5
 *
 * @uses bp_get_activate_slug()
 */
function bp_activate_slug() {
	echo bp_get_activate_slug();
}
	/**
	 * Return the activation slug
	 *
	 * @package BuddyPress
	 * @subpackage Members Template
	 * @since 1.5
	 */
	function bp_get_activate_slug() {
		global $bp;

		if ( !empty( $bp->pages->activate->slug ) )
			$slug = $bp->pages->activate->slug;
		elseif ( defined( 'BP_ACTIVATION_SLUG' ) )
			$slug = BP_ACTIVATION_SLUG;
		else
			$slug = 'activate';

		return apply_filters( 'bp_get_activate_slug', $slug );
	}

/***
 * Members template loop that will allow you to loop all members or friends of a member
 * if you pass a user_id.
 */

class BP_Core_Members_Template {
	var $current_member = -1;
	var $member_count;
	var $members;
	var $member;

	var $in_the_loop;

	var $pag_page;
	var $pag_num;
	var $pag_links;
	var $total_member_count;

	function bp_core_members_template( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras, $exclude, $meta_key, $meta_value ) {
		$this->__construct( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras, $exclude, $meta_key, $meta_value );
	}

	function __construct( $type, $page_number, $per_page, $max, $user_id, $search_terms, $include, $populate_extras, $exclude, $meta_key, $meta_value ) {
		global $bp;

		$this->pag_page  = !empty( $_REQUEST['upage'] ) ? intval( $_REQUEST['upage'] ) : (int)$page_number;
		$this->pag_num   = !empty( $_REQUEST['num'] )   ? intval( $_REQUEST['num'] )   : (int)$per_page;
		$this->type      = $type;

		if ( isset( $_REQUEST['letter'] ) && '' != $_REQUEST['letter'] )
			$this->members = BP_Core_User::get_users_by_letter( $_REQUEST['letter'], $this->pag_num, $this->pag_page, $populate_extras, $exclude );
		else if ( false !== $include )
			$this->members = BP_Core_User::get_specific_users( $include, $this->pag_num, $this->pag_page, $populate_extras );
		else
			$this->members = bp_core_get_users( array( 'type' => $this->type, 'per_page' => $this->pag_num, 'page' => $this->pag_page, 'user_id' => $user_id, 'include' => $include, 'search_terms' => $search_terms, 'populate_extras' => $populate_extras, 'exclude' => $exclude, 'meta_key' => $meta_key, 'meta_value' => $meta_value ) );

		if ( !$max || $max >= (int)$this->members['total'] )
			$this->total_member_count = (int)$this->members['total'];
		else
			$this->total_member_count = (int)$max;

		$this->members = $this->members['users'];

		if ( $max ) {
			if ( $max >= count( $this->members ) ) {
				$this->member_count = count( $this->members );
			} else {
				$this->member_count = (int)$max;
			}
		} else {
			$this->member_count = count( $this->members );
		}

		if ( (int)$this->total_member_count && (int)$this->pag_num ) {
			$this->pag_links = paginate_links( array(
				'base'      => add_query_arg( 'upage', '%#%' ),
				'format'    => '',
				'total'     => ceil( (int)$this->total_member_count / (int)$this->pag_num ),
				'current'   => (int) $this->pag_page,
				'prev_text' => _x( '&larr;', 'Member pagination previous text', 'buddypress' ),
				'next_text' => _x( '&rarr;', 'Member pagination next text', 'buddypress' ),
				'mid_size'   => 1
			) );
		}
	}

	function has_members() {
		if ( $this->member_count )
			return true;

		return false;
	}

	function next_member() {
		$this->current_member++;
		$this->member = $this->members[$this->current_member];

		return $this->member;
	}

	function rewind_members() {
		$this->current_member = -1;
		if ( $this->member_count > 0 ) {
			$this->member = $this->members[0];
		}
	}

	function members() {
		if ( $this->current_member + 1 < $this->member_count ) {
			return true;
		} elseif ( $this->current_member + 1 == $this->member_count ) {
			do_action('member_loop_end');
			// Do some cleaning up after the loop
			$this->rewind_members();
		}

		$this->in_the_loop = false;
		return false;
	}

	function the_member() {
		global $member, $bp;

		$this->in_the_loop = true;
		$this->member = $this->next_member();

		if ( 0 == $this->current_member ) // loop has just started
			do_action('member_loop_start');
	}
}

function bp_rewind_members() {
	global $members_template;

	return $members_template->rewind_members();
}

function bp_has_members( $args = '' ) {
	global $bp, $members_template;

	/***
	 * Set the defaults based on the current page. Any of these will be overridden
	 * if arguments are directly passed into the loop. Custom plugins should always
	 * pass their parameters directly to the loop.
	 */
	$type         = 'active';
	$user_id      = 0;
	$page         = 1;
	$search_terms = null;

	// User filtering
	if ( !empty( $bp->displayed_user->id ) )
		$user_id = $bp->displayed_user->id;

	// type: active ( default ) | random | newest | popular | online | alphabetical
	$defaults = array(
		'type'            => $type,
		'page'            => $page,
		'per_page'        => 20,
		'max'             => false,

		'include'         => false,         // Pass a user_id or a list (comma-separated or array) of user_ids to only show these users
		'exclude'         => false,         // Pass a user_id or a list (comma-separated or array) of user_ids to exclude these users

		'user_id'         => $user_id,      // Pass a user_id to only show friends of this user
		'search_terms'    => $search_terms, // Pass search_terms to filter users by their profile data

		'meta_key'        => false,	        // Only return users with this usermeta
		'meta_value'	  => false,	        // Only return users where the usermeta value matches. Requires meta_key

		'populate_extras' => true           // Fetch usermeta? Friend count, last active etc.
	);

	$r = wp_parse_args( $args, $defaults );
	extract( $r );

	// Pass a filter if ?s= is set.
	if ( is_null( $search_terms ) ) {
		if ( !empty( $_REQUEST['s'] ) )
			$search_terms = $_REQUEST['s'];
		else
			$search_terms = false;
	}

	// Set per_page to max if max is larger than per_page
	if ( !empty( $max ) && ( $per_page > $max ) )
		$per_page = $max;

	// Make sure we return no members if we looking at friendship requests and there are none.
	if ( empty( $include ) && bp_is_friends_component() && bp_is_current_action( 'requests' ) )
		return false;

	$members_template = new BP_Core_Members_Template( $type, $page, $per_page, $max, $user_id, $search_terms, $include, (bool)$populate_extras, $exclude, $meta_key, $meta_value );
	return apply_filters( 'bp_has_members', $members_template->has_members(), $members_template );
}

function bp_the_member() {
	global $members_template;
	return $members_template->the_member();
}

function bp_members() {
	global $members_template;
	return $members_template->members();
}

function bp_members_pagination_count() {
	echo bp_get_members_pagination_count();
}
	function bp_get_members_pagination_count() {
		global $bp, $members_template;

		if ( empty( $members_template->type ) )
			$members_template->type = '';

		$start_num = intval( ( $members_template->pag_page - 1 ) * $members_template->pag_num ) + 1;
		$from_num  = bp_core_number_format( $start_num );
		$to_num    = bp_core_number_format( ( $start_num + ( $members_template->pag_num - 1 ) > $members_template->total_member_count ) ? $members_template->total_member_count : $start_num + ( $members_template->pag_num - 1 ) );
		$total     = bp_core_number_format( $members_template->total_member_count );

		if ( 'active' == $members_template->type )
			$pag = sprintf( __( 'Viewing member %1$s to %2$s (of %3$s active members)', 'buddypress' ), $from_num, $to_num, $total );
		else if ( 'popular' == $members_template->type )
			$pag = sprintf( __( 'Viewing member %1$s to %2$s (of %3$s members with friends)', 'buddypress' ), $from_num, $to_num, $total );
		else if ( 'online' == $members_template->type )
			$pag = sprintf( __( 'Viewing member %1$s to %2$s (of %3$s members online)', 'buddypress' ), $from_num, $to_num, $total );
		else
			$pag = sprintf( __( 'Viewing member %1$s to %2$s (of %3$s members)', 'buddypress' ), $from_num, $to_num, $total );

		return apply_filters( 'bp_members_pagination_count', $pag );
	}

function bp_members_pagination_links() {
	echo bp_get_members_pagination_links();
}
	function bp_get_members_pagination_links() {
		global $members_template;

		return apply_filters( 'bp_get_members_pagination_links', $members_template->pag_links );
	}

/**
 * bp_member_user_id()
 *
 * Echo id from bp_get_member_user_id()
 *
 * @uses bp_get_member_user_id()
 */
function bp_member_user_id() {
	echo bp_get_member_user_id();
}
	/**
	 * bp_get_member_user_id()
	 *
	 * Get the id of the user in a members loop
	 *
	 * @global object $members_template
	 * @return string Members id
	 */
	function bp_get_member_user_id() {
		global $members_template;

		return apply_filters( 'bp_get_member_user_id', $members_template->member->id );
	}

/**
 * bp_member_user_nicename()
 *
 * Echo nicename from bp_get_member_user_nicename()
 *
 * @uses bp_get_member_user_nicename()
 */
function bp_member_user_nicename() {
	echo bp_get_member_user_nicename();
}
	/**
	 * bp_get_member_user_nicename()
	 *
	 * Get the nicename of the user in a members loop
	 *
	 * @global object $members_template
	 * @return string Members nicename
	 */
	function bp_get_member_user_nicename() {
		global $members_template;
		return apply_filters( 'bp_get_member_user_nicename', $members_template->member->user_nicename );
	}

/**
 * bp_member_user_login()
 *
 * Echo login from bp_get_member_user_login()
 *
 * @uses bp_get_member_user_login()
 */
function bp_member_user_login() {
	echo bp_get_member_user_login();
}
	/**
	 * bp_get_member_user_login()
	 *
	 * Get the login of the user in a members loop
	 *
	 * @global object $members_template
	 * @return string Members login
	 */
	function bp_get_member_user_login() {
		global $members_template;
		return apply_filters( 'bp_get_member_user_login', $members_template->member->user_login );
	}

/**
 * bp_member_user_email()
 *
 * Echo email address from bp_get_member_user_email()
 *
 * @uses bp_get_member_user_email()
 */
function bp_member_user_email() {
	echo bp_get_member_user_email();
}
	/**
	 * bp_get_member_user_email()
	 *
	 * Get the email address of the user in a members loop
	 *
	 * @global object $members_template
	 * @return string Members email address
	 */
	function bp_get_member_user_email() {
		global $members_template;
		return apply_filters( 'bp_get_member_user_email', $members_template->member->user_email );
	}

function bp_member_is_loggedin_user() {
	global $bp, $members_template;
	return apply_filters( 'bp_member_is_loggedin_user', $bp->loggedin_user->id == $members_template->member->id ? true : false );
}

function bp_member_avatar( $args = '' ) {
	echo apply_filters( 'bp_member_avatar', bp_get_member_avatar( $args ) );
}
	function bp_get_member_avatar( $args = '' ) {
		global $bp, $members_template;

		$defaults = array(
			'type' => 'thumb',
			'width' => false,
			'height' => false,
			'class' => 'avatar',
			'id' => false,
			'alt' => __( 'Profile picture of %s', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_member_avatar', bp_core_fetch_avatar( array( 'item_id' => $members_template->member->id, 'type' => $type, 'alt' => $alt, 'css_id' => $id, 'class' => $class, 'width' => $width, 'height' => $height, 'email' => $members_template->member->user_email ) ) );
	}

function bp_member_permalink() {
	echo bp_get_member_permalink();
}
	function bp_get_member_permalink() {
		global $members_template;

		return apply_filters( 'bp_get_member_permalink', bp_core_get_user_domain( $members_template->member->id, $members_template->member->user_nicename, $members_template->member->user_login ) );
	}
	function bp_member_link() { echo bp_get_member_permalink(); }
	function bp_get_member_link() { return bp_get_member_permalink(); }

/**
 * Echoes bp_get_member_name()
 *
 * @package BuddyPress
 */
function bp_member_name() {
	echo apply_filters( 'bp_member_name', bp_get_member_name() );
}
	/**
	 * Used inside a bp_has_members() loop, this function returns a user's full name
	 *
	 * Full name is, by default, pulled from xprofile's Full Name field. When this field is
	 * empty, we try to get an alternative name from the WP users table, in the following order
	 * of preference: display_name, user_nicename, user_login.
	 *
	 * @package BuddyPress
	 *
	 * @uses apply_filters() Filter bp_get_the_member_name() to alter the function's output
	 * @return str The user's fullname for display
	 */
	function bp_get_member_name() {
		global $members_template;

		// Generally, this only fires when xprofile is disabled
		if ( empty( $members_template->member->fullname ) ) {
			// Our order of preference for alternative fullnames
			$name_stack = array(
				'display_name',
				'user_nicename',
				'user_login'
			);

			foreach ( $name_stack as $source ) {
				if ( !empty( $members_template->member->{$source} ) ) {
					// When a value is found, set it as fullname and be done
					// with it
					$members_template->member->fullname = $members_template->member->{$source};
					break;
				}
			}
		}

		return apply_filters( 'bp_get_member_name', $members_template->member->fullname );
	}
	add_filter( 'bp_get_member_name', 'wp_filter_kses' );
	add_filter( 'bp_get_member_name', 'stripslashes' );
	add_filter( 'bp_get_member_name', 'strip_tags' );

function bp_member_last_active() {
	echo bp_get_member_last_active();
}
	function bp_get_member_last_active() {
		global $members_template;

		if ( isset( $members_template->member->last_activity ) )
			$last_activity = bp_core_get_last_activity( $members_template->member->last_activity, __( 'active %s', 'buddypress' ) );
		else
			$last_activity = __( 'Never active', 'buddypress' );

		return apply_filters( 'bp_member_last_active', $last_activity );
	}

function bp_member_latest_update( $args = '' ) {
	echo bp_get_member_latest_update( $args );
}
	function bp_get_member_latest_update( $args = '' ) {
		global $bp, $members_template;

		$defaults = array(
			'length'    => 225,
			'view_link' => true
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		if ( !bp_is_active( 'activity' ) || empty( $members_template->member->latest_update ) || !$update = maybe_unserialize( $members_template->member->latest_update ) )
			return false;

		$update_content = apply_filters( 'bp_get_activity_latest_update_excerpt', sprintf( _x( '- &quot;%s &quot;', 'member latest update in member directory', 'buddypress' ), trim( strip_tags( bp_create_excerpt( $update['content'], $length ) ) ) ) );

		// If $view_link is true and the text returned by bp_create_excerpt() is different from the original text (ie it's
		// been truncated), add the "View" link.
		if ( $view_link && ( $update_content != $update['content'] ) ) {
			$view = __( 'View', 'buddypress' );

			$update_content .= '<span class="activity-read-more"><a href="' . bp_activity_get_permalink( $update['id'] ) . '" rel="nofollow">' . $view . '</a></span>';
		}

		return apply_filters( 'bp_get_member_latest_update', $update_content );
	}

function bp_member_profile_data( $args = '' ) {
	echo bp_get_member_profile_data( $args );
}
	function bp_get_member_profile_data( $args = '' ) {
		global $bp, $members_template;

		if ( !bp_is_active( 'xprofile' ) )
			return false;

		// Declare local variables
		$data    = false;
		$user_id = 0;

		// Guess at default $user_id
		if ( !empty( $members_template->member->id ) )
			$user_id = $members_template->member->id;
		elseif ( !empty( $bp->displayed_user->id ) )
			$user_id = $bp->displayed_user->id;

		$defaults = array(
			'field'   => false,   // Field name
			'user_id' => $user_id
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Populate the user if it hasn't been already.
		if ( empty( $members_template->member->profile_data ) && method_exists( 'BP_XProfile_ProfileData', 'get_all_for_user' ) )
			$members_template->member->profile_data = BP_XProfile_ProfileData::get_all_for_user( $user_id );

		// Get the field data if there is data to get
		if ( !empty( $members_template->member->profile_data ) )
			$data = xprofile_format_profile_field( $members_template->member->profile_data[$field]['field_type'], $members_template->member->profile_data[$field]['field_data'] );

		return apply_filters( 'bp_get_member_profile_data', $data );
	}

function bp_member_registered() {
	echo bp_get_member_registered();
}
	function bp_get_member_registered() {
		global $members_template;

		$registered = esc_attr( bp_core_get_last_activity( $members_template->member->user_registered, __( 'registered %s', 'buddypress' ) ) );

		return apply_filters( 'bp_member_last_active', $registered );
	}

function bp_member_random_profile_data() {
	global $members_template;

	if ( bp_is_active( 'xprofile' ) ) { ?>
		<?php $random_data = xprofile_get_random_profile_data( $members_template->member->id, true ); ?>
			<strong><?php echo wp_filter_kses( $random_data[0]->name ) ?></strong>
			<?php echo wp_filter_kses( $random_data[0]->value ) ?>
	<?php }
}

function bp_member_hidden_fields() {
	if ( isset( $_REQUEST['s'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['s'] ) . '" name="search_terms" />';

	if ( isset( $_REQUEST['letter'] ) )
		echo '<input type="hidden" id="selected_letter" value="' . esc_attr( $_REQUEST['letter'] ) . '" name="selected_letter" />';

	if ( isset( $_REQUEST['members_search'] ) )
		echo '<input type="hidden" id="search_terms" value="' . esc_attr( $_REQUEST['members_search'] ) . '" name="search_terms" />';
}

function bp_directory_members_search_form() {
	global $bp;

	$default_search_value = bp_get_search_default_text( 'members' );
	$search_value         = !empty( $_REQUEST['s'] ) ? stripslashes( $_REQUEST['s'] ) : $default_search_value; ?>

	<form action="" method="get" id="search-members-form">
		<label><input type="text" name="s" id="members_search" value="<?php echo esc_attr( $search_value ) ?>"  onfocus="if (this.value == '<?php echo $default_search_value ?>') {this.value = '';}" onblur="if (this.value == '') {this.value = '<?php echo $default_search_value ?>';}" /></label>
		<input type="submit" id="members_search_submit" name="members_search_submit" value="<?php _e( 'Search', 'buddypress' ) ?>" />
	</form>

<?php
}

function bp_total_site_member_count() {
	echo bp_get_total_site_member_count();
}
	function bp_get_total_site_member_count() {
		return apply_filters( 'bp_get_total_site_member_count', bp_core_number_format( bp_core_get_total_member_count() ) );
	}

/** Navigation and other misc template tags **/

/**
 * Uses the $bp->bp_nav global to render out the navigation within a BuddyPress install.
 * Each component adds to this navigation array within its own [component_name]setup_nav() function.
 *
 * This navigation array is the top level navigation, so it contains items such as:
 *      [Blog, Profile, Messages, Groups, Friends] ...
 *
 * The function will also analyze the current component the user is in, to determine whether
 * or not to highlight a particular nav item.
 *
 * @package BuddyPress Core
 * @todo Move to a back-compat file?
 * @deprecated Does not seem to be called anywhere in the core
 * @global object $bp Global BuddyPress settings object
 */
function bp_get_loggedin_user_nav() {
	global $bp;

	// Loop through each navigation item
	foreach( (array) $bp->bp_nav as $nav_item ) {
		// If the current component matches the nav item id, then add a highlight CSS class.
		if ( !bp_is_directory() && $bp->active_components[$bp->current_component] == $nav_item['css_id'] )
			$selected = ' class="current selected"';
		else
			$selected = '';

		/* If we are viewing another person (current_userid does not equal loggedin_user->id)
		   then check to see if the two users are friends. if they are, add a highlight CSS class
		   to the friends nav item if it exists. */
		if ( !bp_is_my_profile() && $bp->displayed_user->id ) {
			$selected = '';

			if ( bp_is_active( 'friends' ) ) {
				if ( $nav_item['css_id'] == $bp->friends->id ) {
					if ( friends_check_friendship( $bp->loggedin_user->id, $bp->displayed_user->id ) )
						$selected = ' class="current selected"';
				}
			}
		}

		// echo out the final list item
		echo apply_filters_ref_array( 'bp_get_loggedin_user_nav_' . $nav_item['css_id'], array( '<li id="li-nav-' . $nav_item['css_id'] . '" ' . $selected . '><a id="my-' . $nav_item['css_id'] . '" href="' . $nav_item['link'] . '">' . $nav_item['name'] . '</a></li>', &$nav_item ) );
	}

	// Always add a log out list item to the end of the navigation
	$logout_link = '<li><a id="wp-logout" href="' .  wp_logout_url( bp_get_root_domain() ) . '">' . __( 'Log Out', 'buddypress' ) . '</a></li>';

	echo apply_filters( 'bp_logout_nav_link', $logout_link );
}

/**
 * Uses the $bp->bp_nav global to render out the user navigation when viewing another user other than
 * yourself.
 *
 * @package BuddyPress Core
 * @global object $bp Global BuddyPress settings object
 */
function bp_get_displayed_user_nav() {
	global $bp;

	foreach ( (array)$bp->bp_nav as $user_nav_item ) {
		if ( !$user_nav_item['show_for_displayed_user'] && !bp_is_my_profile() )
			continue;

		if ( $bp->current_component == $user_nav_item['slug'] )
			$selected = ' class="current selected"';
		else
			$selected = '';

		if ( $bp->loggedin_user->domain )
			$link = str_replace( $bp->loggedin_user->domain, $bp->displayed_user->domain, $user_nav_item['link'] );
		else
			$link = $bp->displayed_user->domain . $user_nav_item['link'];

		echo apply_filters_ref_array( 'bp_get_displayed_user_nav_' . $user_nav_item['css_id'], array( '<li id="' . $user_nav_item['css_id'] . '-personal-li" ' . $selected . '><a id="user-' . $user_nav_item['css_id'] . '" href="' . $link . '">' . $user_nav_item['name'] . '</a></li>', &$user_nav_item ) );
	}
}

/** Avatars *******************************************************************/

function bp_loggedin_user_avatar( $args = '' ) {
	echo bp_get_loggedin_user_avatar( $args );
}
	function bp_get_loggedin_user_avatar( $args = '' ) {
		global $bp;

		$defaults = array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
			'html'   => true,
			'alt'    => __( 'Profile picture of %s', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_loggedin_user_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->loggedin_user->id, 'type' => $type, 'width' => $width, 'height' => $height, 'html' => $html, 'alt' => $alt ) ) );
	}

function bp_displayed_user_avatar( $args = '' ) {
	echo bp_get_displayed_user_avatar( $args );
}
	function bp_get_displayed_user_avatar( $args = '' ) {
		global $bp;

		$defaults = array(
			'type'   => 'thumb',
			'width'  => false,
			'height' => false,
			'html'   => true,
			'alt'    => __( 'Profile picture of %s', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		return apply_filters( 'bp_get_displayed_user_avatar', bp_core_fetch_avatar( array( 'item_id' => $bp->displayed_user->id, 'type' => $type, 'width' => $width, 'height' => $height, 'html' => $html, 'alt' => $alt ) ) );
	}

function bp_displayed_user_email() {
	echo bp_get_displayed_user_email();
}
	function bp_get_displayed_user_email() {
		global $bp;

		// If displayed user exists, return email address
		if ( isset( $bp->displayed_user->userdata->user_email ) )
			$retval = $bp->displayed_user->userdata->user_email;
		else
			$retval = '';

		return apply_filters( 'bp_get_displayed_user_email', esc_attr( $retval ) );
	}

function bp_last_activity( $user_id = 0 ) {
	echo apply_filters( 'bp_last_activity', bp_get_last_activity( $user_id ) );
}
	function bp_get_last_activity( $user_id = 0 ) {
		global $bp;

		if ( empty( $user_id ) )
			$user_id = $bp->displayed_user->id;

		$last_activity = bp_core_get_last_activity( bp_get_user_meta( $user_id, 'last_activity', true ), __('active %s', 'buddypress') );

		return apply_filters( 'bp_get_last_activity', $last_activity );
	}

function bp_user_firstname() {
	echo bp_get_user_firstname();
}
	function bp_get_user_firstname( $name = false ) {
		global $bp;

		// Try to get displayed user
		if ( empty( $name ) )
			$name = $bp->displayed_user->fullname;

		// Fall back on logged in user
		if ( empty( $name ) )
			$name = $bp->loggedin_user->fullname;

		$fullname = (array)explode( ' ', $name );

		return apply_filters( 'bp_get_user_firstname', $fullname[0], $fullname );
	}

function bp_loggedin_user_link() {
	echo bp_get_loggedin_user_link();
}
	function bp_get_loggedin_user_link() {
		return apply_filters( 'bp_get_loggedin_user_link', bp_loggedin_user_domain() );
	}

function bp_displayed_user_link() {
	echo bp_get_displayed_user_link();
}
	function bp_get_displayed_user_link() {
		return apply_filters( 'bp_get_displayed_user_link', bp_displayed_user_domain() );
	}
	function bp_user_link() { bp_displayed_user_domain(); } // Deprecated.

function bp_displayed_user_id() {
	global $bp;
	return apply_filters( 'bp_displayed_user_id', !empty( $bp->displayed_user->id ) ? $bp->displayed_user->id : 0 );
}
	function bp_current_user_id() { return bp_displayed_user_id(); }

function bp_loggedin_user_id() {
	global $bp;
	return apply_filters( 'bp_loggedin_user_id', !empty( $bp->loggedin_user->id ) ? $bp->loggedin_user->id : 0 );
}

function bp_displayed_user_domain() {
	global $bp;
	return apply_filters( 'bp_displayed_user_domain', isset( $bp->displayed_user->domain ) ? $bp->displayed_user->domain : '' );
}

function bp_loggedin_user_domain() {
	global $bp;
	return apply_filters( 'bp_loggedin_user_domain', isset( $bp->loggedin_user->domain ) ? $bp->loggedin_user->domain : '' );
}

function bp_displayed_user_fullname() {
	echo bp_get_displayed_user_fullname();
}
	function bp_get_displayed_user_fullname() {
		global $bp;
		return apply_filters( 'bp_displayed_user_fullname', isset( $bp->displayed_user->fullname ) ? $bp->displayed_user->fullname : '' );
	}
	function bp_user_fullname() { echo bp_get_displayed_user_fullname(); }


function bp_loggedin_user_fullname() {
	echo bp_get_loggedin_user_fullname();
}
	function bp_get_loggedin_user_fullname() {
		global $bp;
		return apply_filters( 'bp_get_loggedin_user_fullname', isset( $bp->loggedin_user->fullname ) ? $bp->loggedin_user->fullname : '' );
	}

function bp_displayed_user_username() {
	echo bp_get_displayed_user_username();
}
	function bp_get_displayed_user_username() {
		global $bp;

		if ( !empty( $bp->displayed_user->id ) ) {
			$username = bp_core_get_username( $bp->displayed_user->id, $bp->displayed_user->userdata->user_nicename, $bp->displayed_user->userdata->user_login );
		} else {
			$username = '';
		}

		return apply_filters( 'bp_get_displayed_user_username', $username );
	}

function bp_loggedin_user_username() {
	echo bp_get_loggedin_user_username();
}
	function bp_get_loggedin_user_username() {
		global $bp;

		if ( !empty( $bp->loggedin_user->id ) ) {
			$username = bp_core_get_username( $bp->loggedin_user->id, $bp->loggedin_user->userdata->user_nicename, $bp->loggedin_user->userdata->user_login );
		} else {
			$username = '';
		}

		return apply_filters( 'bp_get_loggedin_user_username', $username );
	}

/** Signup Form ***************************************************************/

function bp_has_custom_signup_page() {
	if ( locate_template( array( 'register.php' ), false ) || locate_template( array( '/registration/register.php' ), false ) )
		return true;

	return false;
}

function bp_signup_page() {
	echo bp_get_signup_page();
}
	function bp_get_signup_page() {
		global $bp;

		if ( bp_has_custom_signup_page() ) {
			$page = trailingslashit( bp_get_root_domain() . '/' . bp_get_signup_slug() );
		} else {
			$page = bp_get_root_domain() . '/wp-signup.php';
		}

		return apply_filters( 'bp_get_signup_page', $page );
	}

function bp_has_custom_activation_page() {
	if ( locate_template( array( 'activate.php' ), false ) || locate_template( array( '/registration/activate.php' ), false ) )
		return true;

	return false;
}

function bp_activation_page() {
	echo bp_get_activation_page();
}
	function bp_get_activation_page() {
		global $bp;

		if ( bp_has_custom_activation_page() )
			$page = trailingslashit( bp_get_root_domain() . '/' . $bp->pages->activate->slug );
		else
			$page = trailingslashit( bp_get_root_domain() ) . 'wp-activate.php';

		return apply_filters( 'bp_get_activation_page', $page );
	}

function bp_signup_username_value() {
	echo bp_get_signup_username_value();
}
	function bp_get_signup_username_value() {
		$value = '';
		if ( isset( $_POST['signup_username'] ) )
			$value = $_POST['signup_username'];

		return apply_filters( 'bp_get_signup_username_value', $value );
	}

function bp_signup_email_value() {
	echo bp_get_signup_email_value();
}
	function bp_get_signup_email_value() {
		$value = '';
		if ( isset( $_POST['signup_email'] ) )
			$value = $_POST['signup_email'];

		return apply_filters( 'bp_get_signup_email_value', $value );
	}

function bp_signup_with_blog_value() {
	echo bp_get_signup_with_blog_value();
}
	function bp_get_signup_with_blog_value() {
		$value = '';
		if ( isset( $_POST['signup_with_blog'] ) )
			$value = $_POST['signup_with_blog'];

		return apply_filters( 'bp_get_signup_with_blog_value', $value );
	}

function bp_signup_blog_url_value() {
	echo bp_get_signup_blog_url_value();
}
	function bp_get_signup_blog_url_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_url'] ) )
			$value = $_POST['signup_blog_url'];

		return apply_filters( 'bp_get_signup_blog_url_value', $value );
	}

function bp_signup_blog_title_value() {
	echo bp_get_signup_blog_title_value();
}
	function bp_get_signup_blog_title_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_title'] ) )
			$value = $_POST['signup_blog_title'];

		return apply_filters( 'bp_get_signup_blog_title_value', $value );
	}

function bp_signup_blog_privacy_value() {
	echo bp_get_signup_blog_privacy_value();
}
	function bp_get_signup_blog_privacy_value() {
		$value = '';
		if ( isset( $_POST['signup_blog_privacy'] ) )
			$value = $_POST['signup_blog_privacy'];

		return apply_filters( 'bp_get_signup_blog_privacy_value', $value );
	}

function bp_signup_avatar_dir_value() {
	echo bp_get_signup_avatar_dir_value();
}
	function bp_get_signup_avatar_dir_value() {
		global $bp;

		// Check if signup_avatar_dir is passed
		if ( !empty( $_POST['signup_avatar_dir'] ) )
			$signup_avatar_dir = $_POST['signup_avatar_dir'];

		// If not, check if global is set
		elseif ( !empty( $bp->signup->avatar_dir ) )
			$signup_avatar_dir = $bp->signup->avatar_dir;

		// If not, set false
		else
			$signup_avatar_dir = false;

		return apply_filters( 'bp_get_signup_avatar_dir_value', $bp->signup->avatar_dir );
	}

function bp_current_signup_step() {
	echo bp_get_current_signup_step();
}
	function bp_get_current_signup_step() {
		global $bp;

		return $bp->signup->step;
	}

function bp_signup_avatar( $args = '' ) {
	echo bp_get_signup_avatar( $args );
}
	function bp_get_signup_avatar( $args = '' ) {
		global $bp;

		$defaults = array(
			'size' => bp_core_avatar_full_width(),
			'class' => 'avatar',
			'alt' => __( 'Your Avatar', 'buddypress' )
		);

		$r = wp_parse_args( $args, $defaults );
		extract( $r, EXTR_SKIP );

		// Avatar DIR is found
		if ( $signup_avatar_dir = bp_get_signup_avatar_dir_value() ) {
			$gravatar_img = bp_core_fetch_avatar( array(
				'item_id'    => $signup_avatar_dir,
				'object'     => 'signup',
				'avatar_dir' => 'avatars/signups',
				'type'       => 'full',
				'width'      => $size,
				'height'     => $size,
				'alt'        => $alt,
				'class'      => $class
			) );

		// No avatar DIR was found
		} else {

			// Set default gravatar type
			if ( empty( $bp->grav_default->user ) )
				$default_grav = 'wavatar';
			else if ( 'mystery' == $bp->grav_default->user )
				$default_grav = BP_PLUGIN_URL . '/bp-core/images/mystery-man.jpg';
			else
				$default_grav = $bp->grav_default->user;

			// Create
			$gravatar_url    = apply_filters( 'bp_gravatar_url', 'http://www.gravatar.com/avatar/' );
			$md5_lcase_email = md5( strtolower( bp_get_signup_email_value() ) );
			$gravatar_img    = '<img src="' . $gravatar_url . $md5_lcase_email . '?d=' . $default_grav . '&amp;s=' . $size . '" width="' . $size . '" height="' . $size . '" alt="' . $alt . '" class="' . $class . '" />';
		}

		return apply_filters( 'bp_get_signup_avatar', $gravatar_img, $args );
	}

function bp_signup_allowed() {
	echo bp_get_signup_allowed();
}
	function bp_get_signup_allowed() {
		global $bp;

		$signup_allowed = false;

		if ( is_multisite() ) {
			if ( in_array( $bp->site_options['registration'], array( 'all', 'user' ) ) )
				$signup_allowed = true;

		} else {
			if ( get_option( 'users_can_register') )
				$signup_allowed = true;
		}

		return apply_filters( 'bp_get_signup_allowed', $signup_allowed );
	}

/**
 * Hook member activity feed to <head>
 *
 * @since 1.5
 */
function bp_members_activity_feed() {
	if ( !bp_is_active( 'activity' ) || !bp_is_user() )
		return; ?>

	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo( 'name' ) ?> | <?php bp_displayed_user_fullname() ?> | <?php _e( 'Activity RSS Feed', 'buddypress' ) ?>" href="<?php bp_member_activity_feed_link() ?>" />

<?php
}
add_action( 'bp_head', 'bp_members_activity_feed' );


function bp_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {
	echo bp_get_members_component_link( $component, $action, $query_args, $nonce );
}
	function bp_get_members_component_link( $component, $action = '', $query_args = '', $nonce = false ) {
		global $bp;

		// Must be displayed user
		if ( empty( $bp->displayed_user->id ) )
			return;

		// Append $action to $url if there is no $type
		if ( !empty( $action ) )
			$url = $bp->displayed_user->domain . $bp->{$component}->slug . '/' . $action;
		else
			$url = $bp->displayed_user->domain . $bp->{$component}->slug;

		// Add a slash at the end of our user url
		$url = trailingslashit( $url );

		// Add possible query arg
		if ( !empty( $query_args ) && is_array( $query_args ) )
			$url = add_query_arg( $query_args, $url );

		// To nonce, or not to nonce...
		if ( true === $nonce )
			$url = wp_nonce_url( $url );
		elseif ( is_string( $nonce ) )
			$url = wp_nonce_url( $url, $nonce );

		// Return the url, if there is one
		if ( !empty( $url ) )
			return $url;
	}

?>