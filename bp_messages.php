<?php

/**************************************************************************
 
 Plugin Name: 
 BuddyPress Private Messaging

 Description: 
 Private messaging between site users
 
 --------------------------------------------------------------------------
 Version: 0.1
 Type: Add-On
 **************************************************************************/


/**************************************************************************
 messages_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function messages_install()
{
	global $wpdb, $table_name;

	$sql = "CREATE TABLE ". $table_name ." (
		  id mediumint(9) NOT NULL AUTO_INCREMENT,
		  sender_id mediumint(9) NOT NULL,
		  recipient_id mediumint(9) NOT NULL,
		  subject varchar(200) NOT NULL,
		  message longtext NOT NULL,
		  is_read bool DEFAULT 0,
		  is_draft bool DEFAULT 0,
		  date_sent int(11) NOT NULL,
		  UNIQUE KEY id (id)
		 );";

	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
	dbDelta($sql);
}


/**************************************************************************
 messages_add_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function messages_add_menu() 
{	
	global $wpdb, $table_name, $wpmuBaseTablePrefix, $bp_messages;
	$table_name = $wpmuBaseTablePrefix . "bp_messages";
	
	/* Instantiate bp_Messages class to do the real work. */
	$bp_messages = new BP_Messages;
	$bp_messages->bp_messages();
	
	$inbox_count = $bp_messages->get_inbox_count();
	
	add_menu_page("Messages", "Messages$inbox_count", 1, basename(__FILE__), "messages_write_new");
	add_submenu_page(basename(__FILE__), "Write New", "Write New", 1, basename(__FILE__), "messages_write_new");
	add_submenu_page(basename(__FILE__), "Inbox", "Inbox$inbox_count", 1, "messages_inbox", "messages_inbox");	
	add_submenu_page(basename(__FILE__), "Sentbox", "Sentbox", 1, "messages_sentbox", "messages_sentbox");
	add_submenu_page(basename(__FILE__), "Drafts", "Drafts", 1, "messages_drafts", "messages_drafts");

	/* Add the administration tab under the "Site Admin" tab for site administrators */
	add_submenu_page('bp_core.php', "Messages", "Messages", 1, basename(__FILE__), "messages_settings");

	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if($wpdb->get_var("show tables like '$table_name'") != $table_name) messages_install();
}
add_action('admin_menu','messages_add_menu');


/**************************************************************************
 messages_write_new(), messages_inbox(), messages_sentbox(), 
 messages_drafts(), messages_add_js(), messages_add_css()
 
 These are all wrapper functions used in Wordpress hooks to pass through to 
 correct functions within the bp_messages object. Seems the only way
 Wordpress will handle this.
 **************************************************************************/

function messages_write_new() { global $bp_messages; $bp_messages->write_new($_POST['username'], $_POST['subject'], $_POST['message']); }
function messages_inbox() 	  { global $bp_messages; $bp_messages->inbox(); }
function messages_sentbox()   { global $bp_messages; $bp_messages->sentbox(); }
function messages_drafts()    { global $bp_messages; $bp_messages->drafts(); }
function messages_add_js()	  { global $bp_messages; $bp_messages->add_js(); }
function messages_add_css()	  { global $bp_messages; $bp_messages->add_css(); }


/**************************************************************************
 bp_Messages [Class]
 
 Where all the magic happens.
 **************************************************************************/
 
class BP_Messages
{
	var $wpdb;
	var $tableName;
	var $basePrefix;
	var $userdata;
	var $imageBase;
	var $inboxCount;


	/**************************************************************************
 	 bp_messages()
 	  
 	 Contructor function.
 	 **************************************************************************/
	function bp_messages()
	{
		global $wpdb, $wpmuBaseTablePrefix, $userdata, $table_name;
		 
		$this->wpdb = &$wpdb;
		$this->userdata = &$userdata;
		$this->basePrefix = $wpmuBaseTablePrefix;
		$this->tableName = $table_name; // need a root prefix, not a wp_X_ prefix.
		$this->imageBase = get_option('siteurl') . '/wp-content/mu-plugins/bp_messages/images/';

		/* Set up Constants */
		define("IS_DRAFT", 1);
		define("INBOX", 1);
		define("SENTBOX", 2);
		
		/* Setup CSS and JS */
		add_action("admin_print_scripts", "messages_add_css");
		add_action("admin_print_scripts", "messages_add_js");
	}

	
	/**************************************************************************
 	 write_new()
 	  
 	 Handles the generation of the write new message form, as well as handling
 	 validation and directing saving to the DB to the correct function.
 	 **************************************************************************/	
 	 
