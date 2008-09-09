<?php
require_once( 'bp-core.php' );

define ( 'BP_GROUPS_IS_INSTALLED', 1 );
define ( 'BP_GROUPS_VERSION', '0.1.1' );

include_once( 'bp-groups/bp-groups-classes.php' );
include_once( 'bp-groups/bp-groups-ajax.php' );
include_once( 'bp-groups/bp-groups-cssjs.php' );
/*include_once( 'bp-messages/bp-groups-admin.php' );*/
include_once( 'bp-groups/bp-groups-templatetags.php' );


/**************************************************************************
 groups_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function groups_install( $version ) {
	global $wpdb, $bp;
	
	$sql[] = "CREATE TABLE ". $bp['groups']['table_name'] ." (
	  		id int(11) NOT NULL AUTO_INCREMENT,
			creator_id int(11) NOT NULL,
	  		name varchar(100) NOT NULL,
	  		slug varchar(100) NOT NULL,
	  		description longtext NOT NULL,
			news longtext NOT NULL,
			status varchar(10) NOT NULL DEFAULT 'open',
			is_invitation_only tinyint(1) NOT NULL DEFAULT '0',
			enable_wire tinyint(1) NOT NULL DEFAULT '1',
			enable_forum tinyint(1) NOT NULL DEFAULT '1',
			enable_photos tinyint(1) NOT NULL DEFAULT '1',
			photos_admin_only tinyint(1) NOT NULL DEFAULT '0',
			date_created datetime NOT NULL,
			avatar_thumb varchar(150) NOT NULL,
			avatar_full varchar(150) NOT NULL,
	    	PRIMARY KEY id (id)
	 	   );";
	
	$sql[] = "CREATE TABLE ". $bp['groups']['table_name_members'] ." (
	  		id int(11) NOT NULL AUTO_INCREMENT,
			group_id int(11) NOT NULL,
			user_id int(11) NOT NULL,
			inviter_id int(11) NOT NULL,
			is_admin tinyint(1) NOT NULL DEFAULT '0',
			user_title varchar(100) NOT NULL,
			date_modified datetime NOT NULL,
			is_confirmed tinyint(1) NOT NULL DEFAULT '0',
	    	PRIMARY KEY id (id)
	 	   );";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	add_site_option( 'bp-groups-version', $version );
}


/**************************************************************************
 groups_setup_globals()
 
 Set up and add all global variables for this component, and add them to 
 the $bp global variable array.
 **************************************************************************/

function groups_setup_globals() {
	global $bp, $wpdb;

	$bp['groups'] = array(
		'table_name' => $wpdb->base_prefix . 'bp_groups',
		'table_name_members' => $wpdb->base_prefix . 'bp_groups_members',
		'image_base' => get_option('siteurl') . '/wp-content/mu-plugins/bp-groups/images',
		'slug'		 => 'groups'
	);
	
	$bp['groups']['forbidden_names'] = array( 'my-groups', 'group-finder', 'create', 'invites', 'delete', 'add' );
}
add_action( 'wp', 'groups_setup_globals', 1 );	
add_action( '_admin_menu', 'groups_setup_globals', 1 );


