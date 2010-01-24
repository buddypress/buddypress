// AJAX Functions

jQuery(document).ready( function() {
	var j = jQuery;

	/* Hide all activity comment forms */
	j('form.ac-form').hide();

	/* Delegate events instead of binding on every element */
	j('div.activity').click( function(event) {
		var target = j(event.target);

		/* Comment / comment reply links */
		if ( target.attr('class') == 'acomment-reply' ) {
			var id = target.attr('id');
			ids = id.split('-');

			var a_id = ids[2]
			var c_id = target.attr('href').substr( 10, target.attr('href').length );
			var form = j( '#ac-form-' + a_id );

			var form = j( '#ac-form-' + ids[2] );

			form.css( 'display', 'none' );
			form.removeClass('root');
			j('.ac-form').hide();

			/* Hide any error messages */
			form.children('div').each( function() {
				if ( j(this).hasClass( 'error' ) )
					j(this).hide();
			});

			if ( ids[1] != 'comment' ) {
				j('div.activity-comments li#acomment-' + c_id).append( form );
			} else {
				j('li#activity-' + a_id + ' div.activity-comments').append( form );
			}

	 		if ( form.parent().attr( 'class' ) == 'activity-comments' )
				form.addClass('root');

			form.slideDown( 200 );
			j.scrollTo( form, 500, { offset:-100, easing:'easeout' } );
			j('#ac-form-' + ids[2] + ' textarea').focus();

			return false;
		}

		/* Activity comment posting */
		if ( target.attr('name') == 'ac-form-submit' ) {
			var form = target.parent().parent();
			var form_parent = form.parent();
			var form_id = form.attr('id').split('-');

			if ( 'activity-comments' !== form_parent.attr('class') ) {
				var tmp_id = form_parent.attr('id').split('-');
				var comment_id = tmp_id[1];
			} else {
				var comment_id = form_id[2];
			}

			/* Hide any error messages */
			j( 'form#' + form + ' div.error').hide();
			form.addClass('loading');
			target.css('disabled', 'disabled');

			j.post( ajaxurl, {
				action: 'new_activity_comment',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce_new_activity_comment': j("input#_wpnonce_new_activity_comment").val(),
				'comment_id': comment_id,
				'form_id': form_id[2],
				'content': j('form#' + form.attr('id') + ' textarea').val()
			},
			function(response)
			{
				form.removeClass('loading');

				/* Check for errors and append if found. */
				if ( response[0] + response[1] == '-1' ) {
					form.append( response.substr( 2, response.length ) ).hide().fadeIn( 200 );
					target.attr("disabled", '');
				} else {
					form.fadeOut( 200,
						function() {
							if ( 0 == form.parent().children('ul').length ) {
								if ( form.parent().attr('class') == 'activity-comments' )
									form.parent().prepend('<ul></ul>');
								else
									form.parent().append('<ul></ul>');
							}

							form.parent().children('ul').append(response).hide().fadeIn( 200 );
							form.children('textarea').val('');
						}
					);
					j( 'form#' + form + ' textarea').val('');

					/* Re-enable the submit button after 5 seconds. */
					setTimeout( function() { target.attr("disabled", ''); }, 5000 );
				}
			});

			return false;
		}

		/* Deleting an activity comment */
		if ( target.hasClass('acomment-delete') ) {
			var link_href = target.attr('href');
			var comment_li = target.parent().parent();

			var nonce = link_href.split('_wpnonce=');
				nonce = nonce[1];

			var comment_id = link_href.split('cid=');
				comment_id = comment_id[1].split('&');
				comment_id = comment_id[0];

			/* Remove any error messages */
			j('div.activity-comments ul div.error').remove();

			j.post( ajaxurl, {
				action: 'delete_activity_comment',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': nonce,
				'comment_id': comment_id
			},
			function(response)
			{
				/* Check for errors and append if found. */
				if ( response[0] + response[1] == '-1' ) {
					comment_li.prepend( response.substr( 2, response.length ) ).hide().fadeIn( 200 );
				} else {
					comment_li.fadeOut( 200, function() {
						var children_html = j( 'li#' + comment_li.attr('id') + ' ul:first' ).html();

						/* Fade in sub comments if any were found. */
						if ( children_html.length )
							comment_li.parent().append( children_html ).hide().fadeIn( 200 );
				 	});
				}
			});

			return false;
		}
	});

	/* New posts */
	j("input#whats-new-submit").click( function() {
		var button = j(this);
		var form = button.parent().parent().parent().parent();

		form.children().each( function() {
			if ( j.nodeName(this, "textarea") || j.nodeName(this, "input") )
				j(this).attr( 'disabled', 'disabled' );
		});

		j( 'form#' + form.attr('id') + ' span.ajax-loader' ).show();

		/* Remove any errors */
		j('div.error').remove();
		button.attr('disabled','disabled');

		j.post( ajaxurl, {
			action: 'post_update',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_post_update': j("input#_wpnonce_post_update").val(),
			'content': j("textarea#whats-new").val(),
			'group': j("#whats-new-post-in").val()
		},
		function(response)
		{
			j( 'form#' + form.attr('id') + ' span.ajax-loader' ).hide();

			form.children().each( function() {
				if ( j.nodeName(this, "textarea") || j.nodeName(this, "input") )
					j(this).attr( 'disabled', '' );
			});

			/* Check for errors and append if found. */
			if ( response[0] + response[1] == '-1' ) {
				form.prepend( response.substr( 2, response.length ) );
				j( 'form#' + form.attr('id') + ' div.error').hide().fadeIn( 200 );
				button.attr("disabled", '');
			} else {
				if ( 0 == j("ul#activity-list").length ) {
					j("div.error").slideUp(100).remove();
					j("div.activity").append( '<ul id="activity-list" class="activity-list item-list">' );
				}

				j("ul#activity-list").prepend(response);
				j("li.new-update").hide().slideDown( 300 );
				j("li.new-update").removeClass( 'new-update' );
				j("textarea#whats-new").val('');

				/* Re-enable the submit button after 8 seconds. */
				setTimeout( function() { button.attr("disabled", ''); }, 8000 );
			}
		});

		return false;
	});

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

			j("div#members-list-options a.selected").removeClass("selected");
			j("#letter-list li a.selected").removeClass("selected");

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

			if ( undefined === j("input#selected_letter").val() )
				var letter = '';
			else
				var letter = j("input#selected_letter").val();

			if ( undefined === j("input#search_terms").val() )
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

			j("div#groups-list-options a.selected").removeClass("selected");
			j("#letter-list li a.selected").removeClass("selected");

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

			if ( undefined === j("input#selected_letter").val() )
				var letter = '';
			else
				var letter = j("input#selected_letter").val();

			if ( undefined === j("input#search_terms").val() )
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

			j("div#blogs-list-options a.selected").removeClass("selected");
			j("#letter-list li a.selected").removeClass("selected");

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

			if ( undefined === j("input#selected_letter").val() )
				var letter = '';
			else
				var letter = j("input#selected_letter").val();

			if ( undefined === j("input#search_terms").val() )
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

	if ( j('body.home-page div.activity').length )
		bp_activity_widget_post( j.cookie('bp_atype'), j.cookie('bp_afilter') );

	/* New posts */
	j("input#aw-whats-new-submit").click( function() {
		var button = j(this);
		var form = button.parent().parent().parent().parent();

		form.children().each( function() {
			if ( j.nodeName(this, "textarea") || j.nodeName(this, "input") )
				j(this).attr( 'disabled', 'disabled' );
		});

		j( 'form#' + form.attr('id') + ' span.ajax-loader' ).show();

		/* Remove any errors */
		j('div.error').remove();
		button.attr('disabled','disabled');

		j.post( ajaxurl, {
			action: 'post_update',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_post_update': j("input#_wpnonce_post_update").val(),
			'content': j("textarea#whats-new").val(),
			'group': j("#whats-new-post-in").val()
		},
		function(response)
		{
			j( 'form#' + form.attr('id') + ' span.ajax-loader' ).hide();

			form.children().each( function() {
				if ( j.nodeName(this, "textarea") || j.nodeName(this, "input") )
					j(this).attr( 'disabled', '' );
			});

			/* Check for errors and append if found. */
			if ( response[0] + response[1] == '-1' ) {
				form.prepend( response.substr( 2, response.length ) );
				j( 'form#' + form.attr('id') + ' div.error').hide().fadeIn( 200 );
				button.attr("disabled", '');
			} else {
				if ( 0 == j("ul.activity-list").length ) {
					j("div.error").slideUp(100).remove();
					j("div.activity").append( '<ul id="site-wide-stream" class="activity-list item-list">' );
				}

				j("ul.activity-list").prepend(response);
				j("li.new-update").hide().slideDown( 300 );
				j("li.new-update").removeClass( 'new-update' );
				j("textarea#whats-new").val('');

				/* Re-enable the submit button after 8 seconds. */
				setTimeout( function() { button.attr("disabled", ''); }, 8000 );
			}
		});

		return false;
	});

	/* List tabs event delegation */
	j('div.item-list-tabs').click( function(event) {
		var target = j(event.target).parent();

		/* Activity Stream Tabs */
		if ( target.attr('id') == 'activity-all' ||
		 	 target.attr('id') == 'activity-friends' ||
			 target.attr('id') == 'activity-groups' ) {

			var type = target.attr('id').substr( 9, target.attr('id').length );
			var filter = j("#activity-filter-select select").val();

			bp_activity_widget_post(type, filter);

			return false;
		}
	});

	j('#activity-filter-select select').change( function() {
		var selected_tab = j( '.' + j(this).parent().parent().parent().attr('class') + ' li.selected');
		var type = selected_tab.attr('id').substr( 9, selected_tab.attr('id').length );
		var filter = j(this).val();

		bp_activity_widget_post(type, filter);

		return false;
	});

	/* Stream event delegation */
	j('div.widget_bp_activity_widget').click( function(event) {
		var target = j(event.target).parent();

		/* Load more updates at the end of the page */
		if ( target.attr('class') == 'load-more' ) {
			j("li.load-more span.ajax-loader").show();

			var oldest_page = ( j("input#aw-oldestpage").val() * 1 ) + 1;

			j.post( ajaxurl, {
				action: 'aw_get_older_updates',
				'cookie': encodeURIComponent(document.cookie),
				'query_string': j("input#aw-querystring").val(),
				'acpage': oldest_page
			},
			function(response)
			{
				j("li.load-more span.ajax-loader").hide();

				/* Check for errors and append if found. */
				if ( response[0] + response[1] != '-1' ) {
					var response = response.split('||');
					j("input#aw-querystring").val(response[0]);

					j("ul.activity-list").append(response[1]);
					j("input#aw-oldestpage").val( oldest_page );
				}

				target.hide();
			});

			return false;
		}
	});

	function bp_activity_widget_post(type, filter) {
		if ( null == type )
			var type = 'all';

		if ( null == filter )
			var filter = '-1';

		/* Save the type and filter to a session cookie */
		j.cookie( 'bp_atype', type, null );
		j.cookie( 'bp_afilter', filter, null );

		/* Set the correct selected nav and filter */
		j('.widget_bp_activity_widget div.item-list-tabs li').each( function() {
			j(this).removeClass('selected');
		});
		j('li#activity-' + type).addClass('selected');
		j('#activity-filter-select select option[value=' + filter + ']').attr( 'selected', 'selected' );

		/* Reload the activity stream based on the selection */
		j('.widget_bp_activity_widget h2 span.ajax-loader').show();

		j.post( ajaxurl, {
			action: 'activity_widget_filter',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_activity_filter': j("input#_wpnonce_activity_filter").val(),
			'type': type,
			'filter': filter
		},
		function(response)
		{
			j('.widget_bp_activity_widget h2 span.ajax-loader').hide();

			/* Check for errors and append if found. */
			if ( response[0] + response[1] == '-1' ) {
				j('div.activity').fadeOut( 100, function() {
					j(this).html( response.substr( 2, response.length ) ).hide().fadeIn( 200 );
					j(this).fadeIn(100);
				});
			} else {
				var response = response.split('||');
				j("input#aw-querystring").val(response[0]);

				j('div.activity').fadeOut( 100, function() {
					j(this).html(response[1]);
					j(this).fadeIn(100);
				});
			}
		});
	}

	/* Admin Bar Javascript */
	j("#wp-admin-bar ul.main-nav li").mouseover( function() {
		j(this).addClass('sfhover');
	});

	j("#wp-admin-bar ul.main-nav li").mouseout( function() {
		j(this).removeClass('sfhover');
	});

});

