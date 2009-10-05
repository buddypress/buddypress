// AJAX Functions

jQuery(document).ready( function() {
	var j = jQuery;

	j("div#members-directory-page ul#letter-list li a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			j("div#members-list-options a.selected").removeClass("selected"); 
			j("#letter-list li a.selected").removeClass("selected"); 

			j(this).addClass('selected');
			j("input#members_search").val('');
			
			var letter = j(this).attr('id')
			letter = letter.split('-');
			
			var page = ( j('input#members-page-num').val() ) ? j('input#members-page-num').val() : 1;

			j.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-member-filter").val(),
				'letter': letter[1],
				'page': page
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				
				j("#member-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#member-dir-list").html(response);
						j("#member-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	j("form#search-members-form").submit( function() { 
			j('.ajax-loader').toggle();

			var page = ( j('input#members-page-num').val() ) ? j('input#members-page-num').val() : 1;

			j.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-member-filter").val(),
				's': j("input#members_search").val(),
				'page': page
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				
				j("#member-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#member-dir-list").html(response);
						j("#member-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	j("div#member-dir-pag a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var page = j(this).attr('href');
			page = page.split('upage=');
			
			if ( !j("input#selected_letter").val() )
				var letter = '';
			else
				var letter = j("input#selected_letter").val();
						
			if ( !j("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = j("input#search_terms").val();
			
			j.post( ajaxurl, {
				action: 'directory_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce").val(),
				'upage': page[1],
				'_wpnonce': j("input#_wpnonce-member-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				
				j("#member-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#member-dir-list").html(response);
						j("#member-dir-list").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
	
	j("div.friendship-button a").livequery('click',
		function() {
			j(this).parent().addClass('loading');
			var fid = j(this).attr('id');
			fid = fid.split('-');
			fid = fid[1];
		
			var nonce = j(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];

			var thelink = j(this);

			j.post( ajaxurl, {
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
					j(parentdiv).fadeOut(200, 
						function() {
							parentdiv.removeClass('add_friend');
							parentdiv.removeClass('loading');
							parentdiv.addClass('pending');
							parentdiv.fadeIn(200).html(response);
						}
					);

				} else if ( action == 'remove' ) {
					j(parentdiv).fadeOut(200, 
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

	j("div#wire-pagination a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var fpage = j(this).attr('href');
			fpage = fpage.split('=');

			j.post( ajaxurl, {
				action: 'get_wire_posts',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce").val(),
				'wpage': fpage[1],
				'bp_wire_item_id': j("input#bp_wire_item_id").val()
			},
			function(response)
			{	
				j('.ajax-loader').toggle();
			
				response = response.substr(0, response.length-1);

				j("#wire-post-list-content").fadeOut(200, 
					function() {
						j("#wire-post-list-content").html(response);
						j("#wire-post-list-content").fadeIn(200);
					}
				);

				return false;
			});
		
			return false;
		}
	);

	j(".friends div#pag a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var frpage = j(this).attr('href');
			frpage = frpage.split('=');
			
			j.post( ajaxurl, {
				action: 'my_friends_search',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce_friend_search").val(),
				'frpage': frpage[1],

				'friend-search-box': j("#friend-search-box").val()
			},
			function(response)
			{	
				response = response.substr( 0, response.length - 1 );

				j("div#friends-loop").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("div#friends-loop").html(response);
						j("div#friends-loop").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
	
	j("input#friend-search-box").keyup(
		function(e) {
			if ( e.which == 13 ) {
				j('.ajax-loader').toggle();
				
				j.post( ajaxurl, {
					action: 'my_friends_search',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': j("input#_wpnonce_friend_search").val(),
	
					'friend-search-box': j("#friend-search-box").val()
				},
				function(response)
				{	
					response = response.substr( 0, response.length - 1 );
	
					j("div#friends-loop").fadeOut(200, 
						function() {
							j('.ajax-loader').toggle();
							j("div#friends-loop").html(response);
							j("div#friends-loop").fadeIn(200);
						}
					);
				});
				
				return false;
			}
		}
	);

	j("div#groups-directory-page ul#letter-list li a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			j("div#groups-list-options a.selected").removeClass("selected"); 
			j("#letter-list li a.selected").removeClass("selected"); 

			j(this).addClass('selected');
			j("input#groups_search").val('');

			var letter = j(this).attr('id')
			letter = letter.split('-');

			j.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-group-filter").val(),
				'letter': letter[1],
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				j("#group-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#group-dir-list").html(response);
						j("#group-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	j("form#search-groups-form").submit( function() { 
			j('.ajax-loader').toggle();

			j.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-group-filter").val(),
				's': j("input#groups_search").val(),
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				j("#group-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#group-dir-list").html(response);
						j("#group-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	j("div#group-dir-pag a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var page = j(this).attr('href');
			page = page.split('gpage=');
			
			if ( !j("input#selected_letter").val() )
				var letter = '';
			else
				var letter = j("input#selected_letter").val();
						
			if ( !j("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = j("input#search_terms").val();
				
			j.post( ajaxurl, {
				action: 'directory_groups',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce").val(),
				'gpage': page[1],
				'_wpnonce': j("input#_wpnonce-group-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				j("#group-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#group-dir-list").html(response);
						j("#group-dir-list").fadeIn(200);
					}
				);		
			});
			
			return false;
		}
	);
	
	j(".directory-listing div.group-button a").livequery('click',
		function() {
			var gid = j(this).parent().attr('id');
			gid = gid.split('-');
			gid = gid[1];
			
			var nonce = j(this).attr('href');
			nonce = nonce.split('?_wpnonce=');
			nonce = nonce[1].split('&');
			nonce = nonce[0];
			
			var thelink = j(this);

			j.post( ajaxurl, {
				action: 'joinleave_group',
				'cookie': encodeURIComponent(document.cookie),
				'gid': gid,
				'_wpnonce': nonce
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				var parentdiv = thelink.parent();

				j(parentdiv).fadeOut(200, 
					function() {
						parentdiv.fadeIn(200).html(response);
					}
				);
			});
			return false;
		}
	);

	j("form#group-search-form, form#friend-search-form").submit(
		function() {
			return false;
		}
	);
	
	j("div#invite-list input").click(
		function() {
			j('.ajax-loader').toggle();

			var friend_id = j(this).val();

			if ( j(this).attr('checked') == true ) {
				var friend_action = 'invite';
			} else {
				var friend_action = 'uninvite';
			}
						
			j.post( ajaxurl, {
				action: 'groups_invite_user',
				'friend_action': friend_action,
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce_invite_uninvite_user").val(),
				'friend_id': friend_id,
				'group_id': j("input#group_id").val()
			},
			function(response)
			{	
				if ( j("#message") )
					j("#message").hide();
				
				j('.ajax-loader').toggle();

				if ( friend_action == 'invite' ) {
					j('#friend-list').append(response);	
				} else if ( friend_action == 'uninvite' ) {
					j('#friend-list li#uid-' + friend_id).remove();
				}
			});
		}
	);
	
	j("#friend-list li a.remove").livequery('click',
		function() {
			j('.ajax-loader').toggle();
			
			var friend_id = j(this).attr('id');
			friend_id = friend_id.split('-');
			friend_id = friend_id[1];
			
			j.post( ajaxurl, {
				action: 'groups_invite_user',
				'friend_action': 'uninvite',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce_invite_uninvite_user").val(),
				'friend_id': friend_id,
				'group_id': j("input#group_id").val()
			},
			function(response)
			{	
				j('.ajax-loader').toggle();
				j('#friend-list li#uid-' + friend_id).remove();
				j('#invite-list input#f-' + friend_id).attr('checked', false);
			});
			
			return false;
		}
	);
	
	j(".groups div#pag a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var grpage = j(this).attr('href');
			grpage = grpage.split('=');

			j.post( ajaxurl, {
				action: 'group_filter',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce_group_filter").val(),
				'grpage': grpage[1],

				'group-filter-box': j("#group-filter-box").val()
			},
			function(response)
			{	
				response = response.substr( 0, response.length - 1 );
				
				j("div#group-loop").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("div#group-loop").html(response);
						j("div#group-loop").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);
	
	j("input#group-filter-box").keyup(	
		function(e) {
			if ( e.which == 13 ) {
				j('.ajax-loader').toggle();
				
				j.post( ajaxurl, {
					action: 'group_filter',
					'cookie': encodeURIComponent(document.cookie),
					'_wpnonce': j("input#_wpnonce_group_filter").val(),

					'group-filter-box': j("#group-filter-box").val()
				},
				function(response)
				{
					response = response.substr( 0, response.length - 1 );

					j("div#group-loop").fadeOut(200, 
						function() {
							j('.ajax-loader').toggle();
							j("div#group-loop").html(response);
							j("div#group-loop").fadeIn(200);
						}
					);
				});

				return false;
			}
		}
	);
		
	j("div#member-pagination a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var mlpage = j(this).attr('href');
			mlpage = mlpage.split('=');

			j.post( ajaxurl, {
				action: 'get_group_members',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_member_pag_nonce").val(),
				'group_id': j("#group_id").val(),
				'mlpage': mlpage[1]
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);

				j("form#group-members-form").fadeOut(200, 
					function() {
						j("form#group-members-form").html(response);
						j("form#group-members-form").fadeIn(200);
					}
				);

				return false;
			});

			return false;
		}
	);
	
	j("div#member-admin-pagination a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var mlpage = j(this).attr('href');
			mlpage = mlpage.split('=');

			j.post( ajaxurl, {
				action: 'get_group_members_admin',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_member_admin_pag_nonce").val(),
				'group_id': j("#group_id").val(),
				'mlpage': mlpage[1]
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);

				j("form#group-members-form").fadeOut(200, 
					function() {
						j("form#group-members-form").html(response);
						j("form#group-members-form").fadeIn(200);
					}
				);

				return false;
			});

			return false;
		}
	);

	j("input#send-notice").click(	
		function() {
			if ( j("#send_to") ) {
				j("#send_to").val('');
			}
		}
	);

	j("input#send_reply_button").click( 
		function() {
			//tinyMCE.triggerSave(true, true);
			
			var rand = Math.floor(Math.random()*100001);
			j("form#send-reply").before('<div style="display:none;" class="ajax_reply" id="' + rand + '">Sending Message...</div>');
			j("div#" + rand).fadeIn();
		
			j.post( ajaxurl, {
				action: 'messages_send_reply',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#send_message_nonce").val(),
				
				'content': j("#message_content").val(),
				'send_to': j("input#send_to").val(),
				'subject': j("input#subject").val(),
				'thread_id': j("input#thread_id").val()
			},
			function(response)
			{
				response = response.substr(0, response.length-1);
				var css_class = 'message-box';
				
				setTimeout( function() {
					j("div#" + rand).slideUp();
				}, 500);
				
				setTimeout( function() {
					var err_num = response.split('[[split]]');
					if ( err_num[0] == "-1" ) {
						response = err_num[1];
						css_class = 'error-box';
					}
					
					//tinyMCE.activeEditor.setContent('')
					j("#message_content").val('');
					
					j("div#" + rand).html(response).attr('class', css_class).slideDown();
				}, 1250);	
			});
		
			return false;
		}
	);
	
	j("a#mark_as_read").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = j("#message-threads tr td input[type='checkbox']");
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					if ( j('tr#m-' + checkboxes[i].value).hasClass('unread') ) {
						checkboxes_tosend += checkboxes[i].value;
						j('tr#m-' + checkboxes[i].value).removeClass('unread');
						j('tr#m-' + checkboxes[i].value).addClass('read');
						j('tr#m-' + checkboxes[i].value + ' td span.unread-count').html('0');
						var inboxcount = j('.inbox-count').html();
						if ( parseInt(inboxcount) == 1 ) {
							j('.inbox-count').css('display', 'none');
							j('.inbox-count').html('0');
						} else {
							j('.inbox-count').html(parseInt(inboxcount) - 1);	
						}
						
						if ( i != checkboxes.length - 1 ) {
							checkboxes_tosend += ','
						}
					}
				}
			}
			
			j.post( ajaxurl, {
				action: 'messages_markread',
				'thread_ids': checkboxes_tosend
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');
				if ( err_num[0] == "-1" ) {
					// error
					j('table#message-threads').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				}
			});
			return false;			
		}
	);
	
	j("a#mark_as_unread").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = j("#message-threads tr td input[type='checkbox']");
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					if ( j('tr#m-' + checkboxes[i].value).hasClass('read') ) {
						checkboxes_tosend += checkboxes[i].value;
						j('tr#m-' + checkboxes[i].value).removeClass('read');
						j('tr#m-' + checkboxes[i].value).addClass('unread');
						j('tr#m-' + checkboxes[i].value + ' td span.unread-count').html('1');
						var inboxcount = j('.inbox-count').html();
						
						if ( parseInt(inboxcount) == 0 ) {
							j('.inbox-count').css('display', 'inline');
							j('.inbox-count').html('1');
						} else {
							j('.inbox-count').html(parseInt(inboxcount) + 1);
						}

						if ( i != checkboxes.length - 1 ) {
							checkboxes_tosend += ','
						}
					}
				}
			}
			
			j.post( ajaxurl, {
				action: 'messages_markunread',
				'thread_ids': checkboxes_tosend
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');
				if ( err_num[0] == "-1" ) {
					// error
					j('table#message-threads').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				}
			});
			return false;			
		}
	);
	
	j("a#delete_inbox_messages").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = j("#message-threads tr td input[type='checkbox']");

			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					checkboxes_tosend += checkboxes[i].value;
					
					if ( j('tr#m-' + checkboxes[i].value).hasClass('unread') ) {
						var inboxcount = j('.inbox-count').html();
					
						if ( parseInt(inboxcount) == 1 ) {
							j('.inbox-count').css('display', 'none');
							j('.inbox-count').html('0');
						} else {
							j('.inbox-count').html(parseInt(inboxcount) - 1);
						}
					}
					
					if ( i != checkboxes.length - 1 ) {
						checkboxes_tosend += ','
					}
					
					j('tr#m-' + checkboxes[i].value).remove();					
				}
			}

			if ( !checkboxes_tosend ) return false;

			j.post( ajaxurl, {
				action: 'messages_delete',
				'thread_ids': checkboxes_tosend
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');
				
				j('#message').remove();
				
				if ( err_num[0] == "-1" ) {
					// error
					j('table#message-threads').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				} else {
					j('table#message-threads').before('<div id="message" class="updated"><p>' + response + '</p></div>')
				}
			});
			return false;			
		}
	);
	
	j("a#delete_sentbox_messages").click(
		function() {
			checkboxes_tosend = '';
			checkboxes = j("#message-threads tr td input[type='checkbox']");
			
			if ( !checkboxes.length ) return false;
			
			for(var i=0; i<checkboxes.length; i++) {
				if(checkboxes[i].checked) {
					checkboxes_tosend += checkboxes[i].value;

					if ( i != checkboxes.length - 1 ) {
						checkboxes_tosend += ','
					}
					j('tr#m-' + checkboxes[i].value).remove();					
				}
			}

			if ( !checkboxes_tosend ) return false;

			j.post( ajaxurl, {
				action: 'messages_delete',
				'thread_ids': checkboxes_tosend
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');
				
				j('#message').remove();
				
				if ( err_num[0] == "-1" ) {
					// error
					j('table#message-threads').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				} else {
					j('table#message-threads').before('<div id="message" class="updated"><p>' + response + '</p></div>')
				}
			});
			return false;			
		}
	);
	
	
	j("a#close-notice").click(
		function() {
			j.post( ajaxurl, {
				action: 'messages_close_notice',
				'notice_id': j('.notice').attr('id')
			},
			function(response) {
				response = response.substr(0, response.length-1);
				var err_num = response.split('[[split]]');

				if ( err_num[0] == "-1" ) {
					// error
					j('.notice').before('<div id="message" class="error fade"><p>' + err_num[1] + '</p></div>')
				} else {
					j('.notice').remove();
				}
			});
			return false;			
		}
	);
	
	j("select#message-type-select").change(
		function() {
			var selection = j("select#message-type-select").val();
			var checkboxes = j("td input[type='checkbox']");
			for(var i=0; i<checkboxes.length; i++) {
				checkboxes[i].checked = "";
			}

			switch(selection) {
				case 'unread':
					var checkboxes = j("tr.unread td input[type='checkbox']");
					for(var i=0; i<checkboxes.length; i++) {
						checkboxes[i].checked = "checked";
					}
				break;
				case 'read':
					var checkboxes = j("tr.read td input[type='checkbox']");
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

	j("form#status-update-form").livequery('submit', 
		function() {
			j('input#status-update-post').attr( 'disabled', 'disabled' );
		
			j.post( ajaxurl, {
				action: 'status_new_status',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j('input#_wpnonce_add_status').val(),
				'status-update-input': j('#status-update-input').val()
			},
			function(response) {
				if ( response == "1" ) {			
					j("div#user-status").slideUp(400,
						function() {
							j.post( ajaxurl, {
								action: 'status_show_status',
								'cookie': encodeURIComponent(document.cookie)
							},
							function(response) {				
								j("div#user-status").html(response);
								j("div#user-status").slideDown(400);
								j(window).unbind('click');
							});
						} 
					);
				}
			});

			return false;
		}
	);

	j("a#status-clear-status").livequery('click', 
		function() {
			j.post( ajaxurl, {
				action: 'status_clear_status',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				j("div#user-status").fadeOut(300, 
					function() {
						j("div#user-status").html(response);
						j("div#user-status").fadeIn(300);
					}
				);
			});

			return false;
		}
	);

	j("div.status-editable p, a#status-new-status").livequery('click', 
		function() {
			j('div.generic-button a#status-new-status').parent().addClass('loading');
			
			j.post( ajaxurl, {
				action: 'status_show_form',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				j("div#user-status").slideUp(400, 
					function() {
						j("div#user-status").html(response);
						j("div#user-status").slideDown(400, function() {
							j("#status-update-input").focus();
						});
					}
				);
				
				j(window).bind('click', function(ev) {
					if ( !j(ev.target).is('div#user-status') && !j(ev.target).parents('div#user-status').length ) {
						j.post( ajaxurl, {
							action: 'status_show_status',
							'cookie': encodeURIComponent(document.cookie)
						},
						function(response) {				
							j("div#user-status").slideUp(400, 
								function() {
									j("div#user-status").html(response);
									j("div#user-status").slideDown(400);
								}
							);
							
							j(window).unbind('click');
						});
					}
				});
			});

			return false;
		}
	);

	j("form#status-update-form").livequery('submit', 
		function() {
			j.post( ajaxurl, {
				action: 'status_new_status',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j('input#_wpnonce_add_status').val(),
				'status-update-input': j('#status-update-input').val()
			},
			function(response) {
				if ( response == "1" ) {			
					j("div#user-status").slideUp(400,
						function() {
							j.post( ajaxurl, {
								action: 'status_show_status',
								'cookie': encodeURIComponent(document.cookie)
							},
							function(response) {				
								j("div#user-status").html(response);
								j("div#user-status").slideDown(400);
								j(window).unbind('click');
							});
						} 
					);
				}
			});

			return false;
		}
	);

	j("a#status-clear-status").livequery('click', 
		function() {
			j(this).addClass('ajax-loader');
			j(this).attr('style', 'vertical-align: middle; display: inline-block; overflow: hidden; width: 10px; text-indent: -999em' );
			
			j.post( ajaxurl, {
				action: 'status_clear_status',
				'cookie': encodeURIComponent(document.cookie)
			},
			function(response) {				
				j("div#user-status").fadeOut(300, 
					function() {
						j("div#user-status").html(response);
						j("div#user-status").fadeIn(300);
					}
				);
			});

			return false;
		}
	);
	
		j("div#blogs-directory-page ul#letter-list li a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			j("div#blogs-list-options a.selected").removeClass("selected"); 
			j("#letter-list li a.selected").removeClass("selected"); 
			
			j(this).addClass('selected');
			j("input#blogs_search").val('');

			var letter = j(this).attr('id')
			letter = letter.split('-');

			j.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-blog-filter").val(),
				'letter': letter[1],
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				j("#blog-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#blog-dir-list").html(response);
						j("#blog-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	j("form#search-blogs-form").submit( function() { 
			j('.ajax-loader').toggle();

			j.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce-blog-filter").val(),
				's': j("input#blogs_search").val(),
				'page': 1
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				j("#blog-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#blog-dir-list").html(response);
						j("#blog-dir-list").fadeIn(200);
					}
				);
			});
		
			return false;
		}
	);
	
	j("div#blog-dir-pag a").livequery('click',
		function() { 
			j('.ajax-loader').toggle();

			var page = j(this).attr('href');
			page = page.split('bpage=');
			
			if ( !j("input#selected_letter").val() )
				var letter = '';
			else
				var letter = j("input#selected_letter").val();
						
			if ( !j("input#search_terms").val() )
				var search_terms = '';
			else
				var search_terms = j("input#search_terms").val();
						
			j.post( ajaxurl, {
				action: 'directory_blogs',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': j("input#_wpnonce").val(),
				'bpage': page[1],
				'_wpnonce': j("input#_wpnonce-blog-filter").val(),
				
				'letter': letter,
				's': search_terms
			},
			function(response)
			{	
				response = response.substr(0, response.length-1);
				j("#blog-dir-list").fadeOut(200, 
					function() {
						j('.ajax-loader').toggle();
						j("#blog-dir-list").html(response);
						j("#blog-dir-list").fadeIn(200);
					}
				);
			});
			
			return false;
		}
	);

});

// Helper JS Functions

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

function clear(container) {
	if( !document.getElementById(container) ) return;

	var container = document.getElementById(container);

	radioButtons = container.getElementsByTagName('INPUT');

	for(var i=0; i<radioButtons.length; i++) {
		radioButtons[i].checked = '';
	}
	
	return;
}