	function write_new($username = '', $subject = '', $message = '', $alert_type = '', $alert_message = '') 
	{
		if(isset($_GET['mode']) && isset($_POST['send']) && $_GET['mode'] == "send_message")
		{
			/* Validate submission */
			if($_POST['username'] == "" || $_POST['subject'] == "" || $_POST['message_input'] == "")
			{
				$alert_type = 'error';
				$alert_message = __("Please make sure you fill in all fields.");
			}
			else if(!bp_core_get_userid($_POST['username']))
			{
				$alert_type = 'error';
				$alert_message = __("The username you gave was a member who doesn't exist, please check and make sure you entered it correctly.");
			}
			else 
			{
				/* Send message - add to DB */
				$this->send($_POST['username'], $_POST['subject'], $_POST['message_input']); die;
			}
		}
		else if(isset($_GET['mode']) && isset($_POST['send']) && $_GET['mode'] == "send_message")
		{
			
			if($_POST['subject'] == '')
			{
				$alert_type = 'error';
				$message = __("To save a draft you must at minimum enter a subject.");
			}
			else 
			{
				/* Save message - add to DB */
				$this->send($_POST['username'], $_POST['subject'], $_POST['message_input'], IS_DRAFT); die;
			}
		}
		?>
		
		<div class="wrap">
			<h2><?php _e("Write New Message") ?></h2>
			
			<?php if($alert_type != '') { ?>
				<?php if($alert_type == 'error') { $type = "error"; } else { $type = "updated"; } ?>
				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo $alert_message; ?></p>
				</div>
			<?php } ?>					
			
			<form action="admin.php?page=bp_messages.php&amp;mode=send_message" method="post" id="send_message_form">
			
				<fieldset id="usernamediv">
					<legend><?php _e("Send To Username") ?></legend>
					<div>
						<input type="text" name="username" id="username" value="<?php echo $username; ?>" style="width:50%" />
					</div>
				</fieldset>

				<fieldset id="subjectdiv">
					<legend><?php _e("Subject") ?></legend>
					<div>
						<input type="text" name="subject" id="subject" value="<?php echo $subject; ?>" />
					</div>
				</fieldset>

				<p>
					<fieldset id="messagediv">
						<legend><?php _e("Message") ?></legend>
						<div>
							<textarea name="message_input" id="message_input" rows="15" cols="40"><?php echo $message; ?></textarea>
						</div>
					</fieldset>
				</p>
				
				
				<p class="submit">
						<input type="submit" value="<?php _e("Save as Draft"); ?>" name="save_as_draft" id="save_as_draft" />
						<input type="submit" value="<?php _e("Send") ?> &raquo;" name="send" id="send" style="font-weight: bold" />
				</p>
				
			</form>
			<script type="text/javascript">
				document.getElementById("username").focus();
			</script>
			
		</div>
		<?php
	}
	

	/**************************************************************************
 	 inbox()
 	  
	 Displays the list of messages in the inbox as well as redirects to the
	 edit, delete and view functions.
 	 **************************************************************************/	
		
