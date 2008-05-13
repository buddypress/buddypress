<?php

$bp_friends_table_name = $wpdb->base_prefix . 'bp_friends';
$bp_friends_image_base = get_option('siteurl') . '/wp-content/mu-plugins/bp-friends/images';
define('BP_FRIENDS_VERSION', '0.2');

require_once( 'bp-friends/bp-friends-classes.php' );
require_once( 'bp-friends/bp-friends-templatetags.php' );
require_once( 'bp-friends/bp-friends-cssjs.php' );
	
/**************************************************************************
 friends_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function friends_install( $version ) {
	global $wpdb, $bp_friends_table_name;

	$sql = array();			
			
	$sql[] = "CREATE TABLE `". $bp_friends_table_name ."` (
		  		`id` mediumint(9) NOT NULL AUTO_INCREMENT,
		  		`initiator_user_id` mediumint(9) NOT NULL,
		  		`friend_user_id` mediumint(9) NOT NULL,
		  		`is_confirmed` bool DEFAULT 0,
		  		`date_created` int(11) NOT NULL,
		    UNIQUE KEY id (`id`)
		 );";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
	
	add_site_option('bp-friends-version', $version);
}
		
		
/**************************************************************************
 friends_add_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function friends_add_menu() {	
	global $wpdb, $bp_friends_table_name, $bp_friends, $userdata;
	
	if ( $wpdb->blogid == $userdata->primary_blog ) {
		add_menu_page( __("Friends"), __("Friends"), 1, basename(__FILE__), "friends_list" );
		add_submenu_page( basename(__FILE__), __("My Friends"), __("My Friends"), 1, basename(__FILE__), "friends_list" );
		add_submenu_page( basename(__FILE__), __("Friend Finder"), __("Friend Finder"), 1, "friend_finder", "friends_find" );	
		
		/* Add the administration tab under the "Site Admin" tab for site administrators */
		//add_submenu_page( 'wpmu-admin.php', __("Friends"), __("Friends"), 1, basename(__FILE__), "friends_settings" );
	}

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var("show tables like '%" . $bp_friends_table_name . "%'") == false ) || ( get_site_option('bp-friends-version') < BP_FRIENDS_VERSION )  )
		friends_install(BP_FRIENDS_VERSION);
		
}
add_action( 'admin_menu','friends_add_menu' );


/**************************************************************************
 friends_setup()
 
 Setup CSS, JS and other things needed for the xprofile component.
**************************************************************************/

function friends_setup() {
	add_action( 'admin_print_scripts', 'friends_add_css' );
	add_action( 'admin_print_scripts', 'friends_add_js' );
}
add_action( 'admin_menu', 'friends_setup' );


/**************************************************************************
 friends_profile_template()
 
 Set up access to authordata and then set up template tags for use in
 templates.
 **************************************************************************/

function friends_template() {	
	global $is_author, $userdata, $authordata, $friends_template;
	
	$friends_template = new BP_Friends_Template;
	
}
add_action( 'wp_head', 'friends_template' );


/**************************************************************************
 friends_list()
  
 Creates a nice list of all the current users friends. Gives the user
 options to filter the list.
 **************************************************************************/	

function friends_list()
{
		$bp_friends = new BP_Friends();
		$friends = $bp_friends->get_friends();
?>	
	<div class="wrap">
		
		<h2><?php _e("My Friends") ?></h2>
		
		<?php if(!$friends) { ?>
			<div id="message" class="error fade">
				<p><?php _e("There was an error getting your list of friends, please try again.") ?></p>
			</div>
		<?php } else { ?>					
			
			<?php if(count($friends) < 1) { ?>
				<div id="message" class="updated fade">
					<p><?php _e("Looks like you don't have any friends. Why not <a href=\"admin.php?page=friend_finder\" title=\"Friend Finder\">find some</a>?"); ?></p>
				</div>
			<?php } else { ?>
				<ul id="friends-list">
					<?php for($i=0; $i<count($friends); $i++) { ?>
					<li><?php echo '<a href="http://' . $friends[$i][3]->meta_value . '">' . $friends[$i][0]->meta_value . '</a>'; ?></li>
					
					<?php } ?>
				</ul>
			<?php } ?>
			
		<?php } ?>
		
	</div>
<?php
}


/**************************************************************************
 friends_find()
  
 Shows the find friend interface, allowing users to search for friends in
 the system.
 **************************************************************************/	
	
function friends_find($type = "error", $message = "")
{
	if(isset($_POST['searchterm']) && isset($_POST['search']))
	{
		if($_POST['searchterm'] == "")
		{
			$message = __("Please make sure you enter something to search for.");
		}
		else if(strlen($_POST['searchterm']) < 3)
		{
			$message = __("Your search term must be longer than 3 letters otherwise you'll be here for years.");
		}
		else {
			// The search term is okay, let's get it movin'
			$bp_friends = new BP_Friends();
			$results = $bp_friends->search($_POST['searchterm']);			
		}
	}
	
?>

	<div class="wrap">
		<h2><?php _e('Friend Finder'); ?></h2>
	
	<?php if($message != '') { ?>
		<?php if($type == 'error') { $type = "error"; } else { $type = "updated"; } ?>
		<div id="message" class="<?php echo $type; ?> fade">
			<p><?php echo $message; ?></p>
		</div>
	<?php } ?>
		
		<form action="admin.php?page=friend_finder" method="post">
			
			<fieldset id="searchtermdiv">
				<legend><?php _e("Friends name, username or email address:") ?></legend>
				<div>
					<input type="text" name="searchterm" id="searchterm" value="<?php echo $_POST['searchterm'] ?>" />
				</div>
			</fieldset>
			
			<p>
				<input type="submit" value="<?php _e("Search") ?> &raquo;" name="search" id="search" style="font-weight: bold" />
			</p>
			
		</form>
		
		<?php if(isset($results)) { ?>
			<?php if(!$results) { ?>
				<p>Nothing Found!</p>
			<?php } else { ?>
				<ul id="friend_results">
					<?php for($i=0; $i<count($results); $i++) { ?>
						<li><?php echo $results[$i]->display_name; ?></li>
					<?php } ?>
				</ul>
			<?php } ?>
		<?php } ?>
	</div>
	
<?php
}

?>