<?php
/**************************************************************************
 friends_add_js()
  
 Inserts the Javascript needed for managing friends.
 **************************************************************************/	

function friends_add_js() {
	global $bp_friends_image_base;
	?>
	
	<?php
}


/**************************************************************************
 add_css()
  
 Inserts the CSS needed to style the friends pages.
 **************************************************************************/	

function friends_add_css()
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

?>