/**************************************************************************
 groups_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function groups_add_admin_menu() {	
	global $wpdb, $bp, $userdata;
	
	if ( $wpdb->blogid == get_usermeta( $bp['current_userid'], 'home_base' ) ) {
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		//add_submenu_page( 'wpmu-admin.php', __("Friends"), __("Friends"), 1, basename(__FILE__), "friends_settings" );
	}

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var("show tables like '%" . $bp['groups']['table_name'] . "%'") == false ) || ( get_site_option('bp-groups-version') < BP_GROUPS_VERSION )  )
		groups_install(BP_GROUPS_VERSION);
		
}
add_action( 'admin_menu', 'groups_add_admin_menu' );

/**************************************************************************
 groups_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function groups_setup_nav() {
	global $bp;
	global $group_obj, $is_single_group;

	$bp['bp_nav'][4] = array(
		'id'	=> $bp['groups']['slug'],
		'name'  => __('Groups'), 
		'link'  => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/'
	);
	
	$bp['bp_users_nav'][3] = array(
		'id'	=> $bp['groups']['slug'],
		'name'  => __('Groups'), 
		'link'  => $bp['current_domain'] . $bp['groups']['slug'] . '/'
	);
	
	$bp['bp_options_nav'][$bp['groups']['slug']] = array(
		'my-groups'    => array( 
			'name'      => __('My Groups'),
			'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/my-groups' ),
		'group-finder' 	=> array(
			'name'      => __('Group Finder'),
			'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/group-finder' ),
		'create' => array( 
			'name'      => __('Create a Group'),
			'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/create' ),
		'invites' => array( 
			'name'      => __('Invites'),
			'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/invites' )
	);
	
	if ( $bp['current_component'] == $bp['groups']['slug'] ) {
		
		if ( bp_is_home() && !$is_single_group ) {
			
			$bp['bp_options_title'] = __('My Groups');
			
		} else if ( !bp_is_home() && !$is_single_group ) {
			
			$bp['bp_options_avatar'] = bp_core_get_avatar( $bp['current_userid'], 1 );
			$bp['bp_options_title'] = bp_user_fullname( $bp['current_userid'], false );
			
		} else if ( $is_single_group ) {
			
			// We are viewing a single group, so set up the
			// group navigation menu using the $group_obj global.
			
			$bp['bp_options_title'] = bp_create_excerpt( $group_obj->name, 1 );
			$bp['bp_options_avatar'] = '<img src="' . $group_obj->avatar_thumb . '" alt="Group Avatar Thumbnail" />';
			
			$bp['bp_options_nav'][$bp['groups']['slug']] = array(
				''    => array(
					'id'		=> 'group-home',
					'name'      => __('Home'),
					'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $group_obj->slug ),
				'forum' => array(
					'id'		=> 'group-forum',
					'name'      => __('Forum'),
					'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $group_obj->slug . '/forum' )
			);
			
			if ( bp_exists('wire') ) {
				$wire = array(
					'wire' => array(
						'id'		=> 'group-wire',
						'name'      => __('Wire'),
						'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $group_obj->slug . '/wire' )
				);
				$bp['bp_options_nav'][$bp['groups']['slug']] = array_merge( $bp['bp_options_nav'][$bp['groups']['slug']], $wire );
			}
			
			if ( bp_exists('gallery') ) {
				$photos = array(
					'photos' => array( 
						'id'		=> 'group-photos',
						'name'      => __('Photos'),
						'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $group_obj->slug . '/photos' )
				);
				$bp['bp_options_nav'][$bp['groups']['slug']] = array_merge( $bp['bp_options_nav'][$bp['groups']['slug']], $photos );
			}
			
			$options_nav = array(
				'members' => array(
					'id'		=> 'group-members',
					'name'      => __('Members'),
					'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $group_obj->slug . '/members' ),
				'send-invites' => array(
					'id'		=> 'group-invite',
					'name'      => __('Send Invites'),
					'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $group_obj->slug . '/send-invites' ),
			);
			$bp['bp_options_nav'][$bp['groups']['slug']] = array_merge( $bp['bp_options_nav'][$bp['groups']['slug']], $options_nav );
			
			if ( is_user_logged_in() && groups_is_user_member( $bp['loggedin_userid'], $group_obj->id ) ) {
				$leave_nav = array(
					'leave-group' => array(
						'id'		=> 'group-leave',
						'name'      => __('Leave Group'),
						'link'      => $bp['loggedin_domain'] . $bp['groups']['slug'] . '/' . $group_obj->slug . '/leave-group' )
				);
				$bp['bp_options_nav'][$bp['groups']['slug']] = array_merge( $bp['bp_options_nav'][$bp['groups']['slug']], $leave_nav );
			}
		}
	}
}
add_action( 'wp', 'groups_setup_nav', 4 );


/**************************************************************************
 groups_catch_action()
 
 Catch actions via pretty urls.
 **************************************************************************/

