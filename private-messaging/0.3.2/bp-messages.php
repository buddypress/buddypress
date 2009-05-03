<?php

define ( 'BP_MESSAGES_VERSION', '0.3' );

$bp_messages_table_name 			= $wpdb->base_prefix . 'bp_messages';
$bp_messages_table_name_threads 	= $bp_messages_table_name . '_threads';
$bp_messages_table_name_messages	= $bp_messages_table_name . '_messages';
$bp_messages_table_name_recipients	= $bp_messages_table_name . '_recipients';
$bp_messages_table_name_notices		= $bp_messages_table_name . '_notices';
$bp_messages_image_base 			= get_option('siteurl') . '/wp-content/mu-plugins/bp-messages/images';
$bp_messages_slug 					= 'messages';

include_once( 'bp-messages/bp-messages-classes.php' );
include_once( 'bp-messages/bp-messages-ajax.php' );
include_once( 'bp-messages/bp-messages-cssjs.php' );
include_once( 'bp-messages/bp-messages-admin.php' );
include_once( 'bp-messages/bp-messages-templatetags.php' );

/**************************************************************************
 messages_install()
 
 Sets up the database tables ready for use on a site installation.
 **************************************************************************/

function messages_install( $version ) {
	global $wpdb;
	global $bp_messages_table_name_threads;
	global $bp_messages_table_name_messages;
	global $bp_messages_table_name_recipients;
	global $bp_messages_table_name_notices;
	
	$sql[] = "CREATE TABLE ". $bp_messages_table_name_threads ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
		  		message_ids varchar(150) NOT NULL,
				sender_ids varchar(150) NOT NULL,
		  		first_post_date datetime NOT NULL,
		  		last_post_date datetime NOT NULL,
		  		last_message_id int(11) NOT NULL,
				last_sender_id int(11) NOT NULL,
		  		PRIMARY KEY id (id)
		 	   );";
	
	$sql[] = "CREATE TABLE ". $bp_messages_table_name_recipients ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
		  		user_id int(11) NOT NULL,
		  		thread_id int(11) NOT NULL,
				sender_only tinyint(1) NOT NULL DEFAULT '0',
		  		unread_count int(10) NOT NULL DEFAULT '0',
		  		PRIMARY KEY id (id)
		 	   );";

	$sql[] = "CREATE TABLE ". $bp_messages_table_name_messages ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
		  		sender_id int(11) NOT NULL,
		  		subject varchar(200) NOT NULL,
		  		message longtext NOT NULL,
		  		date_sent datetime NOT NULL,
				message_order int(10) NOT NULL,
				sender_is_group tinyint(1) NOT NULL DEFAULT '0',
		  		PRIMARY KEY id (id)
		 	   );";
	
	$sql[] = "CREATE TABLE ". $bp_messages_table_name_notices ." (
		  		id int(11) NOT NULL AUTO_INCREMENT,
		  		subject varchar(200) NOT NULL,
		  		message longtext NOT NULL,
		  		date_sent datetime NOT NULL,
				is_active tinyint(1) NOT NULL DEFAULT '0',
		  		PRIMARY KEY id (id)
		 	   );";
	
	/* DELETE PREVIOUS TABLES (TEMP) */
	//$sql[] = "DROP TABLE wp_bp_messages";
	//$sql[] = "DROP TABLE wp_bp_messages_deleted";
	
	require_once( ABSPATH . 'wp-admin/upgrade-functions.php' );
	dbDelta($sql);
	
	add_site_option( 'bp-messages-version', $version );
}

/**************************************************************************
 messages_add_admin_menu()
 
 Creates the administration interface menus and checks to see if the DB
 tables are set up.
 **************************************************************************/

