<?php
/*
Admin file to allow administration of BuddyPress groups

Version history:
1: 21/03/2008 Chris Taylor
*/
global $bp_groups;
global $current_site;
global $current_user;

if (isset($_GET["group"]) && $_GET["group"] != "")
{
	$bp_groups->id = $_GET["group"];
	$bp_groups->Get_Current_Group();
}

// if an action is set
if (isset($_POST["bp_action"]) && $_POST["bp_action"] == "edit_bp_group" && isset($_GET["group"]) && $_GET["group"] != "")
{

	$bp_groups->Edit_Group();
	
	$bp_groups->id = $_GET["group"];
	$bp_groups->Get_Current_Group();

}

// remove a group member
if (isset($_GET["remove"]))
{
	if (!isset($_GET["confirm"]) || $_GET["confirm"] != "remove")
	{
		$bp_groups->Confirm_Remove();
	} else {
		$bp_groups->Remove_Member();
	}
}

// undo a removal
if (isset($_GET["undoremove"]))
{
	$bp_groups->Undo_Remove_Member();
}

// cancel an invitation
if (isset($_GET["cancel"]))
{
	if (!isset($_GET["confirm"]) || $_GET["confirm"] != "cancel")
	{
		$bp_groups->Confirm_Cancel();
	} else {
		$bp_groups->Cancel_Invite();
	}
}

// undo an invitation cancel
if (isset($_GET["undocancel"]))
{
	$bp_groups->Undo_Cancel_Invite();
}

// promote a member
if (isset($_GET["promote"]))
{
	if (!isset($_GET["confirm"]) || $_GET["confirm"] != "promote")
	{
		$bp_groups->Confirm_Promote();
	} else {
		$bp_groups->Promote_Member();
	}
}

// undo a promotion
if (isset($_GET["undopromote"]))
{
	$bp_groups->Undo_Promote_Member();
}

// reinvite a group member
if (isset($_GET["reinvite"]))
{
	if (!isset($_GET["confirm"]) || $_GET["confirm"] != "reinvite")
	{
		$bp_groups->Confirm_Reinvite();
	} else {
		$bp_groups->Reinvite_Member();
	}
}

// reinstate a group member
if (isset($_GET["reinstate"]))
{
	if (!isset($_GET["confirm"]) || $_GET["confirm"] != "reinstate")
	{
		$bp_groups->Confirm_Reinstate();
	} else {
		$bp_groups->Reinstate_Member();
	}
}

$pagemax = 10;

