jQuery(document).ready( function() {
	jQuery("ul#letter-list li a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			jQuery("div#members-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');
			jQuery("input#members_search").val('');
			
			var letter = jQuery(this).attr('id')
			letter = letter.split('-');
			
			var page = ( jQuery('input#members-page-num').val() ) ? jQuery('input#members-page-num').val() : 1;

			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				'letter': letter[1],
				'page': page
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				
				jQuery("#member-dir-list").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("#member-dir-list").html(response);
						jQuery("#member-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("form#search-members-form").submit( function() { 
			jQuery('.ajax-loader').toggle();

			var page = ( jQuery('input#members-page-num').val() ) ? jQuery('input#members-page-num').val() : 1;

			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				's': jQuery("input#members_search").val(),
				'page': page
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				
				jQuery("#member-dir-list").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("#member-dir-list").html(response);
						jQuery("#member-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("div#member-dir-pag a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			var page = jQuery(this).attr('href');
			page = page.split('upage=');
			
			if ( !jQuery("input#selected_letter").val() )
				var letter = '';
			else
				var letter = jQuery("input#selected_letter").val();
						
			if ( !jQuery("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = jQuery("input#search_terms").val();
			
			jQuery.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'upage': page[1],
				'_wpnonce': jQuery("input#_wpnonce-member-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				
				jQuery("#member-dir-list").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("#member-dir-list").html(response);
						jQuery("#member-dir-list").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
	
	jQuery("div.friendship-button a").livequery('click',
		function() {
			jQuery(this).parent().addClass('loading');
			var fid = jQuery(this).attr('id');
			fid = fid.split('-');
			fid = fid[1];
		
			var nonce = jQuery(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];

			var thelink = jQuery(this);

			jQuery.post( ajaxurl, {
				action: 'addremove_friend',
				'cookie': encodeURIComponent(document.cookie),
				'fid': fid,
				'_wpnonce': nonce
			},
			function(response)
			{
				response = response.substr(0, response.length-1);

				var action = thelink.attr('rel');
				var parentdiv = thelink.parent();
			
				if ( action == 'add' ) {
					jQuery(parentdiv).fadeOut(200, 
						function() {
							parentdiv.removeClass('add_friend');
							parentdiv.removeClass('loading');
							parentdiv.addClass('pending');
							parentdiv.fadeIn(200).html(response);
						}
					);

				} else if ( action == 'remove' ) {
					jQuery(parentdiv).fadeOut(200, 
						function() {
							parentdiv.removeClass('remove_friend');
							parentdiv.removeClass('loading');
							parentdiv.addClass('add');
							parentdiv.fadeIn(200).html(response);
						}
					);				
				}
			});
			return false;
		}
	);

	jQuery("div#wire-pagination a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			var fpage = jQuery(this).attr('href');
			fpage = fpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'get_wire_posts',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'wpage': fpage[1],
				'bp_wire_item_id': jQuery("input#bp_wire_item_id").val()
			},
			function(response)
			{	
				jQuery('.ajax-loader').toggle();
			
				response = response.substr(0, response.length-1);

				jQuery("#wire-post-list-content").fadeOut(200, 
					function() {
						jQuery("#wire-post-list-content").html(response);
						jQuery("#wire-post-list-content").fadeIn(200);
					}
				);

				return false;
			});
		
			return false;
		}
	);

	/* For admin-bar */
	jQuery("#wp-admin-bar ul.main-nav li").mouseover( function() {
		jQuery(this).addClass('sfhover');
	});

	jQuery("#wp-admin-bar ul.main-nav li").mouseout( function() {
		jQuery(this).removeClass('sfhover');
	});

	jQuery("form#friend-search-form").submit(
		function() {
			return false;
		}
	);
	
	jQuery("div#pag a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			var frpage = jQuery(this).attr('href');
			frpage = frpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'friends_search',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce_friend_search").val(),
				'initiator_id': jQuery("input#initiator").val(),
				'frpage': frpage[1],

				'friend-search-box': jQuery("#friend-search-box").val()
			},
			function(response)
			{	
				response = response.substr( 0, response.length - 1 );

				jQuery("div#friends-loop").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("div#friends-loop").html(response);
						jQuery("div#friends-loop").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
	
	jQuery("input#friend-search-box").keyup(

		function(e) {
			if ( e.which == 13 ) {
				jQuery('.ajax-loader').toggle();
				
				jQuery.post( ajaxurl, {
					action: 'friends_search',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': jQuery("input#_wpnonce_friend_search").val(),

					'friend-search-box': jQuery("#friend-search-box").val()
				},
				function(response)
				{
					response = response.substr( 0, response.length - 1 );

					jQuery("div#friends-loop").fadeOut(200, 
						function() {
							jQuery('.ajax-loader').toggle();
							jQuery("div#friends-loop").html(response);
							jQuery("div#friends-loop").fadeIn(200);
						}
					);
					
				});

				return false;
			}
		}
	);

	jQuery("ul#letter-list li a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			jQuery("div#groups-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');
			jQuery("input#groups_search").val('');

			var letter = jQuery(this).attr('id')
			letter = letter.split('-');

			jQuery.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-group-filter").val(),
				'letter': letter[1],
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#group-dir-list").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("#group-dir-list").html(response);
						jQuery("#group-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("form#search-groups-form").submit( function() { 
			jQuery('.ajax-loader').toggle();

			jQuery.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-group-filter").val(),
				's': jQuery("input#groups_search").val(),
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#group-dir-list").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("#group-dir-list").html(response);
						jQuery("#group-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("div#group-dir-pag a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			var page = jQuery(this).attr('href');
			page = page.split('gpage=');
			
			if ( !jQuery("input#selected_letter").val() )
				var letter = '';
			else
				var letter = jQuery("input#selected_letter").val();
						
			if ( !jQuery("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = jQuery("input#search_terms").val();
				
			jQuery.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'gpage': page[1],
				'_wpnonce': jQuery("input#_wpnonce-group-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#group-dir-list").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("#group-dir-list").html(response);
						jQuery("#group-dir-list").fadeIn(200);
					}
				);		
			});
			
			return false;
		}
	);
	
	jQuery("div.group-button a").livequery('click',
		function() {
			var gid = jQuery(this).parent().attr('id');
			gid = gid.split('-');
			gid = gid[1];
			
			var nonce = jQuery(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];
			
			var thelink = jQuery(this);

			jQuery.post( ajaxurl, {
				action: 'joinleave_group',
				'cookie': encodeURIComponent(document.cookie),
				'gid': gid,
				'_wpnonce': nonce
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				var parentdiv = thelink.parent();

				jQuery(parentdiv).fadeOut(200, 
					function() {
						parentdiv.fadeIn(200).html(response);
					}
				);
			});
			return false;
		}
	);

	jQuery("form#group-search-form").submit(
		function() {
			return false;
		}
	);
	
	jQuery("div#invite-list input").click(
		function() {
			jQuery('.ajax-loader').toggle();

			var friend_id = jQuery(this).val();

			if ( jQuery(this).attr('checked') == true ) {
				var friend_action = 'invite';
			} else {
				var friend_action = 'uninvite';
			}
						
			jQuery.post( ajaxurl, {
				action: 'groups_invite_user',
				'friend_action': friend_action,
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce_invite_uninvite_user").val(),
				'friend_id': friend_id,
				'group_id': jQuery("input#group_id").val()
			},
			function(response)
			{	
				if ( jQuery("#message") )
					jQuery("#message").hide();
				
				jQuery('.ajax-loader').toggle();

				if ( friend_action == 'invite' ) {
					jQuery('#friend-list').append(response);	
				} else if ( friend_action == 'uninvite' ) {
					jQuery('#friend-list li#uid-' + friend_id).remove();
				}
			});
		}
	);
	
	jQuery("#friend-list li a.remove").livequery('click',
		function() {
			jQuery('.ajax-loader').toggle();
			
			var friend_id = jQuery(this).attr('id');
			friend_id = friend_id.split('-');
			friend_id = friend_id[1];
			
			jQuery.post( ajaxurl, {
				action: 'groups_invite_user',
				'friend_action': 'uninvite',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce_invite_uninvite_user").val(),
				'friend_id': friend_id,
				'group_id': jQuery("input#group_id").val()
			},
			function(response)
			{	
				jQuery('.ajax-loader').toggle();
				jQuery('#friend-list li#uid-' + friend_id).remove();
				jQuery('#invite-list input#f-' + friend_id).attr('checked', false);
			});
			
			return false;
		}
	);
	
	jQuery("div#pag a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			var grpage = jQuery(this).attr('href');
			grpage = grpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'group_filter',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce_group_filter").val(),
				'grpage': grpage[1],

				'group-filter-box': jQuery("#group-filter-box").val()
			},
			function(response)
			{	
				response = response.substr( 0, response.length - 1 );
				
				jQuery("div#group-loop").fadeOut(200, 
					function() {
						jQuery('.ajax-loader').toggle();
						jQuery("div#group-loop").html(response);
						jQuery("div#group-loop").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
	
	jQuery("input#group-filter-box").keyup(	
		function(e) {
			if ( e.which == 13 ) {
				jQuery('.ajax-loader').toggle();
				
				jQuery.post( ajaxurl, {
					action: 'group_filter',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': jQuery("input#_wpnonce_group_filter").val(),

					'group-filter-box': jQuery("#group-filter-box").val()
				},
				function(response)
				{
					response = response.substr( 0, response.length - 1 );

					jQuery("div#group-loop").fadeOut(200, 
						function() {
							jQuery('.ajax-loader').toggle();
							jQuery("div#group-loop").html(response);
							jQuery("div#group-loop").fadeIn(200);
						}
					);
				});

				return false;
			}
		}
	);
		
	jQuery("div#member-pagination a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			var mlpage = jQuery(this).attr('href');
			mlpage = mlpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'get_group_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_member_pag_nonce").val(),
				'group_id': jQuery("#group_id").val(),
				'mlpage': mlpage[1]
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);

				jQuery("form#group-members-form").fadeOut(200, 
					function() {
						jQuery("form#group-members-form").html(response);
						jQuery("form#group-members-form").fadeIn(200);
					}
				);

				return false;
			});

			return false;
		}
	);
	
	jQuery("div#member-admin-pagination a").livequery('click',
		function() { 
			jQuery('.ajax-loader').toggle();

			var mlpage = jQuery(this).attr('href');
			mlpage = mlpage.split('=');

			jQuery.post( ajaxurl, {
				action: 'get_group_members_admin',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_member_admin_pag_nonce").val(),
				'group_id': jQuery("#group_id").val(),
				'mlpage': mlpage[1]
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);

				jQuery("form#group-members-form").fadeOut(200, 
					function() {
						jQuery("form#group-members-form").html(response);
						jQuery("form#group-members-form").fadeIn(200);
					}
				);

				return false;
			});

			return false;
		}
	);

	jQuery("input#send-notice").click(	
		function() {
			if ( jQuery("#send_to") ) {
				jQuery("#send_to").val('');
			}
		}
	);

	jQuery("input#send_reply_button").click( 
		function() {
			//tinyMCE.triggerSave(true, true);
			
			var rand = Math.floor(Math.random()*100001);
			jQuery("form#send-reply").before('<div style="display:none;" class="ajax_reply" id="' + rand + '">Sending Message...</div>');
			jQuery("div#" + rand).fadeIn();
		
			jQuery.post( ajaxurl, {
				action: 'messages_send_reply',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#send_message_nonce").val(),
				
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
					
					//tinyMCE.activeEditor.setContent('')
					jQuery("#message_content").val('');
					
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
	
	jQuery("a#delete_inbox_messages").click(
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

			if ( !checkboxes_tosend ) return false;

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
	
	jQuery("a#delete_sentbox_messages").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = jQuery("#message-threads tr td input[type='checkbox']");
			
			if ( !checkboxes.length ) return false;
			
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					checkboxes_tosend += checkboxes[i].value;

					if ( i != checkboxes.length - 1 ) {
						checkboxes_tosend += ','
					}
					jQuery('tr#m-' + checkboxes[i].value).remove();					
				}
			}

			if ( !checkboxes_tosend ) return false;

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

	jQuery("div#user-status p, a#status-new-status").livequery('click', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_show_form',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				jQuery("div#user-status").slideUp(400, 
					function() {
						jQuery("div#user-status").html(response);
						jQuery("div#user-status").slideDown(400, function() {
							jQuery("#status-update-input").focus();
						});
					}
				);
				
				jQuery(window).bind('click', function(ev) {
					if ( !jQuery(ev.target).is('div#user-status') && !jQuery(ev.target).parents('div#user-status').length ) {
						jQuery.post( ajaxurl, {
							action: 'status_show_status',
							'cookie': encodeURIComponent(document.cookie)
						},
						function(response) {				
							jQuery("div#user-status").slideUp(400, 
								function() {
									jQuery("div#user-status").html(response);
									jQuery("div#user-status").slideDown(400);
								}
							);
							
							jQuery(window).unbind('click');
						});
					}
				});
			});

			return false;
		}
	);

	jQuery("form#status-update-form").livequery('submit', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_new_status',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery('input#_wpnonce_add_status').val(),
				'status-update-input': jQuery('#status-update-input').val()
			},
			function(response) {
				if ( response == "1" ) {			
					jQuery("div#user-status").slideUp(400,
						function() {
							jQuery.post( ajaxurl, {
								action: 'status_show_status',
								'cookie': encodeURIComponent(document.cookie)
							},
							function(response) {				
								jQuery("div#user-status").html(response);
								jQuery("div#user-status").slideDown(400);
								jQuery(window).unbind('click');
							});
						} 
					);
				}
			});

			return false;
		}
	);

	jQuery("a#status-clear-status").livequery('click', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_clear_status',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				jQuery("div#user-status").fadeOut(300, 
					function() {
						jQuery("div#user-status").html(response);
						jQuery("div#user-status").fadeIn(300);
					}
				);
			});

			return false;
		}
	);

	jQuery("div#user-status p, a#status-new-status").livequery('click', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_show_form',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				jQuery("div#user-status").slideUp(400, 
					function() {
						jQuery("div#user-status").html(response);
						jQuery("div#user-status").slideDown(400, function() {
							jQuery("#status-update-input").focus();
						});
					}
				);
				
				jQuery(window).bind('click', function(ev) {
					if ( !jQuery(ev.target).is('div#user-status') && !jQuery(ev.target).parents('div#user-status').length ) {
						jQuery.post( ajaxurl, {
							action: 'status_show_status',
							'cookie': encodeURIComponent(document.cookie)
						},
						function(response) {				
							jQuery("div#user-status").slideUp(400, 
								function() {
									jQuery("div#user-status").html(response);
									jQuery("div#user-status").slideDown(400);
								}
							);
							
							jQuery(window).unbind('click');
						});
					}
				});
			});

			return false;
		}
	);

	jQuery("form#status-update-form").livequery('submit', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_new_status',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery('input#_wpnonce_add_status').val(),
				'status-update-input': jQuery('#status-update-input').val()
			},
			function(response) {
				if ( response == "1" ) {			
					jQuery("div#user-status").slideUp(400,
						function() {
							jQuery.post( ajaxurl, {
								action: 'status_show_status',
								'cookie': encodeURIComponent(document.cookie)
							},
							function(response) {				
								jQuery("div#user-status").html(response);
								jQuery("div#user-status").slideDown(400);
								jQuery(window).unbind('click');
							});
						} 
					);
				}
			});

			return false;
		}
	);

	jQuery("a#status-clear-status").livequery('click', 
		function() {
			jQuery.post( ajaxurl, {
				action: 'status_clear_status',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				jQuery("div#user-status").fadeOut(300, 
					function() {
						jQuery("div#user-status").html(response);
						jQuery("div#user-status").fadeIn(300);
					}
				);
			});

			return false;
		}
	);
	
		jQuery("ul#letter-list li a").livequery('click',
		function() { 
			jQuery('#ajax-loader-blogs').toggle();

			jQuery("div#blogs-list-options a").removeClass("selected");
			jQuery(this).addClass('selected');
			jQuery("input#blogs_search").val('');

			var letter = jQuery(this).attr('id')
			letter = letter.split('-');

			jQuery.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-blog-filter").val(),
				'letter': letter[1],
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#blog-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-blogs').toggle();
						jQuery("#blog-dir-list").html(response);
						jQuery("#blog-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("form#search-blogs-form").submit( function() { 
			jQuery('#ajax-loader-blogs').toggle();

			jQuery.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce-blog-filter").val(),
				's': jQuery("input#blogs_search").val(),
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#blog-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-blogs').toggle();
						jQuery("#blog-dir-list").html(response);
						jQuery("#blog-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	jQuery("div#blog-dir-pag a").livequery('click',
		function() { 
			jQuery('#ajax-loader-blogs').toggle();

			var page = jQuery(this).attr('href');
			page = page.split('bpage=');
			
			if ( !jQuery("input#selected_letter").val() )
				var letter = '';
			else
				var letter = jQuery("input#selected_letter").val();
						
			if ( !jQuery("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = jQuery("input#search_terms").val();
						
			jQuery.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': jQuery("input#_wpnonce").val(),
				'bpage': page[1],
				'_wpnonce': jQuery("input#_wpnonce-blog-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				jQuery("#blog-dir-list").fadeOut(200, 
					function() {
						jQuery('#ajax-loader-blogs').toggle();
						jQuery("#blog-dir-list").html(response);
						jQuery("#blog-dir-list").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);


});