function messages_add_admin_menu() {	
	global $wpdb, $bp_messages_table_name, $bp_messages, $userdata;

	if ( $wpdb->blogid == $userdata->primary_blog ) {	
		if ( $inbox_count = BP_Messages_Thread::get_inbox_count() ) {
			$count_indicator = ' <span id="awaiting-mod" class="count-1"><span class="message-count">' . $inbox_count . '</span></span>';
		}
		
		add_menu_page    ( __('Messages'), sprintf( __('Messages%s'), $count_indicator ), 1, basename(__FILE__), "messages_inbox" );
		add_submenu_page ( basename(__FILE__), __('Messages &rsaquo; Inbox'), __('Inbox'), 1, basename(__FILE__), "messages_inbox" );	
		add_submenu_page ( basename(__FILE__), __('Messages &rsaquo; Sent Messages'), __('Sent Messages'), 1, "messages_sentbox", "messages_sentbox" );	
		add_submenu_page ( basename(__FILE__), __('Messages &rsaquo; Compose'), __('Compose'), 1, "messages_write_new", "messages_write_new" );

		// Add the administration tab under the "Site Admin" tab for site administrators
		add_submenu_page ( 'wpmu-admin.php', __('Messages'), __('Messages'), 1, basename(__FILE__), "messages_settings" );
	}
	
	/* Need to check db tables exist, activate hook no-worky in mu-plugins folder. */
	if ( ( $wpdb->get_var( "show tables like '%" . $bp_messages_table_name . "%'" ) == false ) || ( get_site_option('bp-messages-version') < BP_MESSAGES_VERSION ) )
		messages_install(BP_MESSAGES_VERSION);
}
add_action( 'admin_menu', 'messages_add_admin_menu' );

/**************************************************************************
 messages_setup_nav()
 
 Set up front end navigation.
 **************************************************************************/

function messages_setup_nav() {
	global $loggedin_userid, $loggedin_domain;
	global $current_userid, $current_domain;
	global $bp_nav, $bp_options_nav, $bp_users_nav;
	global $bp_messages_slug, $bp_options_avatar, $bp_options_title;
	global $current_component;

	$bp_nav[2] = array(
		'id'	=> $bp_messages_slug,
		'name'  => 'Messages', 
		'link'  => $loggedin_domain . $bp_messages_slug . '/'
	);
	
	$inbox_count = BP_Messages_Thread::get_inbox_count();
	$inbox_display = ( $inbox_count ) ? ' style="display:inline;"' : ' style="display:none;"';
	$count_indicator = '&nbsp; <span' . $inbox_display . ' class="unread-count inbox-count">' . BP_Messages_Thread::get_inbox_count() . '</span>';

	if ( $current_component == $bp_messages_slug ) {
		if ( bp_is_home() ) {
			$bp_options_title = __('My Messages');
			$bp_options_nav[$bp_messages_slug] = array(
				'inbox'	   => array( 
					'name' => __('Inbox') . $count_indicator,
					'link' => $loggedin_domain . $bp_messages_slug . '/' ),
				'sentbox'  => array(
					'name' => __('Sent Messages'),
					'link' => $loggedin_domain . $bp_messages_slug . '/sentbox' ),
				'compose' => array( 
					'name' => __('Compose'),
					'link' => $loggedin_domain . $bp_messages_slug . '/compose' )
			);
			
			if ( is_site_admin() ) {
				$bp_options_nav[$bp_messages_slug]['notices'] = array(
					'name' => __('Sent Notices'),
					'link' => $loggedin_domain . $bp_messages_slug . '/notices'
				);
			}
			
		} else {
			$bp_options_avatar = xprofile_get_avatar( $current_userid, 1 );
			$bp_options_title = bp_user_fullname( $current_userid, false ); 
		}
	}
}
add_action( 'wp', 'messages_setup_nav' );


/**************************************************************************
 messages_catch_action()
 
 Catch actions via pretty urls.
 **************************************************************************/

