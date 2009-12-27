// AJAX Functions

jQuery(document).ready( function() {
	var j = jQuery;

	/**** Page Load Actions *******************************************************/

	/* Activity */
	if ( j('div.activity').length && !j('div.activity').hasClass('no-ajax') )
		bp_activity_request( j.cookie('bp-activity-type'), j.cookie('bp-activity-filter') );

	/* Members */
	if ( j('div.members').length )
		bp_filter_request( j.cookie('bp-members-type'), j.cookie('bp-members-filter'), 'members', 'div.members', j.cookie('bp-members-page'), j.cookie('bp-members-search-terms') );

	/* Groups */
	if ( j('div.groups').length )
		bp_filter_request( j.cookie('bp-groups-type'), j.cookie('bp-groups-filter'), 'groups', 'div.groups', j.cookie('bp-groups-page'), j.cookie('bp-groups-search-terms') );

	/* Blogs */
	if ( j('div.blogs').length )
		bp_filter_request( j.cookie('bp-blogs-type'), j.cookie('bp-blogs-filter'), 'blogs', 'div.blogs', j.cookie('bp-blogs-page'), j.cookie('bp-blogs-search-terms') );

	/* Forums */
	if ( j('div.forums').length ) {
		j('div#new-topic-post').hide();
		bp_filter_request( j.cookie('bp-forums-type'), j.cookie('bp-forums-filter'), 'forums', 'div.forums', j.cookie('bp-forums-page'), j.cookie('bp-forums-search-terms') );
	}

	/**** Activity Posting ********************************************************/

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
					j("div#message").slideUp(100).remove();
					j("div.activity").append( '<ul id="activity-stream" class="activity-list item-list">' );
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
			 target.attr('id') == 'activity-groups' ||
			 target.attr('id') == 'activity-favorites' ) {

			var type = target.attr('id').substr( 9, target.attr('id').length );
			var filter = j("#activity-filter-select select").val();

			bp_activity_request(type, filter, target);

			return false;
		}
	});

	j('#activity-filter-select select').change( function() {
		var selected_tab = j( '.' + j(this).parent().parent().parent().attr('class') + ' li.selected');

		if ( !selected_tab.length )
			var type = 'all';
		else
			var type = selected_tab.attr('id').substr( 9, selected_tab.attr('id').length );

		var filter = j(this).val();

		bp_activity_request(type, filter);

		return false;
	});

	/* Stream event delegation */
	j('div.activity').click( function(event) {
		var target = j(event.target);

		/* Favoriting activity stream items */
		if ( target.attr('class') == 'fav' || target.attr('class') == 'unfav' ) {
			var type = target.attr('class')
			var parent = target.parent().parent().parent();
			var parent_id = parent.attr('id').substr( 9, parent.attr('id').length );

			target.addClass('loading');

			j.post( ajaxurl, {
				action: 'activity_mark_' + type,
				'cookie': encodeURIComponent(document.cookie),
				'id': parent_id
			},
			function(response) {
				target.removeClass('loading');

				target.fadeOut( 100, function() {
					j(this).html(response);
					j(this).fadeIn(100);
				});

				if ( 'fav' == type ) {
					if ( !j('div.item-list-tabs li#activity-favorites').length )
						j('div.item-list-tabs ul').append( '<li id="activity-favorites"><a href="">My Favorites (<span>0</span>)</a></li>');

					target.removeClass('fav');
					target.addClass('unfav');

					j('div.item-list-tabs ul li#activity-favorites span').html( Number( j('div.item-list-tabs ul li#activity-favorites span').html() ) + 1 );
				} else {
					target.removeClass('unfav');
					target.addClass('fav');

					j('div.item-list-tabs ul li#activity-favorites span').html( Number( j('div.item-list-tabs ul li#activity-favorites span').html() ) - 1 );

					if ( !Number( j('div.item-list-tabs ul li#activity-favorites span').html() ) ) {
						if ( j('div.item-list-tabs ul li#activity-favorites').hasClass('selected') )
							bp_activity_request( null, null );

						j('div.item-list-tabs ul li#activity-favorites').remove();
					}
				}

				if ( 'activity-favorites' == j( 'div.item-list-tabs li.selected').attr('id') )
					target.parent().parent().parent().slideUp(100);
			});

			return false;
		}

		/* Load more updates at the end of the page */
		if ( target.parent().attr('class') == 'load-more' ) {
			j("li.load-more span.ajax-loader").show();

			var oldest_page = ( j.cookie('bp-activity-oldestpage') * 1 ) + 1;

			j.post( ajaxurl, {
				action: 'activity_get_older_updates',
				'cookie': encodeURIComponent(document.cookie),
				'query_string': j.cookie('bp-activity-querystring'),
				'page': oldest_page
			},
			function(response)
			{
				j("li.load-more span.ajax-loader").hide();

				j.cookie( 'bp-activity-querystring', response.query_string );
				j.cookie( 'bp-activity-oldestpage', oldest_page );

				j("ul.activity-list").append(response.contents);

				target.hide();
			}, 'json' );

			return false;
		}
	});

	/* Activity Loop Requesting */
	function bp_activity_request(type, filter) {
		if ( null == type )
			var type = 'all';

		if ( null == filter )
			var filter = '-1';

		/* Save the type and filter to a session cookie */
		j.cookie( 'bp-activity-type', type, null );
		j.cookie( 'bp-activity-filter', filter, null );
		j.cookie( 'bp-activity-oldestpage', 1 );

		/* Set the correct selected nav and filter */
		j('div.item-list-tabs li').each( function() {
			j(this).removeClass('selected');
		});
		j('li#activity-' + type).addClass('selected');
		j('div.item-list-tabs li.selected, div.item-list-tabs li.current').addClass('loading');
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
			j.cookie( 'bp-activity-querystring', response.query_string );

			j('div.activity').fadeOut( 100, function() {
				j(this).html(response.contents);
				j(this).fadeIn(100);
			});

			j('div.item-list-tabs li.selected, div.item-list-tabs li.current').removeClass('loading');

		}, 'json' );
	}

	/**** Activity Comments *******************************************************/

	/* Hide all activity comment forms */
	j('form.ac-form').hide();

	/* Activity list event delegation */
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
			j('#ac-form-' + ids[2] + ' textarea').TextAreaExpander( 60, 1000 );

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
							form.parent().parent().addClass('has-comments');
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

	/* Escape Key Press for cancelling comment forms */
	j(document).keydown( function(e) {
		e = e || window.event;
		if (e.target)
			element = e.target;
		else if (e.srcElement)
			element = e.srcElement;

		if( element.nodeType == 3)
			element = element.parentNode;

		if( e.ctrlKey == true || e.altKey == true || e.metaKey == true )
			return;

		var keyCode = (e.keyCode) ? e.keyCode : e.which;

		if ( keyCode == 27 ) {
			if (element.tagName == 'TEXTAREA') {
				if ( j(element).attr('class') == 'ac-input' )
					j(element).parent().parent().parent().slideUp( 200 );
			}
		}
	});

	/**** Directory Search ****************************************************/

	j('div.dir-search').click( function(event) {
		var target = j(event.target);

		if ( target.attr('type') == 'submit' ) {
			var css_id = j('div.item-list-tabs li.selected').attr('id').split( '-' );
			var object = css_id[0];

			bp_filter_request( j.cookie('bp-' + object + '-type'), j.cookie('bp-' + object + '-filter'), object, 'div.' + object, 1, target.parent().children('label').children('input').val() );
		}

		return false;
	});

	/**** Tabs and Filters ****************************************************/

	j('div.item-list-tabs').click( function(event) {
		if ( j(this).hasClass('no-ajax') )
			return;

		var target = j(event.target).parent();

		if ( 'LI' == event.target.parentNode.nodeName && !target.hasClass('last') ) {
			var css_id = target.attr('id').split( '-' );
			var object = css_id[0];

			if ( 'activity' == object )
				return false;

			var type = css_id[1];
			var filter = j("#" + object + "-order-select select").val();
			var search_terms = j("#" + object + "_search").val();

			/* Set the correct selected nav */
			j('div.item-list-tabs li').each( function() {
				j(this).removeClass('selected');
			});
			j('li#' + object + '-' + filter).addClass('selected');

			bp_filter_request( type, filter, object, 'div.' + object, 1, search_terms );

			return false;
		}
	});

	j('li.filter select').change( function() {
		if ( j('div.item-list-tabs li.selected').length )
			var el = j('div.item-list-tabs li.selected');
		else
			var el = j(this);

		var css_id = el.attr('id').split('-');
		var object = css_id[0];
		var type = css_id[1];
		var filter = j(this).val();
		var search_terms = j("#" + object + "_search").val();

		bp_filter_request( type, filter, object, 'div.' + object, 1, search_terms );

		return false;
	});

	function bp_filter_request( type, filter, id, target, page, search_terms ) {
		if ( 'activity' == id )
			return false;

		if ( null == type )
			var type = 'all';

		if ( null == filter )
			var filter = 'active';

		if ( null == page )
			var page = 1;

		if ( null == search_terms )
			var search_terms = false;

		/* Save the type and filter to a session cookie */
		j.cookie( 'bp-' + id + '-type', type, null );
		j.cookie( 'bp-' + id + '-filter', filter, null );
		j.cookie( 'bp-' + id + '-page', page, null );
		j.cookie( 'bp-' + id + '-search-terms', search_terms, null );

		/* Set the correct selected nav and filter */
		j('div.item-list-tabs li').each( function() {
			j(this).removeClass('selected');
		});
		j('div.item-list-tabs li#' + id + '-' + type).addClass('selected');
		j('div.item-list-tabs li.selected, div.item-list-tabs li.current').addClass('loading');
		j('div.item-list-tabs select option[value=' + filter + ']').attr( 'selected', 'selected' );

		j.post( ajaxurl, {
			action: id + '_filter',
			'cookie': encodeURIComponent(document.cookie),
			'type': type,
			'filter': filter,
			'page': page,
			'search_terms': search_terms
		},
		function(response)
		{
			j(target).fadeOut( 100, function() {
				j(this).html(response);
				j(this).fadeIn(100);
		 	});
			j('div.item-list-tabs li.selected, div.item-list-tabs li.current').removeClass('loading');
		});
	}

	/* Pagination Links */
	j('div#content').click( function(event) {
		var target = j(event.target);

		if ( target.parent().parent().hasClass('pagination') ) {
			if ( j('div.item-list-tabs li.selected').length )
				var el = j('div.item-list-tabs li.selected');
			else
				var el = j('li.filter select');

			var page_number = 1;
			var css_id = el.attr('id').split( '-' );
			var object = css_id[0];

			if ( j(target).hasClass('next') )
				var page_number = Number( j('div.pagination span.current').html() ) + 1;
			else if ( j(target).hasClass('prev') )
				var page_number = Number( j('div.pagination span.current').html() ) - 1;
			else
				var page_number = Number( j(target).html() );

			bp_filter_request( j.cookie('bp-' + object + '-type'), j.cookie('bp-' + object + '-filter'), object, 'div.' + object, page_number, j.cookie('bp-' + object + '-search-terms') );

			return false;
		}

	});

	/**** New Forum Directory Post **************************************/

	j('a#new-topic-button').click( function() {
		if ( !j('div#new-topic-post').length )
			return false;

		if ( j('div#new-topic-post').is(":visible") )
			j('div#new-topic-post').slideUp(200);
		else
			j('div#new-topic-post').slideDown(200);

		return false;
	});

	j('input#submit_topic_cancel').click( function() {
		if ( !j('div#new-topic-post').length )
			return false;

		j('div#new-topic-post').slideUp(200);
		return false;
	});

	/** Invite Friends Interface ****************************************/

	j("div#invite-list input").click( function() {
		j('.ajax-loader').toggle();

		var friend_id = j(this).val();

		if ( j(this).attr('checked') == true )
			var friend_action = 'invite';
		else
			var friend_action = 'uninvite';

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
	});

	j("#friend-list li a.remove").live('click', function() {
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
	});

	/** Friendship Request Buttons **************************************/

	j("div.friendship-button a").live('click', function() {
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
	} );

	/** Group Join / Leave Buttons **************************************/

	j("div.group-button a").live('click', function() {
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

			if ( !j('body.directory').length )
				location.href = location.href;
			else {
				j(parentdiv).fadeOut(200,
					function() {
						parentdiv.fadeIn(200).html(response);
					}
				);
			}
		});
		return false;
	} );


	/** Button disabling ************************************************/

	j('div.pending').click(function() {
		return false;
	});

	/** Alternate Highlighting ******************************************/

	j('table tr').each( function(i) {
		if ( i % 2 == 1 )
			j(this).addClass('alt');
	});

	j('div.message-box, ul#topic-post-list li').each( function(i) {
		if ( i % 2 != 1 )
			j(this).addClass('alt');
	});

	/** Private Messaging ******************************************/

	j("input#send_reply_button").click(
		function() {
			j('form#send-reply span.ajax-loader').toggle();

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
				if ( response[0] + response[1] == "-1" ) {
					j('form#send-reply').prepend( response.substr( 2, response.length ) );
				} else {
					j('form#send-reply div#message').remove();
					j("#message_content").val('');
					j('form#send-reply').before( response );

					j("div.new-message").hide().slideDown( 200, function() {
						j('form#send-reply span.ajax-loader').toggle();
						j('div.new-message').removeClass('new-message');
					});

					j('div.message-box').each( function(i) {
						j(this).removeClass('alt');
						if ( i % 2 != 1 )
							j(this).addClass('alt');
					});
				}
			});

			return false;
		}
	);

	j("a#mark_as_read, a#mark_as_unread").click(
		function() {
			var checkboxes_tosend = '';
			var checkboxes = j("#message-threads tr td input[type='checkbox']");

			if ( 'mark_as_unread' == j(this).attr('id') ) {
				var currentClass = 'read'
				var newClass = 'unread'
				var unreadCount = 1;
				var inboxCount = 0;
				var unreadCountDisplay = 'inline';
				var action = 'messages_markunread';
			} else {
				var currentClass = 'unread'
				var newClass = 'read'
				var unreadCount = 0;
				var inboxCount = 1;
				var unreadCountDisplay = 'none';
				var action = 'messages_markread';
			}

			checkboxes.each( function(i) {
				if(checkboxes[i].checked) {
					if ( j('tr#m-' + checkboxes[i].value).hasClass(currentClass) ) {
						checkboxes_tosend += checkboxes[i].value;
						j('tr#m-' + checkboxes[i].value).removeClass(currentClass);
						j('tr#m-' + checkboxes[i].value).addClass(newClass);
						j('tr#m-' + checkboxes[i].value + ' td span.unread-count').html(unreadCount);
						j('tr#m-' + checkboxes[i].value + ' td span.unread-count').css('display', unreadCountDisplay);
						var inboxcount = j('.inbox-count').html();

						if ( parseInt(inboxcount) == inboxCount ) {
							j('.inbox-count').css('display', unreadCountDisplay);
							j('.inbox-count').html(unreadCount);
						} else {
							if ( 'read' == currentClass )
								j('.inbox-count').html(parseInt(inboxcount) + 1);
							else
								j('.inbox-count').html(parseInt(inboxcount) - 1);
						}

						if ( i != checkboxes.length - 1 ) {
							checkboxes_tosend += ','
						}
					}
				}
			});
			j.post( ajaxurl, {
				action: action,
				'thread_ids': checkboxes_tosend
			});
			return false;
		}
	);

	j("select#message-type-select").change(
		function() {
			var selection = j("select#message-type-select").val();
			var checkboxes = j("td input[type='checkbox']");
			checkboxes.each( function(i) {
				checkboxes[i].checked = "";
			});

			switch(selection) {
				case 'unread':
					var checkboxes = j("tr.unread td input[type='checkbox']");
				break;
				case 'read':
					var checkboxes = j("tr.read td input[type='checkbox']");
				break;
			}
			if ( selection != '' ) {
				checkboxes.each( function(i) {
					checkboxes[i].checked = "checked";
				});
			} else {
				checkboxes.each( function(i) {
					checkboxes[i].checked = "";
				});
			}
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

/* ScrollTo plugin - just inline and minified */
;(function(d){var k=d.scrollTo=function(a,i,e){d(window).scrollTo(a,i,e)};k.defaults={axis:'xy',duration:parseFloat(d.fn.jquery)>=1.3?0:1};k.window=function(a){return d(window)._scrollable()};d.fn._scrollable=function(){return this.map(function(){var a=this,i=!a.nodeName||d.inArray(a.nodeName.toLowerCase(),['iframe','#document','html','body'])!=-1;if(!i)return a;var e=(a.contentWindow||a).document||a.ownerDocument||a;return d.browser.safari||e.compatMode=='BackCompat'?e.body:e.documentElement})};d.fn.scrollTo=function(n,j,b){if(typeof j=='object'){b=j;j=0}if(typeof b=='function')b={onAfter:b};if(n=='max')n=9e9;b=d.extend({},k.defaults,b);j=j||b.speed||b.duration;b.queue=b.queue&&b.axis.length>1;if(b.queue)j/=2;b.offset=p(b.offset);b.over=p(b.over);return this._scrollable().each(function(){var q=this,r=d(q),f=n,s,g={},u=r.is('html,body');switch(typeof f){case'number':case'string':if(/^([+-]=)?\d+(\.\d+)?(px|%)?$/.test(f)){f=p(f);break}f=d(f,this);case'object':if(f.is||f.style)s=(f=d(f)).offset()}d.each(b.axis.split(''),function(a,i){var e=i=='x'?'Left':'Top',h=e.toLowerCase(),c='scroll'+e,l=q[c],m=k.max(q,i);if(s){g[c]=s[h]+(u?0:l-r.offset()[h]);if(b.margin){g[c]-=parseInt(f.css('margin'+e))||0;g[c]-=parseInt(f.css('border'+e+'Width'))||0}g[c]+=b.offset[h]||0;if(b.over[h])g[c]+=f[i=='x'?'width':'height']()*b.over[h]}else{var o=f[h];g[c]=o.slice&&o.slice(-1)=='%'?parseFloat(o)/100*m:o}if(/^\d+$/.test(g[c]))g[c]=g[c]<=0?0:Math.min(g[c],m);if(!a&&b.queue){if(l!=g[c])t(b.onAfterFirst);delete g[c]}});t(b.onAfter);function t(a){r.animate(g,j,b.easing,a&&function(){a.call(this,n,b)})}}).end()};k.max=function(a,i){var e=i=='x'?'Width':'Height',h='scroll'+e;if(!d(a).is('html,body'))return a[h]-d(a)[e.toLowerCase()]();var c='client'+e,l=a.ownerDocument.documentElement,m=a.ownerDocument.body;return Math.max(l[h],m[h])-Math.min(l[c],m[c])};function p(a){return typeof a=='object'?a:{top:a,left:a}}})(jQuery);
jQuery.extend({easing:{easein:function(x,t,b,c,d){return c*(t/=d)*t+b},easeinout:function(x,t,b,c,d){if(t<d/2)return 2*c*t*t/(d*d)+b;var ts=t-d/2;return-2*c*ts*ts/(d*d)+2*c*ts/d+c/2+b},easeout:function(x,t,b,c,d){return-c*t*t/(d*d)+2*c*t/d+b},expoin:function(x,t,b,c,d){var flip=1;if(c<0){flip*=-1;c*=-1}return flip*(Math.exp(Math.log(c)/d*t))+b},expoout:function(x,t,b,c,d){var flip=1;if(c<0){flip*=-1;c*=-1}return flip*(-Math.exp(-Math.log(c)/d*(t-d))+c+1)+b},expoinout:function(x,t,b,c,d){var flip=1;if(c<0){flip*=-1;c*=-1}if(t<d/2)return flip*(Math.exp(Math.log(c/2)/(d/2)*t))+b;return flip*(-Math.exp(-2*Math.log(c/2)/d*(t-d))+c+1)+b},bouncein:function(x,t,b,c,d){return c-jQuery.easing['bounceout'](x,d-t,0,c,d)+b},bounceout:function(x,t,b,c,d){if((t/=d)<(1/2.75)){return c*(7.5625*t*t)+b}else if(t<(2/2.75)){return c*(7.5625*(t-=(1.5/2.75))*t+.75)+b}else if(t<(2.5/2.75)){return c*(7.5625*(t-=(2.25/2.75))*t+.9375)+b}else{return c*(7.5625*(t-=(2.625/2.75))*t+.984375)+b}},bounceinout:function(x,t,b,c,d){if(t<d/2)return jQuery.easing['bouncein'](x,t*2,0,c,d)*.5+b;return jQuery.easing['bounceout'](x,t*2-d,0,c,d)*.5+c*.5+b},elasin:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d)==1)return b+c;if(!p)p=d*.3;if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);return-(a*Math.pow(2,10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p))+b},elasout:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d)==1)return b+c;if(!p)p=d*.3;if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);return a*Math.pow(2,-10*t)*Math.sin((t*d-s)*(2*Math.PI)/p)+c+b},elasinout:function(x,t,b,c,d){var s=1.70158;var p=0;var a=c;if(t==0)return b;if((t/=d/2)==2)return b+c;if(!p)p=d*(.3*1.5);if(a<Math.abs(c)){a=c;var s=p/4}else var s=p/(2*Math.PI)*Math.asin(c/a);if(t<1)return-.5*(a*Math.pow(2,10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p))+b;return a*Math.pow(2,-10*(t-=1))*Math.sin((t*d-s)*(2*Math.PI)/p)*.5+c+b},backin:function(x,t,b,c,d){var s=1.70158;return c*(t/=d)*t*((s+1)*t-s)+b},backout:function(x,t,b,c,d){var s=1.70158;return c*((t=t/d-1)*t*((s+1)*t+s)+1)+b},backinout:function(x,t,b,c,d){var s=1.70158;if((t/=d/2)<1)return c/2*(t*t*(((s*=(1.525))+1)*t-s))+b;return c/2*((t-=2)*t*(((s*=(1.525))+1)*t+s)+2)+b},linear:function(x,t,b,c,d){return c*t/d+b}}});