function groups_catch_action() {
	global $bp, $current_blog;
	global $is_single_group;
	global $create_group_step, $group_obj, $completed_to_step;
	
	if ( $bp['current_component'] == $bp['groups']['slug'] && $current_blog->blog_id > 1 ) {

		switch ( $bp['current_action'] ) {
			case 'my-groups':
				bp_catch_uri( 'groups/index' );
			break;
			
			case 'group-finder':
				bp_catch_uri( 'groups/group-finder' );
			break;
			
			case 'invites':
				if ( isset($bp['action_variables']) && in_array( 'accept', $bp['action_variables'] ) && is_numeric($bp['action_variables'][1]) ) {
					$member = new BP_Groups_Member( $bp['loggedin_userid'], $bp['action_variables'][1] );
					$member->accept_invite();

					if ( $member->save() ) {
						$bp['message'] = __('Group invite accepted');
						$bp['message_type'] = 'success';
					} else {
						$bp['message'] = __('Group invite could not be accepted');
						$bp['message_type'] = 'error';					
					}
					add_action( 'template_notices', 'bp_core_render_notice' );
				} else if ( isset($bp['action_variables']) && in_array( 'reject', $bp['action_variables'] ) && is_numeric($bp['action_variables'][1]) ) {
					if ( BP_Groups_Member::delete( $bp['loggedin_userid'], $bp['action_variables'][1] ) ) {
						$bp['message'] = __('Group invite rejected');
						$bp['message_type'] = 'success';
					} else {
						$bp['message'] = __('Group invite could not be rejected');
						$bp['message_type'] = 'error';				
					}
					add_action( 'template_notices', 'bp_core_render_notice' );
				}
				bp_catch_uri( 'groups/list-invites' );
			break; 

			case 'create':
				if ( !$create_group_step = $bp['action_variables'][1] ) {
					$create_group_step = '1';
					$completed_to_step = 0;
					setcookie('group_obj_id', NULL, time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
					setcookie('completed_to_step', NULL, time() - 3600, COOKIEPATH, COOKIE_DOMAIN);
					$no_instantiate = true;
					$reset_steps = true;
				}
				
				if ( isset($_COOKIE['completed_to_step']) && !$reset_steps ) {
					$completed_to_step = (int)$_COOKIE['completed_to_step'];
				}
				
				if ( isset( $_POST['save'] ) || isset( $_POST['skip'] ) ) {
					// If the user skipped the avatar step, move onto the next step and don't save anything.
					if ( isset( $_POST['skip'] ) && $create_group_step == "3" ) {
						$create_group_step++;
						$completed_to_step++;
						setcookie('completed_to_step', (string)$completed_to_step, time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
						setcookie('group_obj_id', (string)$_COOKIE['group_obj_id'], time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
						$group_obj = new BP_Groups_Group( $_COOKIE['group_obj_id'] );
					} else {
						if ( !$group_obj_id = &groups_manage_group( $create_group_step, $_COOKIE['group_obj_id'] ) ) {
							$bp['message'] = __('There was an error saving group details. Please try again.');
							$bp['message_type'] = 'error';
					
							add_action( 'template_notices', 'bp_core_render_notice' );
						} else {
							$create_group_step++;
							$completed_to_step++;
							setcookie('completed_to_step', (string)$completed_to_step, time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
							setcookie('group_obj_id', (string)$group_obj_id, time() + 30000000, COOKIEPATH, COOKIE_DOMAIN);
							$group_obj = new BP_Groups_Group( $group_obj_id );
						}
					}
				}

				if ( isset($_COOKIE['group_obj_id']) && !$group_obj && !$no_instantiate )
					$group_obj = new BP_Groups_Group( (int)$_COOKIE['group_obj_id'] );

				bp_catch_uri( 'groups/create' );			
			break;
			
			default:
				if ( $bp['current_action'] != '' ) {
					if ( $group_id = BP_Groups_Group::group_exists($bp['current_action']) ) {
						
						// This is a single group page.
						$is_single_group = true;
						$group_obj = new BP_Groups_Group( $group_id );
						
						switch ( $bp['action_variables'][0] ) {
							case 'forum':
								// Not implemented yet.
								bp_catch_uri( 'groups/forum' );
							break;
							case 'wire':
								// Not implemented yet.
								bp_catch_uri( 'groups/group-home' );
							break;
							case 'photos':
								// Not implemented yet.
								bp_catch_uri( 'groups/group-home' );
							break;
							case 'members':
								// List group members
								bp_catch_uri( 'groups/list-members' );
							break;
							case 'send-invites':
								if ( isset($bp['action_variables']) && $bp['action_variables'][1] == 'send' ) {
									// Send the invites.
									groups_send_invites($group_obj);
									
									$bp['message'] = __('Group invites sent.');
									$bp['message_type'] = 'success';
									
									add_action( 'template_notices', 'bp_core_render_notice' );
									bp_catch_uri( 'groups/group-home' );
								} else {
									// Show send invite page
									bp_catch_uri( 'groups/send-invite' );	
								}
							break;
							case 'join':
								// user wants to join a group
								
								if ( !BP_Groups_Member::check_is_member( $bp['loggedin_userid'], $group_obj->id ) ) {
									if ( !groups_join_group($group_obj->id) ) {
										$bp['message'] = __('There was an error joining the group. Please try again.');
										$bp['message_type'] = 'error';
									} else {
										$bp['message'] = __('You joined the group! <a');
										$bp['message_type'] = 'success';						
									}

									add_action( 'template_notices', 'bp_core_render_notice' );
								}
								
								bp_catch_uri( 'groups/group-home' );
							break;
							case 'leave-group':
								if ( isset($bp['action_variables']) && $bp['action_variables'][1] == 'yes' ) {
									// remove the user from the group.
									if ( !groups_leave_group( $group_obj->id ) ) {
										$bp['message'] = __('There was an error leaving the group. Please try again.');
										$bp['message_type'] = 'error';										
									} else {
										$bp['message'] = __('You left the group successfully.');
										$bp['message_type'] = 'success';									
									}
									add_action( 'template_notices', 'bp_core_render_notice' );
									
									$is_single_group = false;
									$bp['current_action'] = 'group-finder';
									bp_catch_uri( 'groups/group-finder' );
									
								} else if ( isset($bp['action_variables']) && $bp['action_variables'][1] == 'no' ) {
									bp_catch_uri( 'groups/group-home' );
								} else {
									// Show leave group page
									bp_catch_uri( 'groups/leave-group-confirm' );
								}
							break;
							default:
								bp_catch_uri( 'groups/group-home' );
							break;
						}
					} else {
						$bp['current_action'] = 'my-groups';
						bp_catch_uri( 'groups/index' );
					}
				} else {
					$bp['current_action'] = 'my-groups';
					bp_catch_uri( 'groups/index' );	
				}			
			break;
		}
	}
}
add_action( 'wp', 'groups_catch_action', 3 );


/**************************************************************************
 groups_admin_setup()
 
 Setup CSS, JS and other things needed for the xprofile component.
**************************************************************************/

function groups_admin_setup() {
}
add_action( 'admin_menu', 'groups_admin_setup' );


function groups_get_user_groups( $pag_page, $pag_num ) {
	global $bp;
	
	$group_ids = BP_Groups_Member::get_group_ids( $bp['current_userid'], $pag_page, $pag_num );
	$group_count = $group_ids['count'];
	
	for ( $i = 0; $i < count($group_ids['ids']); $i++ ) {
		$groups[] = new BP_Groups_Group( $group_ids['ids'][$i] );
	}
	
	return array( 'groups' => $groups, 'count' => $group_count );
}


/**************************************************************************
 groups_avatar_upload()
 
 Handle uploading of a group avatar
**************************************************************************/

function groups_avatar_upload( $file ) {
	// validate the group avatar upload if there is one.
	$avatar_error = false;

	if ( bp_core_check_avatar_upload($file) ) {
		if ( !bp_core_check_avatar_upload($file) ) {
			$avatar_error = true;
			$avatar_error_msg = __('Your group avatar upload failed, please try again.');
		}

		if ( !bp_core_check_avatar_size($file) ) {
			$avatar_error = true;
			$avatar_size = size_format(1024 * CORE_MAX_FILE_SIZE);
			$avatar_error_msg = sprintf( __('The file you uploaded is too big. Please upload a file under %d'), $avatar_size);
		}

		if ( !bp_core_check_avatar_type($file) ) {
			$avatar_error = true;
			$avatar_error_msg = __('Please upload only JPG, GIF or PNG photos.');		
		}

		// "Handle" upload into temporary location
		if ( !$original = bp_core_handle_avatar_upload($file) ) {
			$avatar_error = true;
			$avatar_error_msg = __('Upload Failed! Your photo dimensions are likely too big.');						
		}

		if ( !bp_core_check_avatar_dimensions($original) ) {
			$avatar_error = true;
			$avatar_error_msg = sprintf( __('The image you upload must have dimensions of %d x %d pixels or larger.'), CORE_CROPPING_CANVAS_MAX, CORE_CROPPING_CANVAS_MAX );
		}
		
		if ( !$canvas = bp_core_resize_avatar($original) ) {
			$avatar_error = true;
			$avatar_error_msg = __('Could not create thumbnail, try another photo.');
		}
		
		if ( $avatar_error ) { ?>
			<div id="message" class="error">
				<p><?php echo $avatar_error_msg ?></p>
			</div>
			<?php
			bp_core_render_avatar_upload_form( '', true );
		} else {
			bp_core_render_avatar_cropper( $original, $canvas, null, null, false, $bp['loggedin_domain'] );
		}
	}
}


/**************************************************************************
 groups_save_avatar()
 
 Save the avatar location urls into the DB for the group.
**************************************************************************/

function groups_get_avatar_hrefs( $avatars ) {
	global $bp;
	
	$src = $bp['loggedin_domain'];

	$thumb_href = str_replace( ABSPATH, $src, $avatars['v1_out'] );
	$full_href = str_replace( ABSPATH, $src, $avatars['v2_out'] );
	
	return array( 'thumb_href' => $thumb_href, 'full_href' => $full_href );
}


/**************************************************************************
 groups_manage_group()
 
 Manage the creation of a group via the step by step wizard.
**************************************************************************/

function groups_manage_group( $step, $group_id ) {
	global $bp;
	
	if ( is_numeric( $step ) && ( $step == '1' || $step == '2' || $step == '3' || $step == '4' ) ) {
		// If this is the group avatar step, load in the JS.
		if ( $create_group_step == '3' )
			add_action( 'wp_head', 'bp_core_add_cropper_js' );
		
		$group = new BP_Groups_Group( $group_id );		
		
		switch ( $step ) {
			case '1':
				if ( isset($_POST['group-name']) && isset($_POST['group-desc']) ) {
					$group->creator_id = $bp['loggedin_userid'];
					$group->name = stripslashes($_POST['group-name']);
					$group->description = stripslashes($_POST['group-desc']);
					$group->news = stripslashes($_POST['group-news']);
					
					$slug = groups_check_slug( sanitize_title($_POST['group-name']) );

					$group->slug = $slug;
					$group->status = 'public';
					$group->is_invitation_only = 0;
					$group->enable_wire = 1;
					$group->enable_forum = 1;
					$group->enable_photos = 1;
					$group->photos_admin_only = 0;
					$group->date_created = time();
					
					if ( !$group->save() )
						return false;
					
					// Save the creator as the group administrator
					$admin = new BP_Groups_Member( $bp['loggedin_userid'], $group->id );
					$admin->is_admin = 1;
					$admin->user_title = __('Group Admin');
					$admin->date_modified = time();
					$admin->inviter_id = 0;
					$admin->is_confirmed = 1;
									
					if ( !$admin->save() )
						return false;
						
					return $group->id;
				}
			break;
			
			case '2':
				$group->status = 'public';
				$group->is_invitation_only = 0;
				$group->enable_wire = 1;
				$group->enable_forum = 1;
				$group->enable_photos = 1;
				$group->photos_admin_only = 0;
				$group->date_created = time();
				
				if ( !isset($_POST['group-show-wire']) )
					$group->enable_wire = 0;
				
				if ( !isset($_POST['group-show-forum']) )
					$group->enable_forum = 0;
				
				if ( !isset($_POST['group-show-photos']) )
					$group->enable_photos = 0;				
				
				if ( $_POST['group-photos-status'] != 'all' )
					$group->photos_admin_only = 1;
				
				if ( $_POST['group-status'] == 'private' ) {
					$group->status = 'private';
				} else if ( $_POST['group-status'] == 'hidden' ) {
					$group->status = 'hidden';
				}
				
				if ( !$group->save() )
					return false;
					
				return $group->id;
			break;
			
			case '3':
				// Image already cropped and uploaded, lets store a reference in the DB.
				if ( !wp_verify_nonce($_POST['nonce'], 'slick_avatars') || !$result = bp_core_avatar_cropstore( $_POST['orig'], $_POST['canvas'], $_POST['v1_x1'], $_POST['v1_y1'], $_POST['v1_w'], $_POST['v1_h'], $_POST['v2_x1'], $_POST['v2_y1'], $_POST['v2_w'], $_POST['v2_h'], false, 'groupavatar', $group_id ) )
					return false;

				// Success on group avatar cropping, now save the results.
				$avatar_hrefs = groups_get_avatar_hrefs($result);
				
				$group->avatar_thumb = $avatar_hrefs['thumb_href'];
				$group->avatar_full = $avatar_hrefs['full_href'];
				
				if ( !$group->save() )
					return false;
				
				return $group->id;
			break;
			
			case '4':
				groups_send_invites($group);

				header( "Location: " . $bp['loggedin_domain'] . $bp['groups']['slug'] . "/" . $group->slug );
				
			break;
		}
	}
	
	return false;
}

function groups_check_slug( $slug ) {
	global $bp;
	
	if ( in_array( $slug, $bp['groups']['forbidden_names'] ) ) {
		$slug = $slug . '-' . rand();
	}
	
	do {
		$slug = $slug . '-' . rand();
	}
	while ( BP_Groups_Group::check_slug( $slug ) );
	
	return $slug;
}

/**************************************************************************
 groups_is_user_admin()
 
 Check if a user is an administrator of a group.
**************************************************************************/

function groups_is_user_admin( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_admin( $user_id, $group_id );
}

function groups_is_user_member( $user_id, $group_id ) {
	return BP_Groups_Member::check_is_member( $user_id, $group_id );
}

function groups_invite_user( $user_id, $group_id ) {
	global $bp;
	
	$invite = new BP_Groups_Member;
	$invite->group_id = $group_id;
	$invite->user_id = $user_id;
	$invite->date_modified = time();
	$invite->inviter_id = $bp['loggedin_userid'];
	$invite->is_confirmed = 0;
	
	if ( !$invite->save() )
		return false;
	
	return true;
}

function groups_uninvite_user( $user_id, $group_id ) {
	global $bp;

	if ( !BP_Groups_Member::delete( $user_id, $group_id ) )
		return false;
	
	return true;
}


function groups_get_invites_for_group( $group_id ) {
	return BP_Groups_Group::get_invites( $group_id );
}


function groups_get_invites_for_user( $user_id = false ) {
	global $bp;
	
	if ( !$user_id )
		$user_id = $bp['loggedin_userid'];
	
	return BP_Groups_Member::get_invites($user_id);
}

function groups_leave_group( $group_id ) {
	global $bp;
	
	// This is exactly the same as deleting and invite, just is_confirmed = 1 NOT 0.
	if ( !groups_uninvite_user( $bp['loggedin_userid'], $group_id ) )
		return false;
	
	return true;
}

function groups_send_invites( $group_obj ) {
	global $bp;
	
	// Send friend invites.
	$invited_users = groups_get_invites_for_group( $group_obj->id ); 
	
	for ( $i = 0; $i < count( $invited_users); $i++ ) {
		$user_id = $invited_users[$i];

		// Send the email

		$invited_user = new BP_Core_User( $user_id );
		$inviter_name = bp_core_get_userlink( $bp['loggedin_userid'], true, false, true );
		
		$message = "You have been invited to join the group '" . $group_obj->name . "' by " . $inviter_name . '.';
		$message .= "\n\n";
		$message .= "View the group: " . $invited_user->user_url . $bp['groups']['slug'] . "/" . $group_obj->slug . "\n";
		$message .= "Accept the invite: " . $invited_user->user_url . $bp['groups']['slug'] . "/invites/accept/" . $group_obj->id . "\n";
		$message .= "Reject the invite: " . $invited_user->user_url . $bp['groups']['slug'] . "/invites/reject/" . $group_obj->id . "\n";

		wp_mail( $invited_user->email, __("New Group Invitation:") . $group_obj->name, $message, "From: noreply@" . $_SERVER[ 'HTTP_HOST' ]  );
	}
}

function groups_join_group( $group_id ) {
	global $bp;
	
	$new_member = new BP_Groups_Member;
	$new_member->group_id = $group_id;
	$new_member->user_id = $bp['loggedin_userid'];
	$new_member->inviter_id = 0;
	$new_member->is_admin = 0;
	$new_member->user_title = '';
	$new_member->date_modified = time();
	$new_member->is_confirmed = 1;
	
	if ( !$new_member->save() )
		return false;
	
	return true;
}
?>