function messages_catch_action() {
	global $bp_messages_slug, $current_component, $current_blog;
	global $loggedin_userid, $current_userid, $current_action;
	global $bp_options_nav, $action_variables, $thread_id;
	global $message, $type;

	if ( $current_component == $bp_messages_slug && $current_blog->blog_id > 1 && $loggedin_userid == $current_userid ) {
		if ( $current_action == '' )
			$current_action = 'inbox';
		
		if ( $current_action == 'inbox' ) {
			bp_catch_uri( 'messages/index' );
		} else if ( $current_action == 'sentbox' ) {
			bp_catch_uri( 'messages/sentbox' );
		} else if ( $current_action == 'compose' ) {
			bp_catch_uri( 'messages/compose' );
		} else if ( $current_action == 'view' && !empty($action_variables) ) {
			$thread_id = $action_variables[0];
		
			if ( !$thread_id || !is_numeric($thread_id) || !BP_Messages_Thread::check_access($thread_id) ) {
				$current_action = 'inbox';
				bp_catch_uri( 'messages/index' );
			} else {
				$bp_options_nav[$bp_messages_slug]['view'] = array(
					'name' => __('From: ' . BP_Messages_Thread::get_last_sender($thread_id)),
					'link' => $loggedin_domain . $bp_messages_slug . '/'			
				);
			
				bp_catch_uri( 'messages/view' );
			}
		} else if ( $current_action == 'delete' && !empty($action_variables) ) {
			$thread_id = $action_variables[0];

			if ( !$thread_id || !is_numeric($thread_id) || !BP_Messages_Thread::check_access($thread_id) ) {
				$current_action = 'inbox';
				bp_catch_uri( 'messages/index' );
			} else {
				// delete message
				if ( !BP_Messages_Thread::delete($thread_id) ) {
					$message = __('There was an error deleting that message.');
					add_action( 'template_notices', 'bp_render_notice' );
					
					$current_action = 'inbox';
					bp_catch_uri( 'messages/index' );
				} else {
					$message = __('Message deleted.');
					$type = 'success';
					add_action( 'template_notices', 'bp_render_notice' );
					
					$current_action = 'inbox';
					bp_catch_uri( 'messages/index' );
				}
			}
		} else if ( $current_action == 'bulk-delete' ) {
			$thread_ids = $_POST['thread_ids'];
			
			if ( !$thread_ids || !BP_Messages_Thread::check_access($thread_ids) ) {
				$current_action = 'inbox';
				bp_catch_uri( 'messages/index' );				
			} else {
				if ( !BP_Messages_Thread::delete( explode(',', $thread_ids ) ) ) {
					$message = __('There was an error deleting messages.');
					add_action( 'template_notices', 'bp_render_notice' );
					
					$current_action = 'inbox';
					bp_catch_uri( 'messages/index' );
				} else {
					$message = __('Messages deleted.');
					$type = 'success';
					add_action( 'template_notices', 'bp_render_notice' );
					
					$current_action = 'inbox';
					bp_catch_uri( 'messages/index' );
				}
			}
		} else if ( $current_action == 'notices' && is_site_admin() ) {
			if ( isset($action_variables) ) {
				$notice_id = $action_variables[1];

				if ( !$notice_id || !is_numeric($notice_id) ) {
					$current_action = 'notices';
					bp_catch_uri( 'messages/notices' );
				} else {
					$notice = new BP_Messages_Notice($notice_id);
					
					if ( $action_variables[0] == 'deactivate' ) {
						if ( !$notice->deactivate() ) {
							$message = __('There was a problem deactivating that notice.');	
						} else {
							$message = __('Notice deactivated.');
							$type = 'success';
						}
					} else if ( $action_variables[0] == 'activate' ) {
						if ( !$notice->activate() ) {
							$message = __('There was a problem activating that notice.');
						} else {
							$message = __('Notice activated.');
							$type = 'success';
						}
					} else if ( $action_variables[0] == 'delete' ) {
						if ( !$notice->delete() ) {
							$message = __('There was a problem deleting that notice.');
						} else {
							$message = __('Notice deleted.');
							$type = 'success';
						}
					}
				}
			}
			add_action( 'template_notices', 'bp_render_notice' );
			bp_catch_uri( 'messages/notices' );
		} else {
			$current_action = 'inbox';
			bp_catch_uri( 'messages/index' );
		}
	}
}
add_action( 'wp', 'messages_catch_action' );

