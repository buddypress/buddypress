<?php

/**************************************************************************
 
 Plugin Name: 
 BuddyPress Friends

 Description: 
 Adds the ability to keep a list of friends on the site.
 
 --------------------------------------------------------------------------
 Version: 0.1
 Type: Add-On
 **************************************************************************/


/**************************************************************************
 friends_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function friends_install()
{
	global $wpdb, $table_name;

	$sql = "CREATE TABLE ". $table_name ." (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  initiator_user_id mediumint(9) NOT NULL,
		  friend_user_id mediumint(9) NOT NULL,
		  is_confirmed bool DEFAULT 0,
		  date_created int(11) NOT NULL,
		  UNIQUE KEY id (id)
		 );";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
}


/**************************************************************************
 friends_add_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function friends_add_menu() 
{	
	global $wpdb, $table_name, $wpmuBaseTablePrefix, $bp_friends;
	$table_name = $wpmuBaseTablePrefix . "bp_friends";
	
	/* Instantiate bp_Friends class to do the real work. */
	$bp_friends = new BP_Friends;
	$bp_friends->bp_friends();
	
	add_menu_page("Friends", "Friends", 1, basename(__FILE__), "friends_list");
	add_submenu_page(basename(__FILE__), "My Friends", "My Friends", 1, basename(__FILE__), "friends_list");
	add_submenu_page(basename(__FILE__), "Friend Finder", "Friend Finder", 1, "friend_finder", "friends_find");	

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page('bp_core.php', "Friends", "Friends", 1, basename(__FILE__), "friends_settings");

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) friends_install();
}
add_action('admin_menu','friends_add_menu');


/**************************************************************************
 messages_write_new(), messages_inbox(), messages_sentbox(), 
 messages_drafts(), messages_add_js(), messages_add_css()
 
 These are all wrapper functions used in Wordpress hooks to pass through to 
 correct functions within the bp_messages object. Seems the only way
 Wordpress will handle this.
 **************************************************************************/

function friends_list() 	{ global $bp_friends; $bp_friends->list_friends(); }
function friends_find()		{ global $bp_friends; $bp_friends->find_friends(); }
function friends_add_css()	{ global $bp_friends; $bp_friends->add_css(); }
function friends_add_js()	{ global $bp_friends; $bp_friends->add_js(); }

/**************************************************************************
 bp_Friends [Class]
 
 Where all the magic happens.
 **************************************************************************/
 
class BP_Friends
{
	var $wpdb;
	var $tableName;
	var $basePrefix;
	var $userdata;
	var $imageBase;


	/**************************************************************************
 	 bp_friends()
 	  
 	 Contructor function.
 	 **************************************************************************/
	function bp_friends()
	{
		global $wpdb, $wpmuBaseTablePrefix, $userdata, $table_name;
		 
		$this->wpdb = &$wpdb;
		$this->userdata = &$userdata;
		$this->basePrefix = $wpmuBaseTablePrefix;
		$this->tableName = $table_name; // need a root prefix, not a wp_X_ prefix.
		$this->imageBase = get_option('siteurl') . '/wp-content/mu-plugins/bp_friends/images/';
		
		/* Setup CSS and JS */
		add_action("admin_print_scripts", "friends_add_css");
		add_action("admin_print_scripts", "friends_add_js");
	}

	
	/**************************************************************************
 	 list_friends()
 	  
	 Creates a nice list of all the current users friends. Gives the user
	 options to filter the list.
 	 **************************************************************************/	
 	 
	function list_friends() 
	{
		$friends = $this->get_friends($this->userdata->ID);
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
 	 find_friends()
 	  
	 Shows the find friend interface, allowing users to search for friends in
	 the system.
 	 **************************************************************************/	
		
	function find_friends($type = "error", $message = "")
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
				$results = $this->search($_POST['searchterm']);			
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


	/**************************************************************************
 	 get_friends()
 	  
	 Get a list of friends for the current user.
 	 **************************************************************************/	
		
	function get_friends($id)
	{
		if(bp_core_validate($id))
		{
			$sql = "SELECT initiator_user_id, friend_user_id
			 		FROM " . $this->tableName . "
					WHERE initiator_user_id = " . $id . "
					OR friend_user_id = " . $id . " 
					AND is_confirmed = 1";

			if(!$friends = $this->wpdb->get_results($sql))
			{
				return false;
			}
			
			for($i=0; $i<count($friends); $i++)
			{
				if($friends[$i]->initiator_user_id != $id)
				{
					$friend_id = $friends[$i]->initiator_user_id;
				}
				else
				{
					$friend_id = $friends[$i]->friend_user_id;
				}
				
				$sql = "SELECT meta_key, meta_value FROM " . $this->basePrefix . "usermeta 
						WHERE user_id = " . $friend_id;

				$friends_details[] = $this->wpdb->get_results($sql);
			
			}
			
			return $friends_details;
		}
		else {
			return false;
		}
	}

	
	/**************************************************************************
 	 search()
 	  
	 Find a user on the site based on someone entering search terms such as
	 a name, username or email address.
 	 **************************************************************************/	
 	 
	function search($terms) 
	{
		$terms = bp_core_clean($terms);
		
		$sql = "SELECT ID, display_name FROM " . $this->basePrefix . "users 
				WHERE user_login LIKE '%" . $terms . "%'
				OR user_nicename LIKE '%" . $terms . "%'
				OR user_email LIKE '%" . $terms . "%'
				ORDER BY user_nicename ASC";
		
		return $this->wpdb->get_results($sql);

	}

	/**************************************************************************
 	 callback()
 	  
	 Callback to specfic functions - this keeps correct tabs selected.
 	 **************************************************************************/	
	
	function callback($message, $type = 'error', $callback) 
	{

		switch($callback)
		{

		}
	}
	
	/**************************************************************************
 	 add_js()
 	  
	 Inserts the TinyMCE Js that's needed for the WYSIWYG message editor.
 	 **************************************************************************/	
	
	function add_js()
	{
		if(isset($_GET['page']))
		{
			?>
			<?php
		}
	}
	

	/**************************************************************************
 	 add_css()
 	  
	 Inserts the CSS needed to style the messages pages.
 	 **************************************************************************/	
	
	function add_css()
	{
		?>
		<style type="text/css">
			.unread td { 
				font-weight: bold; 
				background: #ffffec;
			}
			
			#send_message_form fieldset input {
				width: 98%;
				font-size: 1.7em;
				padding: 4px 3px;
			}
			
			
		</style>
		<?php
	}

} // End Class



?>