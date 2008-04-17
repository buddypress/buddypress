<?php
/*
Admin file to allow creation of BuddyPress groups

Version history:
1: 21/03/2008 Chris Taylor
*/
global $bp_groups;

// set the defaults
$desc = "";
$name = "";

// if a myjournal action is set
if (isset($_POST["bp_action"]) && $_POST["bp_action"] == "create_bp_group")
{
	
	if (isset($_POST["description"]) && trim($_POST["description"]) != "" && isset($_POST["name"]) && trim($_POST["name"]) != "")
	{
	
		$bp_groups->Create_Group();
		
	} else {
	
		$message = "<p class=\"error\">You must enter a name and description for this group. Please try again.</p>";
		$desc = trim(@$_POST["description"]);
		$name = trim(@$_POST["name"]);
	
	}

}
?>
<div class="wrap myjournal">

	<h2>Create a group</h2>
	
	<?php
	$bp_groups->Show_Message();
	
	print $message;
	?>
	
	<form action="admin.php?page=groups.create" method="post" enctype="multipart/form-data">
	<fieldset>
		
		<h6>Write a descriptive name about your group. Shorter is better.</h6>
		<p><label for="name">Name:</label> <input type="text" name="name" id="name" value="<?php print $name; ?>" /></p>
		
		<h6>What is this group for? What type of people would you like to join this group? Add a description of your new group here. Your text will be limited to 300 characters, so try to be brief.</h6>
		<p><label for="description">Description:</label> <textarea name="description" id="description" cols="30" rows="4"><?php print $desc; ?></textarea></p>
		
		<h6>Tick this to hide details about this group such as the membership and discussions from the public. All details will be seen by members.</h6>
		<p><label for="private">Private group:</label> <input type="checkbox" name="private" id="private" value="1" class="button" /></p>
		
		<h6>Tick this to allow anyone to join the group. If this is left unticked then new members must be invited by existing members.</h6>
		<p><label for="open">Open membership:</label> <input type="checkbox" name="open" id="open" value="1" class="button" /></p>
		
		<h6>Choose an image for your new group from your computer.</h6>
		<p><label for="image">Group image</label>
		<input type="file" name="image" id="image" class="m" /></p>
	
		<p>Creating this group will automatically make you a member and administrator.</p>
	
		<p>
			<label for="create_group">Create group</label><input type="submit" name="create_group" id="create_group" value="Create group now" class="button" />
			<input type="hidden" name="bp_action" id="bp_action" value="create_bp_group" />
		</p>
	
	</fieldset>
	</form>
	
</div>