/* jQuery Cookie plugin */
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}('o.5=B(9,b,2){6(h b!=\'E\'){2=2||{};6(b===n){b=\'\';2.3=-1}4 3=\'\';6(2.3&&(h 2.3==\'j\'||2.3.k)){4 7;6(h 2.3==\'j\'){7=w u();7.t(7.q()+(2.3*r*l*l*x))}m{7=2.3}3=\'; 3=\'+7.k()}4 8=2.8?\'; 8=\'+(2.8):\'\';4 a=2.a?\'; a=\'+(2.a):\'\';4 c=2.c?\'; c\':\'\';d.5=[9,\'=\',C(b),3,8,a,c].y(\'\')}m{4 e=n;6(d.5&&d.5!=\'\'){4 g=d.5.A(\';\');s(4 i=0;i<g.f;i++){4 5=o.z(g[i]);6(5.p(0,9.f+1)==(9+\'=\')){e=D(5.p(9.f+1));v}}}F e}};',42,42,'||options|expires|var|cookie|if|date|path|name|domain|value|secure|document|cookieValue|length|cookies|typeof||number|toUTCString|60|else|null|jQuery|substring|getTime|24|for|setTime|Date|break|new|1000|join|trim|split|function|encodeURIComponent|decodeURIComponent|undefined|return'.split('|'),0,{}))

