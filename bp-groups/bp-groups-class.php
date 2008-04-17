<?php
/**************************************************************************
 PLUGIN CLASS
 --------------------------------------------------------------------------
   - BP_Groups -- All group functions
 -------------------------------------------------------------------------- 
 **************************************************************************/
 
class BP_Groups
{	

	// set parameters
	var $message;
	var $options;
	var $enabled;
	var $slug;
	var $id;
	var $current_group;
	var $featuretitle;
	var $itemtitle;
	var $groups_table;
	var $group_members_table;

	// the BP_Groups object
	function BP_Groups()
	{
		
		// set the options as an array of arrays, each option has
		// array('Option description', 'Option name', 'Default value', 'Options type [text|boolean])
		$this->options =	array (
						array("Application enabled", "bp_groups_enabled", "true", "boolean"),
						array("Groups application title", "bp_groups_title", "Groups", "text"),
						array("Group title", "bp_group_title", "group", "text"),
						array("Groups application description", "bp_groups_description", "Welcome to the groups.", "textarea"),
						array("No group members", "bp_groups_no_members", "There are no members of this group. Why not be the first?", "text"),
						array("Already group member", "bp_groups_already_member", "You can't join this group as you are already a member.", "text"),
						array("Member not invited", "bp_groups_not_invited", "You can't join this group as you have not been invited.", "text"),
						array("Joined group", "bp_groups_joined", "Congratulations, you've just joined this group.", "text"),
						array("Join group message", "bp_groups_join_message", "Click here to join this group", "text"),
						array("Action denied error", "bp_groups_action_denied", "You cannot perform this action", "text"),
						array("Error saving image", "bp_error_saving_image", "There has been a problem saving your group image. Please see the errors below for more information.", "text"),
						array("Error duplicate group name", "bp_error_duplicate_group_name", "Sorry, you will have to call your group something different as that name has already been used. Click back and try again. ", "text")
						);
		
		$this->featuretitle = get_site_option("bp_groups_title");
		$this->itemtitle = get_site_option("bp_group_title");
		
		//if (get_site_option("bp_groups_enabled") == "")
		//{
		
			$this->groups_table = $wpdb->base_prefix."bp_groups";
			$this->group_members_table = $wpdb->base_prefix."bp_group_members";
			
			// check the options exist
			$this->Check_Options();
			
			// check the tables exist
			$this->Tables_Action();

			// add the menu options
			$this->Menu_Action();

		//}
	}
	
	function Menu_Action()
	{
		// add the menu options
		add_action('admin_menu', array(&$this, 'Build_Menu'));
	}
	
	function Tables_Action()
	{
		// check the tables exist
		add_action('admin_head', array(&$this, 'Check_Tables'));
	}
	
	function Build_Menu()
	{
		global $userdata, $wpdb;
		
		if($wpdb->blogid == $userdata->primary_blog)
		{	
			add_menu_page("Groups", "Groups", 1, basename(__FILE__), array(&$this, 'Groups'));
			add_submenu_page(basename(__FILE__), "Your Groups", "Your Groups", 1, basename(__FILE__), array(&$this, 'Groups'));
			add_submenu_page(basename(__FILE__), "Group Invites", "Group Invites", 1, "groups.invites", array(&$this, 'Invites'));
			add_submenu_page(basename(__FILE__), "Join Group", "Join Group", 1, "groups.join", array(&$this, 'Join'));
			add_submenu_page(basename(__FILE__), "Create Group", "Create Group", 1, "groups.create", array(&$this, 'Create'));
		
			if ($this->Has_Group_Admin_Rights())
			{
				add_submenu_page(basename(__FILE__), "Administer Groups", "Administer Groups", 1, "groups.edit", array(&$this, 'Edit'));
			}
		}
	}
	
	// feature a group
	function Feature_Group($groupid)
	{
	
		$featured_group = (int)$groupid;
		update_site_option("bp_featured_group", $featured_group);
		$this->message = "<p class=\"success\">The featured group has been successfully saved.</p>";
	
	}
	
	// check if the current group exists, and get the details for it
	function Get_Current_Group()
	{
		// get the globals
		global $wpdb;
		global $myjournal_members;
		global $current_user;

		// get the group details
		if ($this->slug)
		{
			$sql = "select n.id, n.name, n.slug, n.description, n.private, n.open, n.type,
					count(m.id) as members
					from ".$this->groups_table." n
					inner join ".$this->group_members_table." m on m.group_id = n.id and m.status_id = 1
					inner join ".$wpdb->base_prefix."users b on b.id = m.user_id
					where n.slug = '".$wpdb->escape($this->slug)."'
					group by n.id;";
		} 
		if ($this->id)
		{
			$sql = "select n.id, n.name, n.slug, n.description, n.private, n.open, n.type,
					count(m.id) as members
					from ".$this->groups_table." n
					inner join ".$this->group_members_table." m on m.group_id = n.id and m.status_id = 1
					inner join ".$wpdb->base_prefix."users b on b.id = m.user_id
					where n.id = ".$wpdb->escape($this->id)."
					group by n.id;";
		}
		$details = $wpdb->get_row($sql);
		
		if ($details->private == "1")
		{
			$sql = "select id from ".$this->group_members_table." 
					where group_id = ".$details->id."
					and user_id = ".$current_user->ID."
					and status_id in (1,2);";

			$valid_member = $wpdb->get_var($sql);
		} else {
			$valid_member = 1;
		}
		
		// if the group was not found, or it is private and the user isn't a member
		if (!$details || !$valid_member || get_site_option("bp_groups_enabled") != "true")
		{
			$this->current_group->name = "Not found";
			$this->current_group->description = "Sorry, the group you are looking for cannot be found.";
		} else {
			$this->current_group = $details;
		};
	}
	