	function inbox($message = '', $type = 'error')
	{

		if(isset($_GET['mode']) && isset($_GET['id']) && $_GET['mode'] == "view")
		{
			if(bp_core_validate(bp_core_clean($_GET['id']))) {
				$this->view_message($_GET['id'], 'inbox'); die;
			}
		}
		else if(isset($_GET['mode']) && isset($_GET['id']) && $_GET['mode'] == "delete")
		{
			if(bp_core_validate(bp_core_clean($_GET['id']))) {
				$this->delete_message($_GET['id']); die;
			}
		}
		else if(isset($_GET['mode']) && isset($_POST['message_ids']) && $_GET['mode'] == "delete_bulk")
		{
			if(bp_core_validate(bp_core_clean($_POST['message_ids']))) {
				$this->delete_messages($_POST['message_ids']); die;
			}
		}
		else if(isset($_GET['mode']) && $_GET['mode'] == "send_response")
		{
			$this->validate_response($_GET['mid'], $_POST['is_forward'], $_POST['respond_forward_username'], $_POST['respond_reply_username'], $_POST['respond_subject'], $_POST['respond_message']); die;
		}
		?>

		<div class="wrap">
			<h2><?php _e('Inbox'); ?></h2>
			<form action="admin.php?page=messages_inbox&amp;mode=delete_bulk" method="post">
			
		<?php if($message != '') { ?>
			<?php if($type == 'error') { $type = "error"; } else { $type = "updated"; } ?>
			<div id="message" class="<?php echo $type; ?> fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>
			
			<table class="widefat">
				<thead>
					<tr>
						<th scope="col" width="1%"></th>
						<th scope="col" width="15%">From</th>
						<th scope="col">Subject</th>
						<th scope="col" width="21%">Date Recieved</th>
						<th scope="col" colspan="2" style="text-align:center;" width="15%">Action</th>
						<th scope="col" width="1%"><input type="checkbox" id="check_all" onclick="checkAll();" name="check_all" /></th>
					</tr>
				</thead>
				<tbody id="the-list">
		<?php	
		
		$messages = $this->get_messages('inbox');
		
		if(count($messages) > 0) 
		{
			$counter = 0;
			foreach($messages as $message)
			{
				if(!$message->is_read)
				{ 
					$is_read = '<img src="' . $this->imageBase .'email.gif" alt="New Message" />';
					$new = " unread";
				}
				else { $is_read = '<img src="' . $this->imageBase .'email_open.gif" alt="Older Message" />'; }
				if($counter % 2 == 0) $class = "alternate";
				
				echo '
					<tr class="' . $class . $new . '">
						<td>' . $is_read . '</td>
						<td>' . bp_core_get_username($message->sender_id) . '</td>
						<td>' . stripslashes($message->subject) . '</td>
						<td>' . bp_format_time($message->date_sent) . '</td>
						<td><a class="edit" href="admin.php?page=messages_inbox&amp;mode=view&amp;id=' . $message->id . '">View</a></td>
						<td><a class="delete" href="admin.php?page=messages_inbox&amp;mode=delete&amp;id=' . $message->id . '">Delete</a></td>
						<td><input type="checkbox" name="message_ids[]" value="' . $message->id . '" /></td>
					</tr>				
				';
				
				$counter++;
				unset($class);
				unset($new);
				unset($is_read);
			}
			

		}
		else {
			?>
				<tr class="alternate">
				<td colspan="7" style="text-align: center; padding: 15px 0;">
					You have no messages in your Inbox.
				</td>
				</tr>
			<?php
		}
	
		echo '
			</tbody>
			</table>
			<p class="submit">
				<input id="deletebookmarks" class="button" type="submit" onclick="return confirm(\'You are about to delete these messages permanently.\n[Cancel] to stop, [OK] to delete.\')" value="Delete Checked Messages &raquo;" name="deletebookmarks"/>
			</p>
			</form>	
		</div>';
	}
	

	/**************************************************************************
 	 sentbox()
 	  
	 Displays the list of messages in the sentbox as well as redirects to the
	 delete and view functions.
 	 **************************************************************************/	
	
	function sentbox($message = '', $type = 'error')
	{
		if($_GET['mode'] == "delete" && isset($_GET['id']))
		{
			if(bp_core_validate(bp_core_clean($_GET['id'])))
			{
				$this->delete_message($_GET['id'], 'sentbox'); die;
			}
		}
		else if($_GET['mode'] == "view" && isset($_GET['id']))
		{
			if(bp_core_validate(bp_core_clean($_GET['id']))) {
				$this->view_message($_GET['id'], 'sentbox'); die;
			}
		}
		else if(isset($_GET['mode']) && isset($_POST['message_ids']) && $_GET['mode'] == "delete_bulk")
		{
			if(bp_core_validate(bp_core_clean($_POST['message_ids']))) {
				$this->delete_messages($_POST['message_ids'], 'sentbox'); die;
			}
		}
		else if(isset($_GET['mode']) && $_GET['mode'] == "send_response")
		{
			$this->validate_response($_GET['mid'], $_POST['is_forward'], $_POST['respond_forward_username'], $_POST['respond_reply_username'], $_POST['respond_subject'], $_POST['respond_message']); die;
		}
		
		?>
			<div class="wrap">
				<h2><?php _e('Sentbox'); ?></h2>
				<form action="admin.php?page=messages_sentbox&amp;mode=delete_bulk" method="post">
		
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col" width="1%"></th>
							<th scope="col" width="15%">From</th>
							<th scope="col">Subject</th>
							<th scope="col" width="21%">Date Sent</th>
							<th scope="col" colspan="2" style="text-align:center;" width="15%">Action</th>
							<th scope="col" width="1%"><input type="checkbox" id="check_all" onclick="checkAll();" name="check_all" /></th>
						</tr>
					</thead>
					<tbody id="the-list">
					
				<?php if($message != '') { ?>
					<?php if($type == 'error') { $type = "error"; } else { $type = "updated"; } ?>
					<div id="message" class="<?php echo $type; ?> fade">
						<p><?php echo $message; ?></p>
					</div>
				<?php } ?>
			
			<?php	

			$messages = $this->get_messages('sentbox');

			if(count($messages) > 0) 
			{
				$counter = 0;
				foreach($messages as $message)
				{
					if($counter % 2 == 0) $class = "alternate";

					echo '
						<tr class="' . $class . '">
							<td><img src="' . $this->imageBase  . 'email_sent.gif" alt="Sent Message" /></td>
							<td>' . bp_core_get_username($message->sender_id) . '</td>
							<td>' . stripslashes($message->subject) . '</td>
							<td>' . bp_format_time($message->date_sent) . '</td>
							<td><a class="edit" href="admin.php?page=messages_sentbox&amp;mode=view&amp;id=' . $message->id . '">View</a></td>
							<td><a class="delete" href="admin.php?page=messages_sentbox&amp;mode=delete&amp;id=' . $message->id . '">Delete</a></td>
							<td><input type="checkbox" name="message_ids[]" value="' . $message->id . '" /></td>
						</tr>				
					';

					$counter++;
					unset($class);
					unset($new);
					unset($is_read);
				}

			}
			else {
				?>
					<tr class="alternate">
					<td colspan="7" style="text-align: center; padding: 15px 0;">
						You have no messages in your Sentbox.
					</td>
					</tr>
				<?php
			}

			echo '
				</tbody>
				</table>	
				<p class="submit">
					<input id="deletebookmarks" class="button" type="submit" onclick="return confirm(\'You are about to delete these messages permanently.\n[Cancel] to stop, [OK] to delete.\')" value="Delete Checked Messages &raquo;" name="deletebookmarks"/>
				</p>
				</form>	
			</div>';
	}
	

	/**************************************************************************
 	 drafts()
 	  
	 Displays the list of draft messages in the sentbox as well as redirects 
	 to the delete and view functions.
 	 **************************************************************************/	
	
	function drafts($message = '', $type = 'error')
	{
		if($_GET['mode'] == "delete" && isset($_GET['id']))
		{
			if(bp_core_validate(bp_core_clean($_GET['id'])))
			{
				$this->delete_message($_GET['id'], 'drafts'); die;
			}
		}
		else if($_GET['mode'] == "view" && isset($_GET['id']))
		{
			if(bp_core_validate(bp_core_clean($_GET['id']))) {
				$this->view_message($_GET['id'], 'drafts'); die;
			}
		}
		else if(isset($_GET['mode']) && isset($_POST['message_ids']) && $_GET['mode'] == "delete_bulk")
		{
			if(bp_core_validate(bp_core_clean($_POST['message_ids']))) {
				$this->delete_messages($_POST['message_ids'], 'drafts'); die;
			}
		}
		else if(isset($_GET['mode']) && $_GET['mode'] == "send_response")
		{
			$this->validate_response($_GET['mid'], $_POST['is_forward'], $_POST['respond_forward_username'], $_POST['respond_reply_username'], $_POST['respond_subject'], $_POST['respond_message']); die;
		}
		
			?>
			<div class="wrap">
				<h2><?php _e('Drafts'); ?></h2>
				<form action="admin.php?page=messages_drafts&amp;mode=delete_bulk" method="post">
		
				<table class="widefat">
					<thead>
						<tr>
							<th scope="col" width="1%"></th>
							<th scope="col" width="15%">From</th>
							<th scope="col">Subject</th>
							<th scope="col" width="21%">Date Created</th>
							<th scope="col" colspan="2" style="text-align:center;" width="15%">Action</th>
							<th scope="col" width="1%"><input type="checkbox" id="check_all" onclick="checkAll();" name="check_all" /></th>
						</tr>
					</thead>
					<tbody id="the-list">
					
				<?php if($message != '') { ?>
					<?php if($type == 'error') { $type = "error"; } else { $type = "updated"; } ?>
					<div id="message" class="<?php echo $type; ?> fade">
						<p><?php echo $message; ?></p>
					</div>
				<?php } ?>
			
			<?php	

			$messages = $this->get_messages('drafts');

			if(count($messages) > 0) 
			{
				$counter = 0;
				foreach($messages as $message)
				{
					if(!$message->is_read)
					{ 
						$is_read = "(*)";
						$new = " unread";
					}
					if($counter % 2 == 0) $class = "alternate";

					echo '
						<tr class="' . $class . '">
							<td><img src="' . $this->imageBase  . 'email_draft.gif" alt="Draft Message" /></td>
							<td>' . bp_core_get_username($message->sender_id) . '</td>
							<td>' . stripslashes($message->subject) . '</td>
							<td>' . bp_format_time($message->date_sent) . '</td>
							<td><a class="edit" href="admin.php?page=messages_drafts&amp;mode=edit&amp;id=' . $message->id . '">Edit</a></td>
							<td><a class="delete" href="admin.php?page=messages_drafts&amp;mode=delete&amp;id=' . $message->id . '">Delete</a></td>
							<td><input type="checkbox" name="message_ids[]" value="' . $message->id . '" /></td>
						</tr>				
					';

					$counter++;
					unset($class);
					unset($new);
					unset($is_read);
				}

			}
			else {
				?>
					<tr class="alternate">
					<td colspan="7" style="text-align: center; padding: 15px 0;">
						You have no draft messages.
					</td>
					</tr>
				<?php
			}

			echo '
				</tbody>
				</table>	
				<p class="submit">
					<input id="deletebookmarks" class="button" type="submit" onclick="return confirm(\'You are about to delete these messages permanently.\n[Cancel] to stop, [OK] to delete.\')" value="Delete Checked Messages &raquo;" name="deletebookmarks"/>
				</p>
				</form>	
			</div>';
	}
	
	
	/**************************************************************************
 	 get_messages()
 	  
	 Gets an array of message objects based on the current box and returns it.
 	 **************************************************************************/	

	function get_messages($box = 'inbox') 
	{
		if($box == "sentbox") {
			$sql = "SELECT * FROM " . $this->tableName . "
					WHERE folder_id = " . SENTBOX . "
					AND sender_id = " . $this->userdata->ID . "
					ORDER BY date_sent DESC";

		} else if($box == "drafts") {
			$sql = "SELECT * FROM " . $this->tableName . "
					WHERE is_draft = 1
					AND sender_id = " . $this->userdata->ID . "
					ORDER BY date_sent DESC";
		}
		else {
			$sql = "SELECT * FROM " . $this->tableName . "
					WHERE folder_id = " . INBOX . "
					AND recipient_id = " . $this->userdata->ID . "
					ORDER BY date_sent DESC";				
		}

		$messages = $this->wpdb->get_results($sql);
		
		return $messages;
	}
		
	
	/**************************************************************************
 	 delete_message()
 	  
	 Removes a message from the database.
 	 **************************************************************************/	

	function delete_message($message_id, $redirect = 'inbox') 
	{
		/** Check that user can access this message **/
		$sql = "SELECT id FROM " . $this->tableName . "
				WHERE id = " . $message_id . "
				AND (sender_id = " . $this->userdata->ID . " 
					 OR recipient_id = " . $this->userdata->ID . ")";
		
		if(!$this->wpdb->get_var($sql))
		{
			/** No access to this message **/
			unset($_GET['mode']);
			$message = __("That was not a valid message.");
			$this->callback($message, 'error', $redirect);
		}
		else
		{
			/** User has access to this message **/
			$sql = "DELETE FROM " . $this->tableName . "
					WHERE id = " . $message_id . "
					LIMIT 1";
			
			if(!$this->wpdb->query($sql))
			{
				unset($_GET['mode']);
				$message = __("There was a problem deleting that message. Please try again.");
				$this->callback($message, 'error', $redirect);
			}
			else
			{
				unset($_GET['mode']);
				$message = __("The message was deleted successfully!");
				$this->callback($message, 'success', $redirect);
			}
			
		}
	}


	/**************************************************************************
 	 delete_messages()

	 Removes multiple messages from the database.
 	 **************************************************************************/	

	function delete_messages($message_ids, $redirect = 'inbox') 
	{
		if(is_array($message_ids))
		{
			for($i=0; $i<count($message_ids); $i++)
			{
				/** Check that user can access this message **/
				$sql = "SELECT id FROM " . $this->tableName . "
						WHERE id = " . $message_ids[$i] . "
						AND (sender_id = " . $this->userdata->ID . " 
							 OR recipient_id = " . $this->userdata->ID . ")";

				if($this->wpdb->get_var($sql))
				{
					/** User has access to this message **/
					$sql = "DELETE FROM " . $this->tableName . "
							WHERE id = " . $message_ids[$i] . "
							LIMIT 1";

					if(!$this->wpdb->query($sql))
					{
						$errors = 1;
					}
				}
			}
			
			if($errors)
			{
				unset($_GET['mode']);
				$message = __("Some messages were not deleted, please try again.");
				$this->callback($message, 'error', $redirect); die;
			}
			
			unset($_GET['mode']);
			$message = __("Messages were deleted successfully!");
			$this->callback($message, 'success', $redirect); die;
		}
	}
	
	
	/**************************************************************************
 	 addto_sentbox()
 	  
	 Duplicates a sent message into the senders sentbox.
 	 **************************************************************************/	

	function addto_sentbox($username, $subject, $message, $date_sent)
	{
		$sql = "INSERT INTO " . $this->tableName . " (
					sender_id,
					recipient_id,
					subject,
					message,
					folder_id,
					date_sent
				)
				VALUES (
					" . $this->userdata->ID . ",
					" . bp_core_get_userid($username) . ",
					'" . bp_core_clean($subject) . "',
					'" . bp_core_clean($message) . "',
					" . SENTBOX . ",
					" . $date_sent . "								
				)";
				
		$this->wpdb->query($sql);
	}


