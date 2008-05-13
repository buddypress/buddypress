<?php

Class BP_Friends_Template {
	
	var $user_id;
	
	function bp_friends_template() {
		global $authordata;
		$this->user_id = $authordata->ID;
	}

}

function bp_list_friends() { 
	global $friends_template;
	return $friends_template->list_friends();
}

function bp_find_friends() { 
	global $friends_template;
	return $friends_template->find_friends();
}

?>