/**************************************************************************
 messages_template()
 
 Set up template tags for use in templates.
 **************************************************************************/

function messages_template() {
	global $messages_template, $loggedin_userid;
	global $current_component, $bp_messages_slug;
	global $current_action, $loggedin_domain;
	
	if ( $current_component == $bp_messages_slug ) {
		if ( $current_action == 'inbox' || $current_action == 'sentbox' || ( $current_action == 'notices' && is_site_admin() ) )
			$messages_template = new BP_Messages_Template( $loggedin_userid, $current_action );
	}
	
}
add_action( 'wp_head', 'messages_template' );


/**************************************************************************
 messages_write_new()
 
 Handle and display the write new messages screen.
 **************************************************************************/

function messages_write_new( $username = '', $subject = '', $content = '', $type = '', $message = '' ) { ?>
	<?php
	global $messages_write_new_action;
	
	if ( $messages_write_new_action == '' )
		$messages_write_new_action = 'admin.php?page=bp-messages.php&amp;mode=send';
	?>
	
	<div class="wrap">
		<h2><?php _e('Compose Message') ?></h2>
		
		<?php
			if ( $message != '' ) {
				$type = ( $type == 'error' ) ? 'error' : 'updated';
		?>
			<div id="message" class="<?php echo $type; ?> fade">
				<p><?php echo $message; ?></p>
			</div>
		<?php } ?>
						
		<form action="<?php echo $messages_write_new_action ?>" method="post" id="send_message_form">
		<div id="poststuff">
			<p>			
			<div id="titlediv">
				<h3><?php _e("Send To") ?> <small>(Use username - autocomplete coming soon)</small></h3>
				<div id="titlewrap">
					<input type="text" name="send_to" id="send_to" value="<?php echo $username; ?>" />
					<?php if ( is_site_admin() ) : ?><br /><input type="checkbox" id="send-notice" name="send-notice" value="1" /> This is a notice to all users.<?php endif; ?>
				</div>
			</div>
			</p>

			<p>
			<div id="titlediv">
				<h3><?php _e("Subject") ?></h3>
				<div id="titlewrap">
					<input type="text" name="subject" id="subject" value="<?php echo $subject; ?>" />
				</div>
			</div>
			</p>
			
			<p>
				<div id="postdivrich" class="postarea">
					<h3><?php _e("Message") ?></h3>
					<div id="editorcontainer">
						<textarea name="content" id="message_content" rows="15" cols="40"><?php echo $content; ?></textarea>
					</div>
				</div>
			</p>
			
			<p class="submit">
					<input type="submit" value="<?php _e("Send") ?> &raquo;" name="send" id="send" style="font-weight: bold" />
			</p>
		</div>
		</form>
		<script type="text/javascript">
			document.getElementById("send_to").focus();
		</script>
		
	</div>
	<?php
}

function messages_inbox() {
	messages_box( 'inbox', __('Inbox') );
}

function messages_sentbox() {
	messages_box( 'sentbox', __('Sent Messages') );
}


/**************************************************************************
 messages_box()
  
 Handles and displays the messages in a particular box for the current user.
 **************************************************************************/

