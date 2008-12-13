function checkAll() {
	var checkboxes = document.getElementsByTagName("input");
	for(var i=0; i<checkboxes.length; i++) {
		if(checkboxes[i].type == "checkbox") {
			if($("check_all").checked == "") {
				checkboxes[i].checked = "";
			}
			else {
				checkboxes[i].checked = "checked";
			}
		}
	}
}

jQuery(document).ready( function() {
	jQuery("input#send-notice").click(	
		function() {
			if ( jQuery("#send_to") ) {
				jQuery("#send_to").val('');
			}
		}
	);

	jQuery("input#send_reply_button").click( 
		function() {
			tinyMCE.triggerSave(true, true);
			
			var rand = Math.floor(Math.random()*100001);
			jQuery("form#send-reply").before('<div style="display:none;" class="ajax_reply" id="' + rand + '"><img src="/wp-admin/mu-plugins/bp-messages/images/loading.gif" alt="Loading" /> &nbsp;Sending Message...</div>');
			jQuery("div#" + rand).fadeIn();
		
			jQuery.post( ajaxurl, {
				action: 'messages_send_reply',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				
				'content': jQuery("#message_content").val(),
				'send_to': jQuery("input#send_to").val(),
				'subject': jQuery("input#subject").val(),
				'thread_id': jQuery("input#thread_id").val()
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				var css_class = 'message-box';
				
				setTimeout( function() {
					jQuery("div#" + rand).slideUp();
				}, 500);
				
				setTimeout( function() {
					var err_num = response.split('[[split]]');
					if ( err_num[0] == "-1" ) {
						response = err_num[1];
						css_class = 'error-box';
					}
					
					tinyMCE.activeEditor.setContent('')
					jQuery("div#" + rand).html(response).attr('class', css_class).slideDown();
				}, 1250);	
			});
		
			return false;
		}
	);
	
	jQuery("a#mark_as_read").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = jQuery("#message-threads tr td input[type='checkbox']");
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					if ( jQuery('tr#m-' + checkboxes[i].value).hasClass('unread') ) {
						checkboxes_tosend += checkboxes[i].value;
						jQuery('tr#m-' + checkboxes[i].value).removeClass('unread');
						jQuery('tr#m-' + checkboxes[i].value).addClass('read');
						jQuery('tr#m-' + checkboxes[i].value + ' td span.unread-count').html('0');
						var inboxcount = jQuery('.inbox-count').html();
						if ( parseInt(inboxcount) == 1 ) {
							jQuery('.inbox-count').css('display', 'none');
							jQuery('.inbox-count').html('0');
						} else {
							jQuery('.inbox-count').html(parseInt(inboxcount) - 1);	
						}
						
						if ( i != checkboxes.length - 1 ) {
							checkboxes_tosend += ','
						}
					}
				}
			}
			
			jQuery.post( ajaxurl, {
				action: 'messages_markread',
				'thread_ids': checkboxes_tosend
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');
				if ( err_num[0] == "-1" ) {
					// error
					jQuery('table#message-threads').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				}
			});
			return false;			
		}
	);
	
	jQuery("a#mark_as_unread").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = jQuery("#message-threads tr td input[type='checkbox']");
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					if ( jQuery('tr#m-' + checkboxes[i].value).hasClass('read') ) {
						checkboxes_tosend += checkboxes[i].value;
						jQuery('tr#m-' + checkboxes[i].value).removeClass('read');
						jQuery('tr#m-' + checkboxes[i].value).addClass('unread');
						jQuery('tr#m-' + checkboxes[i].value + ' td span.unread-count').html('1');
						var inboxcount = jQuery('.inbox-count').html();
						
						if ( parseInt(inboxcount) == 0 ) {
							jQuery('.inbox-count').css('display', 'inline');
							jQuery('.inbox-count').html('1');
						} else {
							jQuery('.inbox-count').html(parseInt(inboxcount) + 1);
						}

						if ( i != checkboxes.length - 1 ) {
							checkboxes_tosend += ','
						}
					}
				}
			}
			
			jQuery.post( ajaxurl, {
				action: 'messages_markunread',
				'thread_ids': checkboxes_tosend
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');
				if ( err_num[0] == "-1" ) {
					// error
					jQuery('table#message-threads').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				}
			});
			return false;			
		}
	);
	
	jQuery("a#delete_messages").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = jQuery("#message-threads tr td input[type='checkbox']");
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					checkboxes_tosend += checkboxes[i].value;
					
					if ( jQuery('tr#m-' + checkboxes[i].value).hasClass('unread') ) {
						var inboxcount = jQuery('.inbox-count').html();
					
						if ( parseInt(inboxcount) == 1 ) {
							jQuery('.inbox-count').css('display', 'none');
							jQuery('.inbox-count').html('0');
						} else {
							jQuery('.inbox-count').html(parseInt(inboxcount) - 1);
						}
					}
					
					if ( i != checkboxes.length - 1 ) {
						checkboxes_tosend += ','
					}
					
					jQuery('tr#m-' + checkboxes[i].value).remove();					
				}
			}

			jQuery.post( ajaxurl, {
				action: 'messages_delete',
				'thread_ids': checkboxes_tosend
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');
				
				jQuery('#message').remove();
				
				if ( err_num[0] == "-1" ) {
					// error
					jQuery('table#message-threads').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				} else {
					jQuery('table#message-threads').before('<div id="message" class="updated"><p>' + response + '</p></div>')
				}
			});
			return false;			
		}
	);
	
	jQuery("a#close-notice").click(
		function() {
			jQuery.post( ajaxurl, {
				action: 'messages_close_notice',
				'notice_id': jQuery('.notice').attr('id')
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');

				if ( err_num[0] == "-1" ) {
					// error
					jQuery('.notice').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				} else {
					jQuery('.notice').remove();
				}
			});
			return false;			
		}
	);
	
	jQuery("select#message-type-select").change(
		function() {
			var selection = jQuery("select#message-type-select").val();
			var checkboxes = jQuery("td input[type='checkbox']");
			for(var i=0; i<checkboxes.length; i++) {
				checkboxes[i].checked = "";
			}

			switch(selection) {
				case 'unread':
					var checkboxes = jQuery("tr.unread td input[type='checkbox']");
					for(var i=0; i<checkboxes.length; i++) {
						checkboxes[i].checked = "checked";
					}
				break;
				case 'read':
					var checkboxes = jQuery("tr.read td input[type='checkbox']");
					for(var i=0; i<checkboxes.length; i++) {
						checkboxes[i].checked = "checked";
					}
				break;
				case 'all':
					for(var i=0; i<checkboxes.length; i++) {
						checkboxes[i].checked = "checked";
					}
				break;
			}
		}
	);
});