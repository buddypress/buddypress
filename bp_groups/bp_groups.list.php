<?php
/*
Admin file to allow users to see the BuddyPress groups they are a member of

Version history:
1: 21/03/2008 Chris Taylor
*/
global $bp_groups;
global $current_site;

// leave a group
if (isset($_GET["leave"]))
{
	if (!isset($_GET["confirm"]) || $_GET["confirm"] != "leave")
	{
		$bp_groups->Confirm_Leave();
	} else {
		$bp_groups->Leave_Group();
	}
}

// undo a deletion
if (isset($_GET["undoleave"]))
{
	$bp_groups->Undo_Leave_Group();
}

$pagemax = 10;

$start = bp_get_page_start(@$_GET["p"], $pagemax);
$groups = $bp_groups->User_Groups($start, $pagemax, true);
?>
<div class="wrap">

	<h2>Your groups</h2>
	
	<?php print $bp_groups->message; ?>
	
	<?php	
	if (is_array($groups) && count($groups)>0 && $groups[0]->id != "")
	{

		$pages = bp_generate_pages_links($groups[0]->rows,$pagemax,"admin.php?page=groups.class.php&p=%%",@$_GET["p"]);
		$pagelinks = bp_paginate($pages, bp_int(@$_GET["p"],true));

		if ($groups[0]->rows > $pagemax)
		{
			print $pagelinks;
		}
		
		$alt = " class=\"pad\"";
	
		foreach ($groups as $group)
		{
		?>
		
		<div<?php print $alt; ?>>
		
		<h4><?php print stripslashes($group->name); ?></a> (<?php print $group->members; ?> member<?php print bp_plural($group->members); ?>)</h4>
		<p><?php print stripslashes($group->description); ?></p>
					
		<?php
		if ($group->private)
		{
			print "<h6>This is a private group, it is not shown to the public. <a href=\"admin.php?page=groups.invites&amp;group=".$group->id."\">Click here to invite people to join this group.</a></h6>";
		}
		else if (!$group->open)
		{
			print "<h6>New members of this group must be invited. <a href=\"admin.php?page=groups.invites&amp;group=".$group->id."\">Click here to invite people to join this group.</a></h6>";
		} else {
			print "<p><a href=\"admin.php?page=groups.invites&amp;group=".$group->id."\">Click here to invite people to join this group.</a></p>";
		}
		?>
		<p><a href="admin.php?page=groups.class.php&amp;leave=<?php print $group->id; ?>" class="del">Leave this group</a>
		<?php
		if ($group->group_admin == 1)
		{
		?>
		or <a href="admin.php?page=groups.edit&amp;group=<?php print $group->id; ?>" class="undo">Administer this group</a>
		<?php
		}
		?>
		</p>
		</div>
		<?php
		
		if ($alt == " class=\"pad alt\""){ $alt=" class=\"pad\""; } else { $alt = " class=\"pad alt\""; }
		
		}
		
		if ($groups[0]->rows > $pagemax)
		{
			print $pagelinks;
		}
			
	} else {
			
		?>
				
		<p>You are not a member of any groups yet. <a href="admin.php?page=groups.join">Click here to find groups you would like to join</a>.</p>
		
		<?php
			
	}
	?>
	
</div>
