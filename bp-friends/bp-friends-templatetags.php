<?php

Class BP_Friends_Template {
	
	function bp_friends_template() {
		global $authordata;
		$this->user_id = $authordata->ID;
	}

}

function bp_list_friends() { 
	$bp_friends = new BP_Friends();
	$friends = $bp_friends->get_friends();
}

function bp_validate_friend_search_terms() {
	$message = null;
	if(isset($_POST['searchterm']) && isset($_POST['search']))
	{
		if($_POST['searchterm'] == "")
		{
			$message = __("Please make sure you enter something to search for.");
		}
		else if(strlen($_POST['searchterm']) < 3)
		{
			$message = __("Your search term must be longer than 3 letters.");
		}
	}
	return $message;
}

function bp_output_find_friends_message($message) {
	if ( isset($message) ) {
		if($type == 'error') { $type = "error"; } else { $type = "updated"; } ?>
			<div id="message" class="<?php echo $type; ?>">
				<p><?php echo $message; ?></p>
			</div> <?php
		return true;
	} else {
		return false;
	}
}
?>