/* ScrollTo plugin - just inline and minified */
;(function(d){var k=d.scrollTo=function(a,i,e){d(window).scrollTo(a,i,e)};k.defaults={axis:'xy',duration:parseFloat(d.fn.jquery)>=1.3?0:1};k.window=function(a){return d(window)._scrollable()};d.fn._scrollable=function(){return this.map(function(){var a=this,i=!a.nodeName||d.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!i)return a;var e=(a.contentWindow||a).document||a.ownerDocument||a;return d.browser.safari||e.compatMode=='BackCompat'?e.body:e.documentElement})};d.fn.scrollTo=function(n,j,b){if(typeof j=='object'){b=j;j=0}if(typeof b=='function')b={onAfter:b};if(n=='max')n=9e9;b=d.extend({},k.defaults,b);j=j||b.speed||b.duration;b.queue=b.queue&&b.axis.length>1;if(b.queue)j/=2;b.offset=p(b.offset);b.over=p(b.over);return this._scrollable().each(function(){var q=this,r=d(q),f=n,s,g={},u=r.is('html,body');switch(typeof f){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(f)){f=p(f);break}f=d(f,this);case'object':if(f.is||f.style)s=(f=d(f)).offset()}d.each(b.axis.split(''),function(a,i){var e=i=='x'?'Left':'Top',h=e.toLowerCase(),c='scroll'+e,l=q[c],m=k.max(q,i);if(s){g[c]=s[h]+(u?0:l-r.offset()[h]);if(b.margin){g[c]-=parseInt(f.css('margin'+e))||0;g[c]-=parseInt(f.css('border'+e+'Width'))||0}g[c]+=b.offset[h]||0;if(b.over[h])g[c]+=f[i=='x'?'width':'height']()*b.over[h]}else{var o=f[h];g[c]=o.slice&&o.slice(-1)=='%'?parseFloat(o)/100*m:o}if(/^\d+$/.test(g[c]))g[c]=g[c]<=0?0:Math.min(g[c],m);if(!a&&b.queue){if(l!=g[c])t(b.onAfterFirst);delete g[c]}});t(b.onAfter);function t(a){r.animate(g,j,b.easing,a&&function(){a.call(this,n,b)})}}).end()};k.max=function(a,i){var e=i=='x'?'Width':'Height',h='scroll'+e;if(!d(a).is('html,body'))return a[h]-d(a)[e.toLowerCase()]();var c='client'+e,l=a.ownerDocument.documentElement,m=a.ownerDocument.body;return Math.max(l[h],m[h])-Math.min(l[c],m[c])};function p(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);
jQuery.extend({easing:{easein:function(x,t,b,c,d){return c*(t/=d)*t+b},easeinout:function(x,t,b,c,d){if(t<d/2)return 2*c*t*t/(d*d)+b;var ts=t-d/2;return-2*c*ts*ts/(d*d)+2*c*ts/d+c/2+b},easeout:function(x,t,b,c,d){return-c*t*t/(d*d)+2*c*t/d+b},expoin:function(x,t,b,c,d){var flip=1;if(c<0){flip*=-1;c*=-1}return flip*(Math.exp(Math.log(c)/d*t))+b},expoout:function(x,t,b,c,d){var flip=1;if(c<0){flip*=-1;c*=-1}return flip*(-Math.exp(-Math.log(c)/d*(t-d))+c+1)+b},expoinout:function(x,t,b,c,d){var flip=1;if(c<0){flip*=-1;c*=-1}if(t<d/2)return flip*(Math.exp(Math.log(c/2)/(d/2)*t))+b;return flip*(-Math.exp(-2*Math.log(c/2)/d*(t-d))+c+1)+b},bouncein:function(x,t,b,c,d){return c-jQuery.easing['bounceout'](x,d-t,0,c,d)+b},bounceout:function(x,t,b,c,d){if((t/=d)<(1/2.75)){return c*(7.5625*t*t)+b}else if(t<(2/2.75)){return c*(7.5625*(t-=(1.5/2.75))*t+.75)+b}else if(t<(2.5/2.75)){return c*(7.5625*(t-=(2.25/2.75))*t+.9375)+b}else{return c*(7.5625*(t-=(2.625/2.75))*t+.984375)+b}},bounceinout:function(x,t,b,c,d){if(t<d/2)return jQuery.easing['bouncein'](x,t*2,0,c,d)*.5+b;return jQuery.easing['bounceout'](x,t*2-d,0,c,d)*.5+c*.5+b},elasin:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d)==1)return b+c;if(!p)p=d*.3;if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);return-(a*Math.pow(2,10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p))+b},elasout:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d)==1)return b+c;if(!p)p=d*.3;if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);return a*Math.pow(2,-10*t)*Math.sin((t*d-s)*(2*Math.PI)/p)+c+b},elasinout:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d/2)==2)return b+c;if(!p)p=d*(.3*1.5);if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);if(t<1)return-.5*(a*Math.pow(2,10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p))+b;return a*Math.pow(2,-10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p)*.5+c+b},backin:function(x,t,b,c,d){var s=1.70158;return c*(t/=d)*t*((s+1)*t-s)+b},backout:function(x,t,b,c,d){var s=1.70158;return c*((t=t/d-1)*t*((s+1)*t+s)+1)+b},backinout:function(x,t,b,c,d){var s=1.70158;if((t/=d/2)<1)return c/2*(t*t*(((s*=(1.525))+1)*t-s))+b;return c/2*((t-=2)*t*(((s*=(1.525))+1)*t+s)+2)+b},linear:function(x,t,b,c,d){return c*t/d+b}}});

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