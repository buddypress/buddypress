<?php
/*
Admin file to show user invitations to BuddyPress groups

Version history:
1: 21/03/2008 Chris Taylor
*/
global $bp_groups;
global $current_site;

if (isset($_GET["invite"]) && $_GET["invite"] != "")
{
	$bp_groups->Invite_Member();
}

// accept an invitation
if (isset($_GET["accept"]))
{
	$bp_groups->Accept_Invite();
}

// decline an invitation
if (isset($_GET["decline"]))
{
	$bp_groups->Decline_Invite();
}
?>
<div class="wrap">

	<h2>Group invitations</h2>
	
	<?php
	print $bp_groups->message;
	
	if (!isset($_GET["group"]) || $_GET["group"] == "")
	{
	
		$invites = $bp_groups->User_Invites();
		if (is_array($invites) && count($invites) > 0)
		{
		?>
		
			<h3>You have been invited to join the following groups:</h3>
			
			<?php
			foreach ($invites as $group)
			{
				?>
				
				<h4 class="clear"><a href="<?php print $current_site->path; ?>groups/<?php print $group->slug; ?>/"><img src="<?php print $bp_groups->Get_Group_Image($group->id, "m"); ?>" alt="<?php print stripslashes($group->name); ?>" class="left" /><?php print stripslashes($group->name); ?></a></h4>
				<p><a href="admin.php?page=groups.invites&amp;accept=<?php print $group->id; ?>" class="cancel">Accept invitation</a> <a href="admin.php?page=groups.invites&amp;decline=<?php print $group->id; ?>" class="del">Decline invitation</a></p>
				<p>You were invited by <a href="<?php print $group->inviter_url; ?>"><?php print stripslashes($group->inviter); ?></a> from <?php print "<a href=\"".$group->siteurl."\">".stripslashes($group->blogname)."</a>"; ?></p>
				
				<?php
			}
		} else {
		
		?>
		
		<p>You have no invitations to join groups. <a href="admin.php?page=groups.join">Click here to find groups you would like to join</a>.</p>
		
		<?php
		}
	
	} else {
	?>
	
	<h3>Invite someone to join this group</h3>
	
	<p>Search for the member you wish to invite to join this group below.</p>
	
	<form action="admin.php?page=groups.invites&group=<?php print $_GET["group"]; ?>" method="post">
	
	<fieldset>
	
	<p><label for="q">Search for</label>
	<input type="text" name="q" id="q" /></p>
	
	<p><label for="go">Search now</label>
	<input type="submit" name="go" id="go" class="button" value="Search members now" /></p>
	
	</fieldset>
	
	</form>
	
	<?php
	if (isset($_POST["q"]) && $_POST["q"] != "")
	{
	
		$users = bp_search_users($_POST["q"]);
		
		if (is_array($users) && count($users) > 0)
		{
		
			foreach ($users as $user)
			{
			
				?>
				
				<p>
				<strong><?php print $user->display_name; ?></strong>
				<br /><a href="admin.php?page=groups.invites&amp;group=<?php print $_GET["group"]; ?>&amp;invite=<?php print $user->id; ?>" class="cancel">Invite <?php print $user->display_name; ?> to this group</a>
				</p>
				
				<?php
			
			}
		
		} else {
		
			?>
			
			<p>There were no users found for your search. Please change your search words and try again.</p>
			
			<?php
		
		}
	
	}
	
	}
	?>
	
</div>