/* jQuery Cookie plugin */
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}('o.5=B(9,b,2){6(h b!=\'E\'){2=2||{};6(b===n){b=\'\';2.3=-1}4 3=\'\';6(2.3&&(h 2.3==\'j\'||2.3.k)){4 7;6(h 2.3==\'j\'){7=w u();7.t(7.q()+(2.3*r*l*l*x))}m{7=2.3}3=\'; 3=\'+7.k()}4 8=2.8?\'; 8=\'+(2.8):\'\';4 a=2.a?\'; a=\'+(2.a):\'\';4 c=2.c?\'; c\':\'\';d.5=[9,\'=\',C(b),3,8,a,c].y(\'\')}m{4 e=n;6(d.5&&d.5!=\'\'){4 g=d.5.A(\';\');s(4 i=0;i<g.f;i++){4 5=o.z(g[i]);6(5.p(0,9.f+1)==(9+\'=\')){e=D(5.p(9.f+1));v}}}F e}};',42,42,'||options|expires|var|cookie|if|date|path|name|domain|value|secure|document|cookieValue|length|cookies|typeof||number|toUTCString|60|else|null|jQuery|substring|getTime|24|for|setTime|Date|break|new|1000|join|trim|split|function|encodeURIComponent|decodeURIComponent|undefined|return'.split('|'),0,{}))