$start = bp_get_page_start(@$_GET["p"], $pagemax);
$groups = $bp_groups->Admin_Groups($start, $pagemax);
?>
<div class="wrap">

	<h2>Administer groups</h2>
	
	<?php
	if (!isset($_GET["group"]) || $_GET["group"] == "")
	{

		if (is_array($groups) && count($groups)>0)
		{
		
			$alt = " class=\"pad\"";
			foreach ($groups as $group)
			{
				?>
				
				<div<?php print $alt; ?>>
							
				<h4><a href="<?php print $current_site->path; ?>groups/<?php print $group->slug; ?>/"><img src="<?php print $bp_groups->Get_Group_Image($group->id, "m"); ?>" alt="<?php print stripslashes($group->name); ?>" class="left" /><?php print stripslashes($group->name); ?></a></h4>
				<p><?php print stripslashes($group->description); ?></p>
				<p><a href="admin.php?page=groups.edit&amp;group=<?php print $group->id; ?>" class="undo">Administer this group</a></p>
				
				</div>
							
				<?php
				if ($alt == " class=\"pad alt\""){ $alt=" class=\"pad\""; } else { $alt = " class=\"pad alt\""; }
			}
				
		} else {
				
			?>
					
			<p>You are not an administrator of any groups.</p>
			
			<?php
				
		}

	} else {
		
		if (!$bp_groups->Is_Group_Member($current_user->ID))
		{
		
			?>
			
			<p>You are not a member of the selected group. Please click back and try again.</p>
			
		<?php
		} else {
		
			if (!$bp_groups->Is_Group_Admin($current_user->ID))
			{
				?>
			
				<p>You are not an administrator of the selected group. You cannot edit the details for this group.</p>
				
				<?php
			} else {
			
				print $bp_groups->message;
				
				if (!isset($_GET["view"]))
				{
					?>
			
					<h3>Settings for '<?php print $this->current_group->name; ?>'</h3>
					
					<h4><a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=members" class="button">Administer members</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=invites" class="button">Administer invitations</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=deleted" class="button">Administer deleted members</a></h4>
					
					<form action="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>" method="post" enctype="multipart/form-data">
					<fieldset>
						
						<h6><?php if ($this->current_group->private){ print " This is a private group."; } else { print "Anyone can see this group."; } ?></h6>
						
						<p><label for="name">Name:</label> <input type="text" name="name" id="name" value="<?php print stripslashes($this->current_group->name); ?>" /></p>
						
						<h6>The description is limited to 300 characters.</h6>
						<p><label for="description">Description:</label> <textarea name="description" id="description" cols="30" rows="4"><?php print stripslashes($this->current_group->description); ?></textarea></p>
						
						<h6>Tick this to hide details about this group such as the membership and discussions from the public. All details will be seen by members.</h6>
						<p><label for="private">Private:</label> <input type="checkbox" name="private" id="private" value="1" class="button"<?php print bp_boolean_ticked($this->current_group->private); ?> /></p>
						
						<h6>Tick this to allow anyone to join the group. If this is left unticked then new members must be invited by existing members.</h6>
						<p><label for="open">Open membership:</label> <input type="checkbox" name="open" id="open" value="1" class="button"<?php print bp_boolean_ticked($this->current_group->open); ?> /></p>
					
						<h6>Change the image for your group, or just keep the current one. Remember, only JPEG files are allowed.</h6>
						<p><label for="image">Group image</label>
						<input type="file" name="image" id="image" class="m" /></p>
						<p><label>Current image</label><img src="<?php print $bp_groups->Get_Group_Image($bp_groups->current_group->id, "m", true, time()); ?>" alt="Current image" /></p>
					
						<p>
							<label for="edit_group">Edit group</label><input type="submit" name="edit_group" id="edit_group" value="Edit group now" class="button" />
							<input type="hidden" name="bp_action" id="bp_action" value="edit_bp_group" />
						</p>
					
					</fieldset>
					</form>
					
					<?php
				}
				if ($_GET["view"] == "members")
				{
					
					$start = bp_get_page_start(@$_GET["p"], $pagemax);

					$members = $bp_groups->Group_Members($start, $pagemax);
				
					?>
					
					<h3>Members of '<?php print $this->current_group->name; ?>'</h3>
					
					<h4><a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>" class="button">Administer settings</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=invites" class="button">Administer invitations</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=deleted" class="button">Administer deleted members</a></h4>
					
					<?php
					if (is_array($members) && count($members) > 0)
					{
					
						$pages = bp_generate_pages_links($bp_groups->current_group->members,$pagemax,"admin.php?page=groups.edit&group=".$this->current_group->id."&view=members&p=%%",$_GET["p"]);
						$pagelinks = bp_paginate($pages, bp_int(@$_GET["p"],true));
					
						if ($bp_groups->current_group->members > $pagemax)
						{
							print $pagelinks;
						}
					
						$alt = " class=\"pad\"";
						foreach ($members as $member)
						{
							?>
							
							<div<?php print $alt; ?>>
							
							<p>
							<strong><?php print stripslashes($member->display_name); ?></strong>
							<?php
							if ($member->admin)
							{
								?>
								<br />
								<span class="note"><?php print stripslashes($member->display_name); ?> is an administrator of this group. Group administrators can only be removed by site administrators.</span>
								<?php
							} else {
								?>
								<br />
								<a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=members&amp;remove=<?php print $member->user_id; ?>" class="del">Remove this member from this group</a>
								<br />
								<a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=members&amp;promote=<?php print $member->user_id; ?>" class="undo">Promote this member to be an administrator of this group</a>
								<?php
							}
							?>
							</p>
							
							</div>
							
							<?php
							if ($alt == " class=\"pad alt\""){ $alt=" class=\"pad\""; } else { $alt = " class=\"pad alt\""; }
						}
						
						if ($bp_groups->current_group->members > $pagemax)
						{
							print $pagelinks;
						}
						
					} else {
						?>
						
						<p>There are no members of this group.</p>
						
						<?php
					}
				}
				if ($_GET["view"] == "deleted")
				{
				
					$start = bp_get_page_start(@$_GET["p"], $pagemax);

					$members = $bp_groups->Deleted_Members($start, $pagemax);
				
					?>
					
					<h3>Deleted members of '<?php print $this->current_group->name; ?>'</h3>
					
					<h4><a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>" class="button">Administer settings</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=invites" class="button">Administer invitations</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=members" class="button">Administer members</a></h4>
					
					<?php

					if (is_array($members) && count($members) > 0)
					{
					
						$pages = bp_generate_pages_links($members[0]["rows"],$pagemax,"admin.php?page=groups.edit&group=".$this->current_group->id."&view=deleted&p=%%",$_GET["p"]);
						$pagelinks = bp_paginate($pages, bp_int(@$_GET["p"],true));
					
						if ($members[0]->rows > $pagemax)
						{
							print $pagelinks;
						}
					
						foreach ($members as $member)
						{
							?>
							
							<p>
							<strong><?php print stripslashes($member->display_name); ?></strong>
							<?php
							if ($member["admin"])
							{
								?>
								<br />
								<span class="note"><?php print stripslashes($member->display_name); ?> is an administrator of this group</span>
								<?php
							} else {
								?>
								<br />
								<a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=deleted&amp;reinvite=<?php print $member->user_id; ?>" class="cancel">Invite this member to this group</a>
								<br />
								<a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=deleted&amp;reinstate=<?php print $member->user_id; ?>" class="undo">Reinstate this member</a>
								<?php
							}
							?>
							</p>
							
							<?php
						}
						
						if ($members[0]["rows"] > $pagemax)
						{
							print $pagelinks;
						}
					} else {
						?>
						
						<p>There are no deleted members of this group.</p>
						
						<?php
					}
				}
				if ($_GET["view"] == "invites")
				{
				
					$start =bp_get_page_start(@$_GET["p"], $pagemax);

					$invites = $bp_groups->Group_Invites($start, $pagemax);
					
					?>
				
					<h3>Invitations for '<?php print $this->current_group->name; ?>'</h3>
				
					<h4><a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>" class="button">Administer settings</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=members" class="button">Administer members</a> <a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=deleted" class="button">Administer deleted members</a></h4>
				
					<?php
					if (is_array($invites) && count($invites) > 0)
					{
					
						$pages = bp_generate_pages_links($invites[0]->rows,$pagemax,"admin.php?page=groups.edit&group=".$this->current_group->id."&view=invites&p=%%",$_GET["p"]);
						$pagelinks = bp_paginate($pages, bp_int(@$_GET["p"],true));
					
						if ($invites[0]->rows > $pagemax)
						{
							print $pagelinks;
						}
					
						foreach ($invites as $invite)
						{
							?>
					
							<p>
							<strong><?php print stripslashes($member->display_name); ?></strong>
							<br />
							Invited by <?php print stripslashes($invite->inviter_display_name); ?>
							<br />
							<a href="admin.php?page=groups.edit&amp;group=<?php print $this->current_group->id; ?>&amp;view=invites&amp;cancel=<?php print $invite->user_id; ?>" class="del">Cancel this invitation</a>

							</p>
					
							<?php
						}
						
						if ($invites[0]->rows > $pagemax)
						{
							print $pagelinks;
						}
						
					} else {
					?>
					
					<p>There are no invitations for this group.</p>
					
					<?php
					}
				}
			}
		}
	}
	?>
	
</div>