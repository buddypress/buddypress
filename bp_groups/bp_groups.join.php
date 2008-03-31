<?php
/*
Admin file to allow users to search and join BuddyPress groups

Version history:
1: 21/03/2008 Chris Taylor
*/
global $bp_groups;
global $current_site;

// leave a group
if (isset($_GET["join"]))
{
	if (!isset($_GET["confirm"]) || $_GET["confirm"] != "join")
	{
		$bp_groups->Confirm_Join();
	} else {
		$bp_groups->Join_Group();
	}
}

// undo a deletion
if (isset($_GET["undojoin"]))
{
	$bp_groups->Undo_Join_Group();
}
?>
<div class="wrap">

	<h2>Join a group</h2>
	
	<?php
	print $bp_groups->message;
	?>
	
	<p>Search for groups you wish to join below.</p>
	
	<form action="admin.php?page=groups.join" method="post">
	
	<fieldset>
	
	<p><label for="q">Search for</label>
	<input type="text" name="q" id="q" /></p>
	
	<p><label for="go">Search now</label>
	<input type="submit" name="go" id="go" class="button" value="Search groups now" /></p>
	
	</fieldset>
	
	</form>
	
	<?php
	
	if (isset($_POST["q"]) && $_POST["q"] != "")
	{
	
		$groups = $bp_groups->Search_Groups($_POST["q"]);
		
		if (is_array($groups) && count($groups) > 0)
		{
		
		?>
		<h3>Groups found for your search</h3>
		<?php
		
			$alt = " class=\"pad\"";
		
			foreach ($groups as $group)
			{
			
				?>
				
				<div<?php print $alt; ?>>
				
				<p>
				<a href="<?php print $current_site->path; ?>groups/<?php print $group->slug; ?>/">
				<img src="<?php print $group->image; ?>" alt="<?php print stripslashes($group->name); ?>" class="left" />
				</a>
				<strong><?php print stripslashes($group->name); ?></strong>
				<a href="admin.php?page=groups.join&amp;join=<?php print $group->id; ?>" class="cancel">Join this group</a>
				<br />
				<?php print stripslashes($group->description); ?>
				</p>
				
				</div>
				
				<?php
				
				if ($alt == " class=\"pad alt\""){ $alt=" class=\"pad\""; } else { $alt = " class=\"pad alt\""; }
			
			}
		
		} else {
		
			?>
			
			<h3>No groups found for your search</h3>
			
			<p>There were no groups found for your search. Please change your search words and try again.</p>
			
			<?php
		
		}
	
	}
	?>
	
</div>