	// get basic details of a group
	function Group_Details($groupid)
	{
		// get the globals
		global $wpdb;
		$sql = "select name, slug from ".$this->groups_table." where id = ".$wpdb->escape($groupid).";";
		return $wpdb->get_row($sql);
	}
	
	// search public groups
	function Search_Groups($q, $start=0, $num=10)
	{
		// get the globals
		global $wpdb;
		
		$sql = "select SQL_CALC_FOUND_ROWS id, name, description, slug
				from ".$this->groups_table."
				where (name like '%".$wpdb->escape($q)."%' or description like '%".$wpdb->escape($q)."%')
				limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";

		$groups = $wpdb->get_results($sql);
		if (is_array($groups) && count($groups) > 0)
		{
			$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
			for ($i = 0; $i < count($groups); $i++)
			{
				$image = $this->Get_Group_Image($groups[$i]->id, "m");
				$groups[$i]->image = $image;
				$groups[$i]->rows = $rows;
			}
		}
		return $groups;
	}
	
	// get the latest groups
	function Latest_Groups($limit=6, $start=0)
	{
		// get the globals
		global $wpdb;
	
		$groups = $wpdb->get_results("select id, slug, name, description, open
							from ".$this->groups_table."
							where status_id = 1
							and private = 0
							order by timestamp desc
							limit ".$start.", ".$limit.";");
							
		return $groups;
	}
	
	// get the latest groups
	function Popular_Groups($limit=6, $start=0)
	{
		// get the globals
		global $wpdb;
	
		$groups = $wpdb->get_results("select n.id, n.slug, n.name, n.description, n.open, count(m.id) as members
							from ".$this->groups_table." n
							left outer join ".$this->group_members_table." m on m.group_id = n.id and m.status_id = 1
							where n.status_id = 1
							and n.private = 0
							group by n.id
							order by members desc
							limit ".$start.", ".$limit.";");
							
		return $groups;
	}
	
	// get the groups the current user is in
	function User_Groups($start = 0, $num = 10, $all = false)
	{
		// get the globals
		global $wpdb;
		global $current_user;
		
		if ($all)
		{
		
			$sql = "select SQL_CALC_FOUND_ROWS n.id, n.name, n.slug, n.description, n.private,
				n.open, n.type, m.group_admin,
				(select count(id) from ".$this->group_members_table." where group_id = n.id and status_id = 1) as members
				from ".$this->groups_table." n
				inner join ".$this->group_members_table." m on m.group_id = n.id
				where m.user_id = ".$current_user->ID."
				and m.status_id = 1
				and (n.private = 0
				or m.user_id = ".$current_user->ID.")
				group by n.id
				limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
		
		} else {
		
			$sql = "select SQL_CALC_FOUND_ROWS n.id, n.name, n.slug, n.description, n.private,
				n.open, n.type, m.group_admin, Group_Members
				(select count(id) from ".$this->group_members_table." where group_id = n.id and status_id = 1) as members
				from ".$this->groups_table." n
				inner join ".$this->group_members_table." m on m.group_id = n.id
				where m.user_id = ".$current_user->ID."
				and m.status_id = 1
				group by n.id
				limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
				
		}
		//print $sql."<br />";
		$groups = $wpdb->get_results($sql);

		$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
		$groups[0]->rows = $rows;
		return $groups;
	}
	
	// get the number of group invitations
	function User_Invites_Num()
	{
		// get the globals
		global $wpdb;
		global $current_user;
	
		$sql = "select count(n.id)
				from ".$this->groups_table." n
				inner join ".$this->group_members_table." m on m.group_id = n.id
				inner join ".$wpdb->base_prefix."users b on b.id = m.user_id
				where m.user_id = ".$current_user->user_id."
				and m.status_id = 2
				limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
		$invites = $wpdb->get_var($sql);
		if ($invites > 0)
		{
			return " (".$invites.")";
		}
	}
	
	// get the groups the current user has been invited to join
	function User_Invites($start = 0, $num = 10)
	{
		// get the globals
		global $wpdb;
		global $current_user;
		
		$sql = "select SQL_CALC_FOUND_ROWS n.id, n.name, n.slug, n.description, n.private, n.open, n.type, m.group_admin, m.inviter_id
				from ".$this->groups_table." n
				inner join ".$this->group_members_table." m on m.group_id = n.id
				inner join ".$wpdb->base_prefix."users b on b.id = m.user_id
				where m.user_id = ".$current_user->user_id."
				and m.status_id = 2
				limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
		$invites = $wpdb->get_results($sql);
		$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
		if (is_array($invites) && count($invites) > 0)
		{
			for ($i = 0; $i < count($invites); $i++)
			{
				$user_details = get_user_details($invites[$i]->inviter_id);
				$inviter = get_userdata($invites[$i]->inviter_id);
				$invites[$i]->inviter = $inviter->user_nicename;
				$invites[$i]->inviter_url = "/members/" . $inviter->user_login . "/";
				$invites[$i]->siteurl = $user_details->siteurl;
				$invites[$i]->username = $user_details->username;
				$invites[$i]->rows = $rows;
			}
		}
		return $invites;
	}
	
	// get the invites for the current group
	function Group_Invites($start = 0, $num = 10)
	{
		// get the globals
		global $wpdb;
		
		$sql = "select SQL_CALC_FOUND_ROWS b.user_id, i.user_id as inviter_id, m.group_admin
			from ".$this->group_members_table." m
			inner join  ".$wpdb->base_prefix."users b on b.id = m.user_id
			inner join  ".$wpdb->base_prefix."users i on i.id = m.inviter_id
			where m.group_id = ".$this->current_group->id." 
			and m.status_id = 2
			order by m.timestamp desc
			limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
		$invites = $wpdb->get_results($sql);
		if (is_array($invites) && count($invites) > 0)
		{
			$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
			for ($i = 0; $i < count($invites); $i++)
			{
			
				$user = get_user_details($invites[$i]->user_id);
				$owner = get_userdata($invites[$i]->user_id);
				
				$inviter_user = get_user_details($invites[$i]->inviter_id);
				$inviter = get_userdata($invites[$i]->inviter_id);
				
				$invites[$i]->inviter = $inviter->user_nicename;
				$invites[$i]->inviter_siteurl = $inviter->user_url;
				$invites[$i]->inviter_username = $inviter_user->username;
				$invites[$i]->inviter_userurl = $inviter_user->path;
				
				$invites[$i]->name = $owner->user_nicename;
				$invites[$i]->siteurl = $owner->user_url;
				$invites[$i]->username = $user->username;
				$invites[$i]->userurl = $user->path;
				
				$invites[$i]->rows = $rows;
			
			}
			return $invites;
		} else {
			return false;
		}
	}
	
	// get the members in the current group
	function Group_Members($start = 0, $num = 10)
	{
		// get the globals
		global $wpdb;
		
		$sql = "select SQL_CALC_FOUND_ROWS b.id as user_id, b.user_login, m.group_admin
			from ".$this->group_members_table." m
			inner join  ".$wpdb->base_prefix."users b on b.id = m.user_id
			where m.group_id = ".$this->current_group->id." 
			and m.status_id = 1
			order by m.timestamp desc
			limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
		$users = $wpdb->get_results($sql);
		if (is_array($users) && count($users) > 0)
		{
			$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
			$users_out = array();
			foreach ($users as $user)
			{
		
				$user_details = get_user_details($user->user_login);
				$user_details->admin = $user->group_admin;
				$user_details->rows = $rows;

				$users_out[] = $user_details;
			
			}
			return $users_out;
		} else {
			return false;
		}
	}
	
	// get the deleted members in the current group
	function Deleted_Members($start = 0, $num = 10)
	{
		// get the globals
		global $wpdb;
		
		$sql = "select SQL_CALC_FOUND_ROWS b.id as user_id, b.user_login, m.group_admin
			from ".$this->group_members_table." m
			inner join  ".$wpdb->base_prefix."users b on b.id = m.user_id
			where m.group_id = ".$this->current_group->id." 
			and m.status_id = 0
			order by m.timestamp desc
			limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";
		$users = $wpdb->get_results($sql);
		if (is_array($users) && count($users) > 0)
		{
			$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
			$users_out = array();
			foreach ($users as $user)
			{
			
				$user_details = get_user_details($user->user_login);
				$user_details->admin = $user->group_admin;
				$user_details->rows = $rows;
				
				$users_out[] = $user_details;
			
			}
			return $users_out;
		} else {
			return false;
		}
	}
	
	// is the current user a member of the current group
	function Is_Group_Member($userid = 0)
	{
		global $wpdb;
		
		if ($userid == 0)
		{
			global $current_user;
			$userid = $current_user->ID;
		}
		
		$sql =	"select count(id) 
				from  ".$this->group_members_table." 
				where group_id = ".$this->current_group->id." 
				and status_id = 1
				and user_id = ".$userid.";";

		if ($wpdb->get_var($sql) == 0)
		{
			return false;
		} else {
			return true;
		}
	}
	
	// is the current user an administrator of the current group
	function Is_Group_Admin($userid = 0)
	{
		global $wpdb;
		
		if ($userid == 0)
		{
			global $current_user;
			$userid = $current_user->ID;
		}

		$sql = 	"select count(id) 
				from  ".$this->group_members_table." 
				where group_id = ".$this->current_group->id."						
				and status_id = 1
				and user_id = ".$userid."
				and group_admin = 1;";

		if ($wpdb->get_var($sql) == 0)
		{
			return false;
		} else {
			return true;
		}
	}
	
	// is the current user an administrator of the current group
	function User_Is_Group_Admin()
	{
		global $wpdb;
		global $current_user;

		$sql = 	"select count(id) 
				from  ".$this->group_members_table." 
				where group_id = ".$this->current_group->id."						
				and status_id = 1
				and user_id = ".$current_user->user_id."
				and group_admin = 1;";

		if ($wpdb->get_var($sql) == 0)
		{
			return false;
		} else {
			return true;
		}
	}
	
	// is the current user an administrator of any groups
	function Has_Group_Admin_Rights()
	{
		global $wpdb;
		global $current_user;
		
		$sql = 	"select count(id) 
				from  ".$this->group_members_table." 
				where status_id = 1
				and user_id = ".$current_user->ID."
				and group_admin = 1;";
				
		if ($wpdb->get_var($sql) == 0)
		{
			return false;
		} else {
			return true;
		}
	}
	
	// get the groups the current user is an administrator of
	function Admin_Groups($start = 0, $num = 10)
	{
		// get the globals
		global $wpdb;
		global $current_user;
		
		$sql = "select SQL_CALC_FOUND_ROWS n.id, n.name, n.slug, n.description, n.private, n.open, n.type, m.group_admin
				from ".$this->groups_table." n
				left outer join ".$this->group_members_table." m on m.group_id = n.id
				where m.user_id = ".$current_user->ID."
				and m.status_id = 1
				and m.group_admin = 1
				limit ".$wpdb->escape($start).", ".$wpdb->escape($num).";";

		$groups = $wpdb->get_results($sql);
		$rows = $wpdb->get_var("SELECT found_rows() AS found_rows");
		if ($rows > 0)
		{
			$groups[0]->rows = $rows;
		}
		return $groups;
	}
	
	// is the current user invited to join this group
	function Is_Invited()
	{
		global $current_user;
		global $wpdb;
		
		$sql = 	"select id 
				from  ".$this->group_members_table." 
				where group_id = ".$this->current_group->id."						
				and status_id = 2
				and user_id = ".$current_user->user_ID.";";
		
		if ($wpdb->get_var($sql) == "")
		{
			return false;
		} else {
			return true;
		}
	}
	
	// show a form to join this group, if allowed
	function Join_Group_Form($message="")
	{
		if ($message == ""){ $message = get_site_option("bp_groups_join_message"); }
		
		if ($this->current_group->open == "1")
		{
		
			print "
			<a href=\"".$user->siteurl."/wp-admin/admin.php?page=groups.join&amp;join=".$this->current_group->id."\">Click here to join this group</a>
			";
		
		} else {
		
			print "
			You can only join this group by being invited by a member.
			";
		
		}
	}
	
	// get the featured group
	function Featured_Group($imagesize="m")
	{
		// if the wall application is enabled
		if (get_site_option("bp_groups_enabled") == "true")
		{
		
			// get the globals
			global $wpdb;
			global $current_site;
			global $current_user;
		
			$featured_group = get_site_option("bp_featured_group");
		
			if ($featured_group != "" && $featured_group != "0")
			{
		
				// get the featured group
				$group = $wpdb->get_row("select id, name, slug, description
									from ".$this->groups_table."
									where id = ".$wpdb->escape($featured_group).";");
									
				$group->image = $this->Get_Group_Image($group->id, $imagesize);
				
				return $group;
									
			} else {
			
				return false;
			
			}
			
		}
		
	}
	
	// try to join a group
	function Join_Group()
	{
		global $wpdb;
		global $current_user;
		global $current_user;
		
		$this->id = $_GET["join"];
		$this->Get_Current_Group();
	
		// if the current user is already a member of this group
		if ($this->Is_Group_Member())
		{
		
			$this->message = "<p class=\"error\">".get_site_option("bp_groups_already_member")."</p>";
			
		} else {
		
			$invited = $this->Is_Invited();
		
			// if the group is private and the current user doesn't have an invite
			if (($this->current_group->private  || !$this->current_group->open) && !invited)
			{
			
				$this->message = "<p class=\"error\">".get_site_option("bp_groups_not_invited")."</p>";
				
			} else {
			
				if ($wpdb->query("insert into ".$this->group_members_table."
								(timestamp, status_id, user_id, group_id)
								values
								(UNIX_TIMESTAMP(), 1, ".$current_user->user_id.", ".$wpdb->escape($_GET["join"]).");"))
				{
				
					$this->id = (int)$_GET["join"];
					$this->Get_Current_Group();
					
					$this->message = "<p class=\"success\">".get_site_option("bp_groups_joined")."</p>";
				} else {
					$this->message = "<p class=\"error\">You could not join this group because of a system error</p>";
				}
			}
		
		}
	}
	
	// get an image for a group
	function Get_Group_Image($groupid, $size, $fallback=true, $qs = "")
	{
		global  $current_site;
		global $myjournal_config;
		
		if ($qs != "")
		{
			$qs = "?".$qs;
		}
		
		$image = "";
		
		// check if the image exists
		if (file_exists(ABSPATH."wp-content/groups.dir/".$groupid."/group_image_".$size.".jpg"))
		{
			$image = $current_site->path."wp-content/groups.dir/".$groupid."/group_image_".$size.".jpg".$qs;
		} else {
			if ($fallback)
			{
				$image = $current_site->path."wp-content/groups.dir/0/group_image_".$size.".jpg";
			} else {
				$image = "";
			}
		}

		return $image;
	}
	
	// check the tables exist, if not create them
	function Check_Tables()
	{
		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		global $wpdb;
		
		if($wpdb->get_var("SHOW TABLES LIKE '".$this->groups_table."'") != $this->groups_table)
		{
			/* schema:
				id: auto-incrementing identifier
				timestamp: time this group was created
				status_id: status of this group (0 = dead, 1 = live)
				name: name of this group
				slug: URL-safe version of the name (lowercase, alphanumeric, spaces replaced with -)
				description: description of this group
				private: 1 if this group is private, i.e. can only be seen by members
				open: 0 if this group is private, i.e. requires members to be invited
				type: 1 if this group is a business group, 2 if this is group is a leisure group
				created_by: the ID of the user who created this group
			*/
			$sql = "CREATE TABLE " . $this->groups_table . " (
				  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  timestamp int DEFAULT '0' NOT NULL,
				  status_id tinyint(2) DEFAULT '1' NOT NULL,
				  name varchar(100) NOT NULL,
				  slug varchar(100) NOT NULL,
				  description varchar(300) NOT NULL,
				  private tinyint(1) DEFAULT '0' NOT NULL,
				  open tinyint(1) DEFAULT '1' NOT NULL,
				  type tinyint(1) DEFAULT '1' NOT NULL,
				  created_by int NOT NULL
				);";
			dbDelta($sql);
		}
		
		if($wpdb->get_var("SHOW TABLES LIKE '".$this->group_members_table."'") != $this->group_members_table)
		{
			/* schema:
				id: auto-incrementing identifier
				timestamp: time this group member was created
				status_id: status of this group (0 = dead, 1 = live, 2 = invited)
				user_id: id of this member user
				group_admin: 1 if this member is a group administrator
				group_id: id of the group
				inviter_id: id of the user of the person who invited this member
			*/
			$sql = "CREATE TABLE " . $this->group_members_table . " (
				  id int NOT NULL AUTO_INCREMENT PRIMARY KEY,
				  timestamp int DEFAULT '0' NOT NULL,
				  status_id tinyint(2) DEFAULT '1' NOT NULL,
				  user_id int NOT NULL,
				  group_admin tinyint(1) DEFAULT '0' NOT NULL,
				  group_id int NOT NULL,
				  inviter_id int DEFAULT '0' NOT NULL
				);";
			dbDelta($sql);
		}
		return true;
	}
	
	// check the options exist, if not create them
	function Check_Options()
	{
		foreach ($this->options as $option)
		{
			if (get_site_option($option[1]) == "")
			{
				add_site_option($option[1], $option[2]);
			}
		}
		return true;
	}
	
	// show the groups list
	function Groups()
	{
		include(ABSPATH."wp-content/mu-plugins/bp-groups/bp-groups-list.php");
	}
	
	// show the create group form
	function Create()
	{
		include(ABSPATH."wp-content/mu-plugins/bp-groups/bp-groups-create.php");
	}
	
	// show the edit group form
	function Edit()
	{
		include(ABSPATH."wp-content/mu-plugins/bp-groups/bp-groups-edit.php");
	}
	
	// show the invitations
	function Invites()
	{
		include(ABSPATH."wp-content/mu-plugins/bp-groups/bp-groups-invites.php");
	}
	
	// show the group join search form
	function Join()
	{
		include(ABSPATH."wp-content/mu-plugins/bp-groups/bp-groups-join.php");
	}
	
	// confirm leaving a group
	function Confirm_Leave()
	{
		$this->message = "<p class=\"warning\">Are you sure you want to leave this group? <a href=\"admin.php?page=groups.class.php&amp;leave=".$_GET["leave"]."&amp;confirm=leave\" class=\"del\">Click here to confirm you want to leave this group</a> <a href=\"admin.php?page=groups.class.php\" class=\"cancel\">Click here to stay in this group</a></p>";
	}
	
	// invite a member
	function Invite_Member()
	{
		// get the globals
		global $wpdb;
		global $bp_groups;
		global $current_user;
		global $current_user;
		
		// is this a deleted member
		$deleted_member = $wpdb->get_var("select id from  ".$this->group_members_table." where user_id = ".$wpdb->escape($_GET["invite"])." and group_id = ".$wpdb->escape($_GET["group"])." and status_id = 0;");
		
		// is this an existing member
		$existing_member = $wpdb->get_var("select id from  ".$this->group_members_table." where user_id = ".$wpdb->escape($_GET["invite"])." and group_id = ".$wpdb->escape($_GET["group"])." and status_id = 1;");
		
		// is this an already invited member
		$invited_member = $wpdb->get_var("select id from  ".$this->group_members_table." where user_id = ".$wpdb->escape($_GET["invite"])." and group_id = ".$wpdb->escape($_GET["group"])." and status_id = 2;");
		
		if ($deleted_member > 0)
		{
			$this->message = "<p class=\"error\">You could not invite this member because they are a deleted member of this group. Only a group administrator can invite them back.</p>";
		} else if ($existing_member > 0) {
			$this->message = "<p class=\"error\">You could not invite this member because they are already a member of this group.</p>";
		} else if ($invited_member > 0) {
			$this->message = "<p class=\"error\">You could not invite this member because they have already been invited to join this group.</p>";
		} else {
		
			// get the members email address
			$invited_member = get_userdata($_GET["invite"]);
			$invited_email = $invited_member->user_email;
			$inviter = $current_user->user_email;
			$group = $this->Group_Details($_GET["group"]);
		
			$message_headers = "MIME-Version: 1.0\n" . "From: \"".get_site_option("myjournal_system_name")."\" <support@".$_SERVER["SERVER_NAME"].">\n" . "Content-Type: text/plain; charset=\"" . get_option('user_charset') . "\"\n";

			$message = $current_user->user_nicename . " has invited you to join the '" . $group->name . "' group. Please log in to your dashboard to accept or reject this invitation.\n\nRegards, ".get_site_option("myjournal_system_name")." Administrator";

			if ($wpdb->query("insert into ".$this->group_members_table." (timestamp, status_id, user_id, group_id, inviter_id) values (UNIX_TIMESTAMP(), 2, ".$wpdb->escape($_GET["invite"]).", ".$wpdb->escape($_GET["group"]).", ".$current_user->user_id.");") !== false && wp_mail($invited_email, get_site_option("myjournal_system_name")." group invitation", $message, $message_headers))
			{
				$this->message = "<p class=\"success\">Congratulations, you have invited this member</p>";
			} else {
				$this->message = "<p class=\"error\">You could not invite this member because of a system error</p>";
			}
		}
	}
	
	// leave a group
	function Leave_Group()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($wpdb->query("update  ".$this->group_members_table." set status_id = 0 where user_id=".$current_user->user_id." and group_id = ".$wpdb->escape($_GET["leave"]).";") !== false)
		{
			$this->message = "<p class=\"success\">You have left the group <a href=\"admin.php?page=groups.class.php&amp;undoleave=".$_GET["leave"]."\" class=\"undo\">Undo?</a></p>";
		} else {
			$this->message = "<p class=\"error\">You could not leave this group because of a system error</p>";
		}
	}
	
	// undo leaving a group
	function Undo_Leave_Group()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($wpdb->query("update ".$this->group_members_table." set status_id = 1 where user_id=".$current_user->user_id." and group_id = ".$wpdb->escape($_GET["undoleave"]).";") !== false)
		{
			$this->message = "<p class=\"success\">You have rejoined this group</p>";
		} else {
			$this->message = "<p class=\"error\">You could not rejoin this group because of a system error</p>";
		}
	}
	
	// confirm removing a group member
	function Confirm_Remove()
	{
		if ($this->User_Is_Group_Admin())
		{
			$this->message = "<p class=\"warning\">Are you sure you want to remove this member from this group? <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;remove=".$_GET["remove"]."&amp;confirm=remove&amp;view=members\" class=\"del\">Click here to confirm you want to remove this member</a> <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;view=members\" class=\"cancel\">Click here to keep this group member</a></p>";
		} else {
			$this->message = "<p class=\"error\">You cannot perform this action</p>";
		}
	}
	
	// remove a group member
	function Remove_Member()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update  ".$this->group_members_table." set status_id = 0 where user_id=".$wpdb->escape($_GET["remove"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have removed this member <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;undoremove=".$_GET["remove"]."&amp;view=members\" class=\"undo\">Undo?</a></p>";
			} else {
				$this->message = "<p class=\"error\">This member could not be removed because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">You cannot perform this action</p>";
		}
	}
	
	// undo removing a group member
	function Undo_Remove_Member()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update ".$this->group_members_table." set status_id = 1 where user_id=".$wpdb->escape($_GET["undoremove"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have reinstated this member</p>";
			} else {
				$this->message = "<p class=\"error\">This member could not be reinstated because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">You cannot perform this action</p>";
		}
	}
	
	// confirm cancelling an invite
	function Confirm_Cancel()
	{
		if ($this->User_Is_Group_Admin())
		{
			$this->message = "<p class=\"warning\">Are you sure you want to cancel this group invitation? <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;cancel=".$_GET["cancel"]."&amp;confirm=cancel&amp;view=invites\" class=\"del\">Click here to confirm you want to cancel this invitation</a> <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;view=invites\" class=\"cancel\">Click here to keep this invitation</a></p>";
		} else {
			$this->message = "<p class=\"error\">You cannot perform this action</p>";
		}
	}
	
	// cancel an invite
	function Cancel_Invite()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update  ".$this->group_members_table." set status_id = 0 where user_id=".$wpdb->escape($_GET["cancel"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have cancelled this invitation <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;undocancel=".$_GET["cancel"]."&amp;view=invites\" class=\"undo\">Undo?</a></p>";
			} else {
				$this->message = "<p class=\"error\">This invitation could not be cancelled because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">You cannot perform this action</p>";
		}
	}
	
	// confirm reinviting a member
	function Confirm_Reinvite()
	{
		if ($this->User_Is_Group_Admin())
		{
			$this->message = "<p class=\"warning\">Are you sure you want to invite this member? <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;reinvite=".$_GET["reinvite"]."&amp;confirm=reinvite&amp;view=deleted\" class=\"del\">Click here to confirm you want to reinvite this member</a> <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;view=deleted\" class=\"cancel\">Click here to keep this member deleted</a></p>";
		} else {
			$this->message = "<p class=\"error\">You cannot perform this action</p>";
		}
	}
	
	// reinvite a member
	function Reinvite_Member()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update  ".$this->group_members_table." set status_id = 2 where user_id=".$wpdb->escape($_GET["reinvite"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have reinvited this member</p>";
			} else {
				$this->message = "<p class=\"error\">This member could not be invited because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">" . get_site_option("bp_groups_action_denied") . "</p>";
		}
	}
	
	// confirm joining a group
	function Confirm_Join()
	{
		$this->message = "<p class=\"warning\">Are you sure you want to join this group? <a href=\"admin.php?page=groups.join&amp;join=".$_GET["join"]."&amp;confirm=join\" class=\"del\">Click here to confirm you want to join this group</a> <a href=\"admin.php?page=groups.join\" class=\"cancel\">Click here to cancel joining this group</a></p>";
	}
	
	// confirm promoting a member
	function Confirm_Promote()
	{
		if ($this->User_Is_Group_Admin())
		{
			$this->message = "<p class=\"warning\">Are you sure you want to promote this member to be an administrator? <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;promote=".$_GET["promote"]."&amp;confirm=promote&amp;view=members\" class=\"del\">Click here to confirm you want to promote this member</a> <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;view=members\" class=\"cancel\">Click here to cancel</a></p>";
		} else {
			$this->message = "<p class=\"error\">You cannot perform this action</p>";
		}
	}
	
	// promote a member
	function Promote_Member()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update  ".$this->group_members_table." set group_admin = 1 where user_id=".$wpdb->escape($_GET["promote"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have promoted this member <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;undopromote=".$_GET["promote"]."&amp;view=members\" class=\"undo\">Undo?</a></p>";
			} else {
				$this->message = "<p class=\"error\">This member could not be promoted because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">" . get_site_option("bp_groups_action_denied") . "</p>";
		}
	}
	
	// undo promote a member
	function Undo_Promote_Member()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update  ".$this->group_members_table." set group_admin = 0 where user_id=".$wpdb->escape($_GET["undopromote"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have undone promoting this member</p>";
			} else {
				$this->message = "<p class=\"error\">You could not undo promoting this member because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">" . get_site_option("bp_groups_action_denied") . "</p>";
		}
	}
	
	// confirm reinstating a member
	function Confirm_Reinstate()
	{
		if ($this->User_Is_Group_Admin())
		{
			$this->message = "<p class=\"warning\">Are you sure you want to reinstate this member? <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;reinstate=".$_GET["reinstate"]."&amp;confirm=reinstate&amp;view=deleted\" class=\"del\">Click here to confirm you want to reinstate this member</a> <a href=\"admin.php?page=groups.edit&amp;group=".$_GET["group"]."&amp;view=deleted\" class=\"cancel\">Click here to keep this member deleted</a></p>";
		} else {
			$this->message = "<p class=\"error\">" . get_site_option("bp_groups_action_denied") . "</p>";
		}
	}
	
	// reinstate a member
	function Reinstate_Member()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update  ".$this->group_members_table." set status_id = 1 where user_id=".$wpdb->escape($_GET["reinstate"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have reinstated this member</p>";
			} else {
				$this->message = "<p class=\"error\">This member could not be reinstated because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">" . get_site_option("bp_groups_action_denied") . "</p>";
		}
	}
	
	// undo cancelling an invite
	function Undo_Cancel_Invite()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		if ($this->User_Is_Group_Admin())
		{
			if ($wpdb->query("update ".$this->group_members_table." set status_id = 2 where user_id=".$wpdb->escape($_GET["undocancel"])." and group_id = ".$wpdb->escape($_GET["group"]).";") !== false)
			{
				$this->message = "<p class=\"success\">You have reinstated this invitation</p>";
			} else {
				$this->message = "<p class=\"error\">This invitation could not be reinstated because of a system error</p>";
			}
		} else {
			$this->message = "<p class=\"error\">" . get_site_option("bp_groups_action_denied") . "</p>";
		}
	}
	
	// accept an invitation
	function Accept_Invite()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		global $current_user;
		
		if ($wpdb->query("update ".$this->group_members_table." set status_id = 1 where user_id=".$current_user->user_id." and group_id = ".$wpdb->escape($_GET["accept"]).";") !== false)
		{
			$this->id = (int)$_GET["accept"];
			$this->Get_Current_Group();
		
			$this->message = "<p class=\"success\">Congratulations, you are now a member of the group.</p>";
		} else {
			$this->message = "<p class=\"error\">You could not accept this invitation because of a system error</p>";
		}
	}
	
	// decline an invitation
	function Decline_Invite()
	{
		// get the globals
		global $wpdb;
		global $current_user;
		
		if ($wpdb->query("update ".$this->group_members_table." set status_id = 0 where user_id=".$current_user->user_id." and group_id = ".$wpdb->escape($_GET["decline"]).";") !== false)
		{
			$this->message = "<p class=\"success\">You have declined this invitation. You will not be able to join the group unless someone invites you again.</p>";
		} else {
			$this->message = "<p class=\"error\">You could not decline this invitation because of a system error</p>";
		}
	}
	
	// edit a group
	function Edit_Group()
	{
		global $wpdb;
		
		$this->message = "";
		
		if ($this->User_Is_Group_Admin())
		{
			$private = bp_boolean(@$_POST["private"]);
			$open = bp_boolean(@$_POST["open"]);
			if ($private == 1)
			{
				$open = 0;
			}
		
			if ($wpdb->query("update ".$this->groups_table." set name = '".substr($wpdb->escape(stripslashes(@$_POST["name"])), 0, 100)."', description = '".substr($wpdb->escape(stripslashes(@$_POST["description"])), 0, 300)."', open = ".$open.$private." where id = ".$wpdb->escape($_GET["group"]).";") !== false && $this->Update_Group_Image())
			{
				$this->message = "<p class=\"success\">This group has been updated</p>";
			} else {
				$this->message .= "<p class=\"error\">You could not update this group because of a system error</p>";
			}
			
		} else {
			$this->message = "<p class=\"error\">" . get_site_option("bp_groups_action_denied") . "</p>";
		}
	}
	
	// create a group
	function Create_Group()
	{
		global $wpdb;
		global $current_user;
		global $current_site;
		
		$this->message = "";

		$open = bp_boolean(@$_POST["open"]);
		$private = bp_boolean(@$_POST["private"]);
		$type = (int)@$_POST["type"];
		
		if ($private == 1)
		{
			$open = 0;
		}
		
		$name = substr(stripslashes(@$_POST["name"]), 0, 100);
		
		$slug = sanitize_title($name);
		
		if ($wpdb->get_var("select count(id) from ".$this->groups_table." where slug = '".$slug."';") == 0)
		{
		
			$sql = "insert into ".$this->groups_table."
					(timestamp, name, slug, description, open, private, type, status_id, created_by)
					values
					(UNIX_TIMESTAMP(), '".$wpdb->escape($name)."', '".$slug."', '".substr($wpdb->escape(stripslashes(@$_POST["description"])), 0, 300)."', ".$open.", ".$private.", ".$type.", 1, ".$current_user->ID.");";
					//print $sql."<br />";
			$create_group = $wpdb->query($sql);
							
			$group_id = $wpdb->get_var("select id from ".$this->groups_table." where slug = '".$slug."' order by timestamp desc limit 1;");
			
			$sql = "insert into ".$this->group_members_table."
					(timestamp, status_id, user_id, group_id, group_admin)
					values
					(UNIX_TIMESTAMP(), 1, ".$current_user->id.", ".$group_id.", 1);";
					//print $sql."<br />";
			$insert_member = $wpdb->query($sql);
			
			$this->id = $group_id;
			$this->Get_Current_Group();
			
			if ($create_group !== false && $insert_member !== false)
			{
				if ($this->Update_Group_Image())
				{
					$this->message = "<p class=\"success\">Congratulations, your new group has been created. <a href=\"".$current_site->path."groups/".$slug."/\">Click here to see it</a>.</p>";
				} else {
					$this->message = "<p class=\"success\">Congratulations, your new group has been created, however the image you have chosen could not be saved. You can try to set the image for this group any time you want. <a href=\"".$current_site->path."groups/".$slug."/\">Click here to see your new group</a>.</p>";
				}
			} else {
				$this->message .= "<p class=\"error\">Sorry, this group could not be created because of a system error (".$wpdb->print_error().")</p>";
			}
		} else {
		
			$this->message .= "<p class=\"error\">".get_site_option("bp_error_duplicate_group_name")."<a href=\"".$current_site->path."groups/".$slug."/\">Click here to see the group with that name</a></p>";
		
		}
	}
	
	// update an image for the current group
	function Update_Group_Image()
	{
		// if there is an image
		if (is_array($_FILES["image"]) && $_FILES["image"]["name"] != "")
		{
		
			// Upload the image using the built in WP upload function.			
			$image = wp_handle_upload($_FILES["image"], 
				array("action" => "save", 
					  "upload_error_strings" => $upload_error_strings,
					  "uploads" => $uploads)
			 );
		
			global $wpdb;
			global $current_site;
			global $current_user;
			$image = new Image();
			
			$sizes = 	array(
						array("suffix"=>"f", "width"=>200, "width_max"=>true, "width_fixed"=>false, "height"=>200, "height_max"=>true, "height_fixed"=>false),
						array("suffix"=>"l", "width"=>193, "width_max"=>false, "width_fixed"=>true, "height"=>260, "height_max"=>true, "height_fixed"=>false),
						array("suffix"=>"m", "width"=>120, "width_max"=>false, "width_fixed"=>true, "height"=>90, "height_max"=>true, "height_fixed"=>false),
						array("suffix"=>"t", "width"=>89, "width_max"=>false, "width_fixed"=>true, "height"=>62, "height_max"=>false, "height_fixed"=>true),
						array("suffix"=>"s", "width"=>36, "width_max"=>false, "width_fixed"=>true, "height"=>36, "height_max"=>false, "height_fixed"=>true),
					);
			
			// upload and resize the new image
			$errors = $image->uploadAndResize($_FILES["image"], "myjournal_group_image", $sizes, "groups.dir/".$this->current_group->id, "jpg");
			
			if ($errors == "")
			{
				$value = $option[1];
				$image->delete("groups.dir/".$this->current_group->id."_original.jpg");
					
				return true;
			} else {
				$this->message .= "<p class=\"error\">".get_site_option("bp_error_saving_image")."</p><ul>" . $errors . "</ul>";
				$value = null;
				return false;
			}
			
		} else {
			// no image supplied
			return true;
		}
	}
	
	// show the current message
	function Show_Message()
	{
		if ($this->message != "")
		{
			print $this->message;
		}
	}
}
// create the new BP_Groups object
global $bp_groups;
$bp_groups = new BP_Groups();
?>