	/**************************************************************************
 	 send()
 	  
	 Insert a message into the database.
 	 **************************************************************************/		
	
	function send($username, $subject, $message, $is_draft = 0) 
	{
		$date_sent = time();
		
		$sql = "INSERT INTO " . $this->tableName . " (
					sender_id,
					recipient_id,
					subject,
					message,
					is_draft,
					date_sent
				)
				VALUES (
					" . $this->userdata->ID . ",
					" . bp_core_get_userid($username) . ",
					'" . bp_core_clean($subject) . "',
					'" . bp_core_clean($message) . "',
					" . bp_core_clean($is_draft) . ",
					" . $date_sent . "								
				)";

		if($this->wpdb->query($sql))
		{
			if($is_draft)
			{
				unset($_POST['save_as_draft']);
				$this->write_new('', '', '', 'success', __("Your message has been saved."));		
			}
			else
			{
				unset($_POST['send']);
				$this->addto_sentbox($username, $subject, $message, $date_sent);
				$this->write_new('', '', '', 'success', __("Your message was sent successfully!"));	
			}	
		}
		else
		{
			if($is_draft)
			{
				unset($_POST['save_as_draft']);
				$this->write_new($username, $subject, $message, 'error', __("There was a problem saving your message, please try again."));	
			}
			else
			{
				unset($_POST['send']);
				$this->write_new($username, $subject, $message, 'error', __("There was a problem sending your message, please try again."));		
			}
		}
	}


	/**************************************************************************
 	 validate_response()
 	  
	 Check to see if a reply or forward of a message is valid. Used in Inbox
	 Sentbox and Drafts, so needs to be extracted into its own function.
 	 **************************************************************************/		
		
	function validate_response($mid, $response_type, $forward_username, $reply_username, $subject, $message)
	{
		if($response_type == "1" ) { $response_type = "forward"; } else { $response_type = "reply"; }
		$reply = array("subject" => $subject, "message" => $message, "type" => $response_type);
		
		if($response_type == "forward")
		{
			if($subject == "" || $forward_username == "")
			{
				$this->view_message(bp_core_clean($mid), 'inbox', $response_type, $reply, __('Please fill in all fields')); die;
			}
			else if(!bp_core_get_userid($forward_username)) {
				$this->view_message(bp_core_clean($mid), 'inbox', $response_type, $reply, __('That username was invalid')); die;	
			}
			else {
				//echo "sending"; die;
				$this->send($forward_username, $subject, $message); die;
			}
		}
		else 
		{
			if($subject == "" || $reply_username == "")
			{

				$this->view_message(bp_core_clean($mid), 'inbox', $response_type, $reply, __('Please fill in all fields')); die;
			}
			else if(!bp_core_get_userid($reply_username)) {
				$this->view_message(bp_core_clean($mid), 'inbox', $response_type, $reply, __('That username was invalid')); die;	
			}
			else {
				//echo "sending"; die;
				$this->send($reply_username, $subject, $message); die;
			}
			
		}
	}
	
	
		
	/**************************************************************************
 	 mark_as_read()
 	  
	 Marks a message as read, once it's been viewed.
 	 **************************************************************************/		
	
	function mark_as_read($mid)
	{
		$sql = "UPDATE " . $this->tableName . "
				SET is_read = 1 
				WHERE id = " . $mid;
				
		$this->wpdb->query($sql);
	}
	
		
	/**************************************************************************
 	 get_message()
 	  
	 Select and return a message.
 	 **************************************************************************/	
	
	function get_message($mid)
	{
		$sql = "SELECT * FROM " . $this->tableName . "
				WHERE id = " . $mid;
		
		return $this->wpdb->get_row($sql);
	}
	
	
	/**************************************************************************
 	 view_message()
 	  
	 Selects and displays a message and handles reply/forward form.
 	 **************************************************************************/		
	
	function view_message($mid, $redirect_to, $show_respond = false, $callback = null, $error_message = null)
	{
		
		/* Check if user can view. */
		$sql = "SELECT * FROM " . $this->tableName . "
				WHERE id = " . $mid . "
				AND (recipient_id = " . $this->userdata->ID . "
				OR sender_id = " . $this->userdata->ID . ") 
				LIMIT 1";
		
		if(!$message = $this->wpdb->get_row($sql))
		{
			unset($_GET['mode']);
			$this->callback(__("There was a problem viewing that message, please try again."), 'error', $redirect_to);
		}
		else {
			
			/* All good, now show the message */
			?>
			
			<div class="wrap">
				<h2 id="message_subject"><?php echo "\"" . $message->subject . "\""; ?></h2>
			
				<ul>
					<li><?php _e("From") ?>: <?php echo bp_core_get_username($message->sender_id) ?></li>
					<li><?php _e("Subject") ?>: <strong><?php echo $message->subject ?></strong></li>
					<li><?php _e("Date Sent") ?>: <?php echo bp_format_time($message->date_sent) ?></li>
					<li><?php _e("To") ?>: <?php echo bp_core_get_username($message->recipient_id) ?></li>
				</ul>
				<hr />
				
				<div id="message_view">
					<?php echo $message->message; ?>
				</div>
				<hr />
				<p>
					<a href="javascript:sendReply();">Reply</a>
					<a href="javascript:forwardMessage();">Forward</a>
				</p>
				
				<?php if($error_message != '') { ?>
					<div id="message" class="error fade">
						<p><?php echo $error_message; ?></p>
					</div>
				<?php } ?>

				<div id="respond"<?php if(!$show_respond) { ?>style="display: none;"<?php } ?>>
					<form action="admin.php?page=messages_<?php echo $redirect_to ?>&amp;mode=send_response&amp;mid=<?php echo $message->id; ?>" method="post" id="send_message_form">
						
						<input type="hidden" name="respond_reply_username" id="respond_reply_username" value="<?php echo bp_core_get_username($message->sender_id) ?>" />

						<fieldset id="recipientdiv"<?php if($show_respond != "forward") { ?>style="display:none;"<?php } ?>>
							<legend><?php _e("Forward to Username:") ?></legend>
							<div>
								<input type="text" name="respond_forward_username" id="respond_forward_username" style="width: 50%;" value="<?php if(isset($callback["forward_username"])) { echo $callback["forward_username"]; } ?>" />
							</div>
						</fieldset>

						<fieldset id="subjectdiv">
							<legend><?php _e("Subject") ?></legend>
							<div>
								<input type="text" name="respond_subject" id="respond_subject" value="<?php if(isset($callback["subject"])) { echo $callback["subject"]; } else { echo $subject; } ?>" />
							</div>
						</fieldset>

						<p>
							<fieldset id="messagediv">
								<legend><?php _e("Message") ?></legend>
								<div>
									<textarea name="respond_message" id="respond_message" rows="15" cols="40"><?php if(isset($callback["message"])) { echo $callback["message"]; } else { ?><br /><br />-------- Original Message -------------------------------------<br /><br /><?php echo $message->message; ?></fieldset><?php } ?></textarea>
								</div>
							</fieldset>
						</p>


						<p class="submit">
								<input type="submit" value="<?php _e("Save as Draft"); ?>" name="save_as_draft" id="save_as_draft" />
								<input type="submit" value="<?php _e("Send") ?> &raquo;" name="send" id="send" style="font-weight: bold" />
						</p>
						
						<input type="hidden" name="is_forward" id="is_forward" value="<?php if($callback["type"] == "forward") { ?>1<?php }else{ ?>0<?php } ?>" />
					</form>
				</div>
				
				
			</div>

			<?php
			
			/** Now mark this message as read. **/
			$this->mark_as_read($message->id);
			
		}
	}
	
	
	/**************************************************************************
 	 get_inbox_count()
 	  
	 Counts unread messages in Inbox, to display unread count on tab.
 	 **************************************************************************/	
	
	function get_inbox_count() 
	{
		$sql = "SELECT count(id) FROM " . $this->tableName . "
				WHERE recipient_id = " . $this->userdata->ID . "
				AND folder_id = 1  
				AND is_read = 0";

		$count = $this->wpdb->get_var($sql);

		if($count > 0) 
		{
			return " <strong>(" . $count . ")</strong>";
		} 
		
		return false;
	}

	
	/**************************************************************************
 	 callback()
 	  
	 Callback to specfic functions - this keeps correct tabs selected.
 	 **************************************************************************/	
	
	function callback($message, $type = 'error', $callback) 
	{

		switch($callback)
		{
			case "inbox":
				$this->inbox($message, $type);
			break;
			case "sentbox":
				$this->sentbox($message, $type);
			break;
			case "drafts":
				$this->drafts($message, $type);
			break;
			default:
				$this->inbox($message, $type);	
		}
	}
	
	/**************************************************************************
 	 add_js()
 	  
	 Inserts the TinyMCE Js that's needed for the WYSIWYG message editor.
 	 **************************************************************************/	
	
	function add_js()
	{
		if(isset($_GET['page']))
		{
			?>
			<script type="text/javascript">
				function checkAll() {
					var checkboxes = document.getElementsByTagName("input");
					for(var i=0; i<checkboxes.length; i++) {
						if(checkboxes[i].type == "checkbox") {
							if(document.getElementById("check_all").checked == "") {
								checkboxes[i].checked = "";
							}
							else {
								checkboxes[i].checked = "checked";
							}
						}
					}
				}
				
				function sendReply() {
					var subject = document.getElementById("message_subject").innerHTML;
					
					document.getElementById("respond").style.display = "block";
					document.getElementById("recipientdiv").style.display = "none";
					document.getElementById("respond_subject").value = "Re: " + subject.substr(1).replace('"', '');
					document.getElementById("respond_forward_username").value = "";
					document.getElementById("is_forward").value = "0";
				}
				
				function forwardMessage() {
					var subject = document.getElementById("message_subject").innerHTML;
					
					document.getElementById("respond").style.display = "block";
					document.getElementById("recipientdiv").style.display = "block";
					document.getElementById("respond_subject").value = "Fwd: " + subject.substr(1).replace('"', '');
					document.getElementById("respond_forward_username").value = "";
					document.getElementById("is_forward").value = "1";
				}
				
			</script>
			<script type="text/javascript" src="../wp-includes/js/tinymce/tiny_mce.js"></script>
			<script type="text/javascript">
				<!--
				tinyMCE.init({
				theme : "advanced",
				theme : "advanced",
				language : "en",
				theme_advanced_toolbar_location : "top",
				theme_advanced_toolbar_align : "left",
				theme_advanced_path_location : "bottom",
				theme_advanced_resizing : true,
				theme_advanced_resize_horizontal : false,
				theme_advanced_buttons1 : "bold,italic,strikethrough,separator,bullist,numlist,outdent,indent,separator,justifyleft,justifycenter,justifyright,separator,link,unlink,spellchecker,forecolor,fontsizeselect",
				theme_advanced_buttons2 : "",
				theme_advanced_buttons3 : "",
				content_css : "<?php echo get_option('siteurl') . '/wp-includes/js/tinymce/plugins/wordpress/wordpress.css'; ?>",
				mode : "exact",
				elements : "message_input, respond_message",
				width : "99%",
				height : "200"
				});
				-->
			</script>
			<?php
		}
	}
	

	/**************************************************************************
 	 add_css()
 	  
	 Inserts the CSS needed to style the messages pages.
 	 **************************************************************************/	
	
	function add_css()
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

} // End Class



?>