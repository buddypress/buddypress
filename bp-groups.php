<?php

add_site_option('bp-groups-version', '0.1');

$bp_groups_table_name = $wpdb->base_prefix . "bp_groups";
$bp_group_members_table_name = $wpdb->base_prefix . "bp_group_members";

include_once('bp-groups/bp-groups-class.php');

/**************************************************************************
 
 groups API functions
 
 **************************************************************************/

// get the current group
function bp_groups_get_current_group($var)
{
	global $bp_groups;
	if (is_int($var))
	{
		$bp_groups->id = $var;
	} else {
		$bp_groups->slug = $var;
	}
	return $bp_groups->Get_Current_Group();
}

// get group details
function bp_groups_group_details($id)
{
	global $bp_groups;
	return $bp_groups->Group_Details($id);
}

// search groups
function bp_groups_search($q, $start=0, $num=10)
{
	global $bp_groups;
	return $bp_groups->Search_Groups($q, $start, $num);
}

// latest created groups
function bp_groups_latest($limit=6, $start=0)
{
	global $bp_groups;
	return $bp_groups->Latest_Groups($limit, $start);
}

// most popular groups
function bp_groups_popular($q, $start=0, $num=10)
{
	global $bp_groups;
	return $bp_groups->Popular_Groups();
}

// groups for the current user
function bp_groups_user_groups($start = 0, $num = 10, $all = false)
{
	global $bp_groups;
	return $bp_groups->User_Groups($start, $num, $all);
}

// total number of invites for the current user
function bp_groups_user_invites_num()
{
	global $bp_groups;
	return $bp_groups->User_Invites_Num();
}

// get invites for the current user
function bp_groups_user_invites($start = 0, $num = 10)
{
	global $bp_groups;
	return $bp_groups->User_Invites($start, $num);
}

// get invites for the current group
function bp_groups_invites($start = 0, $num = 10)
{
	global $bp_groups;
	return $bp_groups->Group_Invites($start, $num);
}

// get members of the current group
function bp_groups_members($start = 0, $num = 10)
{
	global $bp_groups;
	return $bp_groups->Group_Members($start, $num);
}

// get deleted members of the current group
function bp_groups_deleted_members($start = 0, $num = 10)
{
	global $bp_groups;
	return $bp_groups->Deleted_Members($start, $num);
}

// is the current user a member of the current group
function bp_groups_is_member($userid = 0)
{
	global $bp_groups;
	return $bp_groups->Is_Group_Member($userid);
}

// is the current user an administrator of the current group
function bp_groups_is_admin($userid = 0)
{
	global $bp_groups;
	return $bp_groups->User_Is_Group_Admin($userid);
}

// is the current user an administrator of any groups
function bp_groups_admin_rights($userid = 0)
{
	global $bp_groups;
	return $bp_groups->Has_Group_Admin_Rights($userid);
}

// groups the current user is an administrator of
function bp_groups_admin_groups($start = 0, $num = 10)
{
	global $bp_groups;
	return $bp_groups->Admin_Groups($start, $num);
}

// has the current user been invited to join the current group
function bp_groups_is_invited()
{
	global $bp_groups;
	return $bp_groups->Is_Invited();
}

// the featured group
function bp_groups_featured()
{
	global $bp_groups;
	return $bp_groups->Featured_Group();
}
?>