function messages_box( $box = 'inbox', $display_name = 'Inbox', $message = '', $type = '' ) {
	global $bp_messages_image_base, $userdata;
	
	if ( isset($_GET['mode']) && isset($_GET['thread_id']) && $_GET['mode'] == 'view' ) {
		messages_view_thread( $_GET['thread_id'], 'inbox' );
	} else if ( isset($_GET['mode']) && isset($_GET['thread_id']) && $_GET['mode'] == 'delete' ) {
		messages_delete_thread( $_GET['thread_id'], $box, $display_name );
	} else if ( isset($_GET['mode']) && isset($_POST['thread_ids']) && $_GET['mode'] == 'delete_bulk' ) {
		messages_delete_thread( $_POST['thread_ids'], $box, $display_name );
	} else if ( isset($_GET['mode']) && $_GET['mode'] == 'send' ) {
		messages_send_message( $_POST['send_to'], $_POST['subject'], $_POST['content'], $_POST['thread_id'] );
	} else {
	?>
	
		<div class="wrap">
			<h2><?php echo $display_name ?></h2>
			<form action="admin.php?page=bp-messages.php&amp;mode=delete_bulk" method="post">

			<?php
				if ( $message != '' ) {
					$type = ( $type == 'error' ) ? 'error' : 'updated';
			?>
				<div id="message" class="<?php echo $type; ?> fade">
					<p><?php echo $message; ?></p>
				</div>
			<?php } ?>
			
			<?php if ( $box == 'inbox' ) { ?>
				<div class="messages-options">	
					<?php bp_messages_options() ?>
				</div>
				
				<?php bp_message_get_notices(); ?>
			<?php } ?>
	
			<table class="widefat" id="message-threads" style="margin-top: 10px;">
				<tbody id="the-list">
		<?php
		$threads = BP_Messages_Thread::get_current_threads_for_user( $userdata->ID, $box );
		
		if ( $threads ) {
			$counter = 0;
			foreach ( $threads as $thread ) {
				if ( $thread->unread_count ) { 
					$is_read = '<img src="' . $bp_messages_image_base .'/email.gif" alt="New Message" /><a href="admin.php?page=bp-messages.php&amp;mode=view&amp;thread_id=' . $thread->thread_id . '"><span id="awaiting-mod" class="count-1"><span class="message-count">' . $thread->unread_count . '</span></span></a>';
					$new = " unread";
				} else { 
					$is_read = '<img src="' . $bp_messages_image_base .'/email_open.gif" alt="Older Message" />'; 
					$new = " read";
				}
				
				if ( $counter % 2 == 0 ) 
					$class = "alternate";
				?>
					<tr class="<?php echo $class . $new ?>" id="m-<?php echo $message->id ?>">
						<td class="is-read" width="1%"><?php echo $is_read ?></td>
						<td class="avatar" width="1%">
							<?php if ( function_exists('xprofile_get_avatar') )
									echo xprofile_get_avatar($thread->last_sender_id, 1);
							?>
						</td>
						<td class="sender-details" width="20%">
							<?php if ( $box == 'sentbox') { ?>
								<h3>To: <?php echo BP_Messages_Thread::get_recipient_links($thread->recipients); ?></h3>
							<?php } else { ?>
								<h3>From: <?php echo bp_core_get_userlink($thread->last_sender_id) ?></h3>
							<?php } ?>
							<?php echo bp_format_time(strtotime($thread->last_post_date)) ?>
						</td>
						<td class="message-details" width="40%">
							<h4><a href="admin.php?page=bp-messages.php&amp;mode=view&amp;thread_id=<?php echo $thread->thread_id ?>"><?php echo stripslashes($thread->last_message_subject) ?></a></h4>
							<?php echo bp_create_excerpt($thread->last_message_message, 20); ?>
						</td>
						<td width="10%"><a href="admin.php?page=bp-messages.php&amp;mode=delete&amp;thread_id=<?php echo $thread->thread_id ?>">Delete</a> <input type="checkbox" name="message_ids[]" value="<?php echo $thread->thread_id ?>" /></td>
					</tr>
				<?php
	
				$counter++;
				unset($class);
				unset($new);
				unset($is_read);
			}
			
			echo '
				</tbody>
				</table>
				<p class="submit">
					<input id="deletebookmarks" class="button" type="submit" onclick="return confirm(\'You are about to delete these messages permanently.\n[Cancel] to stop, [OK] to delete.\')" value="Delete Checked Messages &raquo;" name="deletebookmarks"/>
				</p>
				</form>	
			</div>';
			
		} else {
			?>
				<tr class="alternate">
				<td colspan="7" style="text-align: center; padding: 15px 0;">
					<?php _e('You have no messages in your'); echo ' ' . $display_name . '.'; ?>
				</td>
				</tr>
			<?php
		}
		?>
			</tbody>
			</table>
			</form>	
		</div>
		<?php
	}
}

/**************************************************************************
 messages_send_message()
  
 Send a message.
 **************************************************************************/

function messages_send_message( $recipients, $subject, $content, $thread_id, $from_ajax = false, $from_template = false, $is_reply = false ) {
	global $userdata;
	global $messages_write_new_action;
	global $pmessage;
	global $message, $type;
	global $loggedin_domain, $bp_messages_slug;

	if ( isset( $_POST['send-notice'] ) ) {
		messages_send_notice( $subject, $content, $from_template );
	} else {
		if ( $recipients == '' ) {
			if ( !$from_ajax ) {
				messages_write_new( '', $subject, $content, 'error', __('Please enter at least one valid user to send this message to.'), $messages_write_new_action );
			} else {
				return array('status' => 0, 'message' => __('There was an error sending the reply, please try again.'));
			}
		} else if ( $subject == '' || $content == '' ) {
			if ( !$from_ajax ) {
				messages_write_new( $to_user, $subject, $content, 'error', __('Please make sure you fill in all the fields.'), $messages_write_new_action );
			} else {
				return array('status' => 0, 'message' => __('Please make sure you have typed a message before sending a reply.'));
			}
		} else {
			$pmessage = new BP_Messages_Message;

			$pmessage->sender_id = $userdata->ID;
			$pmessage->subject = $subject;
			$pmessage->message = $content;
			$pmessage->thread_id = $thread_id;
			$pmessage->date_sent = time();
			$pmessage->message_order = 0; // TODO
			$pmessage->sender_is_group = 0;
			
			if ( $is_reply ) {
				$thread = new BP_Messages_Thread($thread_id);
				$pmessage->recipients = $thread->get_recipients();
			} else {
				$pmessage->recipients = BP_Messages_Message::get_recipient_ids( explode( ',', $recipients ) );
			}
			
			unset($_GET['mode']);

			if ( !is_null( $pmessage->recipients ) ) {
				if ( !$pmessage->send() ) {
					$message = __('Message could not be sent, please try again.');
					$type = 'error';
			
					if ( $from_ajax ) {
						return array('status' => 0, 'message' => $message);
					} else if ( $from_template ) {
						unset($_POST['send_to']);
						bp_render_notice();
						messages_write_new();
					} else {
						messages_box( 'inbox', __('Inbox'), $message, $type );	
					}
				} else {
					$message = __('Message sent successfully!') . ' <a href="' . $loggedin_domain . $bp_messages_slug . '/view/' . $pmessage->thread_id . '">' . __('View Message') . '</a> &raquo;';
					$type = 'success';
			
					if ( $from_ajax ) {
						return array('status' => 1, 'message' => $message, 'reply' => $pmessage);
					} else if ( $from_template ) {
						unset($_POST['send_to']);
						bp_render_notice();
						messages_write_new();
					} else {
						messages_box( 'inbox', __('Inbox'), $message, $type );
					}
				}
			} else {
				unset($_POST['send_to']);
				unset($_POST['send-notice']);
			
				$message = __('Message could not be sent, please try again.');
				$type = 'error';
			
				if ( $from_ajax ) {
					return array('status' => 0, 'message' => $message);
				} else if ( $from_template ) {
					bp_render_notice();
					messages_write_new();
				} else {
					messages_box( 'inbox', __('Inbox'), $message, $type );	
				}
			}
		}
	}
}

/**************************************************************************
 messages_send_notice()
  
 Handles the sending of notices by an administrator
 **************************************************************************/

function messages_send_notice( $subject, $message, $from_template ) {
	if ( !is_site_admin() || $subject == '' || $message == '' ) {
		unset($_POST['send_to']);
		unset($_POST['send-notice']);
		
		$message = __('Notice could not be sent, please try again.');
		$type = 'error';
	
		if ( $from_template ) {
			bp_render_notice();
			messages_write_new();
		} else {
			messages_box( 'inbox', __('Inbox'), $message, $type );	
		}
	} else {
		// Has access to send notices, lets do it.
		$notice = new BP_Messages_Notice;
		$notice->subject = $subject;
		$notice->message = $message;
		$notice->date_sent = time();
		$notice->is_active = 1;
		$notice->save(); // send it.
	}
		
}

/**************************************************************************
 messages_delete_thread()
  
 Handles the deletion of a single or multiple threads.
 **************************************************************************/

function messages_delete_thread( $thread_ids, $box, $display_name ) {
	$type = 'success';
	
	if ( is_array($thread_ids) ) {
		$message = __('Messages deleted successfully!');
		
		for ( $i = 0; $i < count($thread_ids); $i++ ) {
			if ( !$status = BP_Messages_Thread::delete($thread_ids[$i]) ) {
				$message = __('There was an error when deleting messages. Please try again.');
				$type = 'error';
			}
		}
	} else {
		$message = __('Message deleted successfully!');
		
		if ( !$status = BP_Messages_Thread::delete($thread_ids) ) {
			$message = __('There was an error when deleting that message. Please try again.');
			$type = 'error';
		}
	}
	
	unset($_GET['mode']);
	messages_box( $box, $display_name, $message, $type );
}


function messages_view_thread( $thread_id ) {
	global $bp_messages_image_base, $userdata;

	$thread = new BP_Messages_Thread( $thread_id, true );
	
	if ( !$thread->has_access ) {
		unset($_GET['mode']);
		messages_inbox( __('There was an error viewing this message, please try again.'), 'error' );
	} else {
		if ( $thread->messages ) { ?>
			<?php $thread->mark_read() ?>
				
			<div class="wrap">
				<h2 id="message-subject"><?php echo $thread->subject; ?></h2>
				<table class="form-table">
					<tbody>
						<tr>
							<td>
								<img src="<?php echo $bp_messages_image_base ?>/email_open.gif" alt="Message" style="vertical-align: top;" /> &nbsp;
								<?php _e('Sent between ') ?> <?php echo BP_Messages_Thread::get_recipient_links($thread->recipients) ?> 
								<?php _e('and') ?> <?php echo bp_core_get_userlink($userdata->ID) ?>. 
							</td>
						</tr>
					</tbody>
				</table>
				
		<?php
			foreach ( $thread->messages as $message ) {
				?>
					<a name="<?php echo 'm-' . $message->id ?>"></a>
					<div class="message-box">
						<div class="avatar-box">
							<?php if ( function_exists('xprofile_get_avatar') ) 
								echo xprofile_get_avatar($message->sender_id, 1);
							?>
				
							<h3><?php echo bp_core_get_userlink($message->sender_id) ?></h3>
							<small><?php echo bp_format_time(strtotime($message->date_sent)) ?></small>
						</div>
						<?php echo stripslashes($message->message); ?>
						<div class="clear"></div>
					</div>
				<?php
			}
		
			?>
				<form id="send-reply" action="<?php echo get_option('home'); ?>/wp-admin/admin.php?page=bp-messages.php&amp;mode=send" method="post">
					<div class="message-box">
							<div id="messagediv">
								<div class="avatar-box">
									<?php if ( function_exists('xprofile_get_avatar') ) 
										echo xprofile_get_avatar($userdata->ID, 1);
									?>
					
									<h3><?php _e("Reply: ") ?></h3>
								</div>
								<label for="reply"></label>
								<div>
									<textarea name="content" id="message_content" rows="15" cols="40"><?php echo $content; ?></textarea>
								</div>
							</div>
							<p class="submit">
								<input type="submit" name="send" value="Send Reply &raquo;" id="send_reply_button" />
							</p>
							<input type="hidden" id="thread_id" name="thread_id" value="<?php echo $thread->thread_id ?>" />
							<input type="hidden" name="subject" id="subject" value="<?php _e('Re: '); echo str_replace( 'Re: ', '', $thread->last_message_subject); ?>" />
					</div>
					<?php if ( function_exists('wp_nonce_field') )
						wp_nonce_field('messages_sendreply');
					?>
				</form>
			</div>
			<?php
		}
	}
}



?>