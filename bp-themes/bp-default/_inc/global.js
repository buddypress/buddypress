// AJAX Functions
var j = jQuery;

// Global variable to prevent multiple AJAX requests
var bp_ajax_request = null;

j(document).ready( function() {
	/**** Page Load Actions *******************************************************/

	/* Activity filter and scope set */
	bp_init_activity();

	/* Object filter and scope set. */
	var objects = [ 'members', 'groups', 'blogs', 'forums' ];
	bp_init_objects( objects );

	/* Hide Forums Post Form */
	if ( j('div.forums').length )
		j('div#new-topic-post').hide();

	/* @mention Compose Scrolling */
	if ( j.query.get('r') ) {
		if ( j('textarea#whats-new').length ) {
			j.scrollTo( j('textarea#whats-new'), 500, { offset:-125, easing:'easeout' } );
			j('textarea#whats-new').focus();
		}
	}

	/* @mention username help button display */
	if ( j( 'span.highlight span' ).length )
		j( 'span.highlight span' ).toggle();

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

		/* Default POST values */
		var object = '';
		var item_id = j("#whats-new-post-in").val();
		var content = j("textarea#whats-new").val();

		/* Set object for non-profile posts */
		if ( item_id > 0 ) {
			object = j("#whats-new-post-object").val();
		}

		j.post( ajaxurl, {
			action: 'post_update',
			'cookie': encodeURIComponent(document.cookie),
			'_wpnonce_post_update': j("input#_wpnonce_post_update").val(),
			'content': content,
			'object': object,
			'item_id': item_id
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
				j("ul.activity-list li:first").addClass('new-update');
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
	j('div.activity-type-tabs').click( function(event) {
		var target = j(event.target).parent();

		if ( event.target.nodeName == 'STRONG' || event.target.nodeName == 'SPAN' )
			target = target.parent();
		else if ( event.target.nodeName != 'A' )
			return false;

		/* Reset the page */
		j.cookie( 'bp-activity-oldestpage', 1, {path: '/'} );

		/* Activity Stream Tabs */
		var scope = target.attr('id').substr( 9, target.attr('id').length );
		var filter = j("#activity-filter-select select").val();

		if ( scope == 'mentions' )
			j( 'li#' + target.attr('id') + ' a strong' ).remove();

		bp_activity_request(scope, filter, target);

		return false;
	});

	/* Activity filter select */
	j('#activity-filter-select select').change( function() {
		var selected_tab = j( 'div.activity-type-tabs li.selected' );

		if ( !selected_tab.length )
			var scope = null;
		else
			var scope = selected_tab.attr('id').substr( 9, selected_tab.attr('id').length );

		var filter = j(this).val();

		bp_activity_request(scope, filter);

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
						j('div.item-list-tabs ul li#activity-mentions').before( '<li id="activity-favorites"><a href="#">' + bp_terms_my_favs + ' (<span>0</span>)</a></li>');

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

		/* Delete activity stream items */
		if ( target.hasClass('delete-activity') ) {
			var li = target.parents('div.activity ul li');
			var id = li.attr('id').substr( 9, li.attr('id').length );
			var link_href = target.attr('href');

			var nonce = link_href.split('_wpnonce=');
				nonce = nonce[1];

			target.addClass('loading');

			j.post( ajaxurl, {
				action: 'delete_activity',
				'cookie': encodeURIComponent(document.cookie),
				'id': id,
				'_wpnonce': nonce
			},
			function(response) {
				target.removeClass('loading');

				if ( response[0] + response[1] == '-1' ) {
					li.prepend( response.substr( 2, response.length ) );
					li.children('div#message').hide().fadeIn(200);
				} else {
					li.slideUp(200);
				}
			});

			return false;
		}

		/* Load more updates at the end of the page */
		if ( target.parent().attr('class') == 'load-more' ) {
			j("li.load-more").addClass('loading');

			if ( null == j.cookie('bp-activity-oldestpage') )
				j.cookie('bp-activity-oldestpage', 1, {path: '/'} );

			var oldest_page = ( j.cookie('bp-activity-oldestpage') * 1 ) + 1;

			j.post( ajaxurl, {
				action: 'activity_get_older_updates',
				'cookie': encodeURIComponent(document.cookie),
				'page': oldest_page
			},
			function(response)
			{
				j("li.load-more").removeClass('loading');
				j.cookie( 'bp-activity-oldestpage', oldest_page, {path: '/'} );
				j("ul.activity-list").append(response.contents);

				target.parent().hide();
			}, 'json' );

			return false;
		}
	});

	/**** Activity Comments *******************************************************/

	/* Hide all activity comment forms */
	j('form.ac-form').hide();

	/* Hide excess comments */
	if ( j('div.activity-comments').length )
		bp_dtheme_hide_comments();

	/* Activity list event delegation */
	j('div.activity').click( function(event) {
		var target = j(event.target);

		/* Comment / comment reply links */
		if ( target.attr('class') == 'acomment-reply' || target.parent().attr('class') == 'acomment-reply' ) {
			if ( target.parent().attr('class') == 'acomment-reply' )
				target = target.parent();

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
							form.parent().parent().addClass('has-comments');
						}
					);
					j( 'form#' + form + ' textarea').val('');

					/* Increase the "Reply (X)" button count */
					j('li#activity-' + form_id[2] + ' a.acomment-reply span').html( Number( j('li#activity-' + form_id[2] + ' a.acomment-reply span').html() ) + 1 );

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

			target.addClass('loading');

			/* Remove any error messages */
			j('div.activity-comments ul div.error').remove();

			j.post( ajaxurl, {
				action: 'delete_activity',
				'cookie': encodeURIComponent(document.cookie),
				'_wpnonce': nonce,
				'id': comment_id
			},
			function(response)
			{
				/* Check for errors and append if found. */
				if ( response[0] + response[1] == '-1' ) {
					comment_li.prepend( response.substr( 2, response.length ) ).hide().fadeIn( 200 );
				} else {
					comment_li.fadeOut( 200, function() {
						var children = j( 'li#' + comment_li.attr('id') + ' ul:first' );

						/* Fade in sub comments if any were found. */
						if ( children.length )
							comment_li.parent().append( children.html() ).hide().fadeIn( 200 );
				 	});

					/* Decrease the "Reply (X)" button count */
					var parent_li = comment_li.parents('ul#activity-stream > li');
					j('li#' + parent_li.attr('id') + ' a.acomment-reply span').html( j('li#' + parent_li.attr('id') + ' a.acomment-reply span').html() - 1 );
				}
			});

			return false;
		}

		/* Showing hidden comments - pause for half a second */
		if ( target.parent().hasClass('show-all') ) {
			target.parent().addClass('loading');

			setTimeout( function() {
				target.parent().parent().children('li').fadeIn(200, function() {
					target.parent().remove();
				});
			}, 600 );

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

	/**** @mention username help tooltip **************************************/

	j('span.highlight span').click( function() {
		if ( !j('div.help').length ) {
			j(this).parent().after( '<div id="message" class="info help"><p>' + bp_terms_mention_explain + '</p></div>' );
			j('div.help').hide().slideDown(200);
		} else {
			j('div.help').hide().remove();
		}
	})

	/**** Directory Search ****************************************************/

	/* The search form on all directory pages */
	j('div.dir-search').click( function(event) {
		if ( j(this).hasClass('no-ajax') )
			return false;

		var target = j(event.target);

		if ( target.attr('type') == 'submit' ) {
			var css_id = j('div.item-list-tabs li.selected').attr('id').split( '-' );
			var object = css_id[0];

			bp_filter_request( object, j.cookie('bp-' + object + '-filter'), j.cookie('bp-' + object + '-scope') , 'div.' + object, target.parent().children('label').children('input').val(), 1, j.cookie('bp-' + object + '-extras') );

			return false;
		}
	});

	/**** Tabs and Filters ****************************************************/

	/* When a navigation tab is clicked - e.g. | All Groups | My Groups | */
	j('div.item-list-tabs').click( function(event) {
		if ( j(this).hasClass('no-ajax') )
			return;

		var target = j(event.target).parent();

		if ( 'LI' == event.target.parentNode.nodeName && !target.hasClass('last') ) {
			var css_id = target.attr('id').split( '-' );
			var object = css_id[0];

			if ( 'activity' == object )
				return false;

			var scope = css_id[1];
			var filter = j("#" + object + "-order-select select").val();
			var search_terms = j("#" + object + "_search").val();

			bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, j.cookie('bp-' + object + '-extras') );

			return false;
		}
	});

	/* When the filter select box is changed re-query */
	j('li.filter select').change( function() {
		if ( j('div.item-list-tabs li.selected').length )
			var el = j('div.item-list-tabs li.selected');
		else
			var el = j(this);

		var css_id = el.attr('id').split('-');
		var object = css_id[0];
		var scope = css_id[1];
		var filter = j(this).val();
		var search_terms = false;

		if ( j('div.dir-search input').length )
			search_terms = j('div.dir-search input').val();

		if ( 'friends' == object )
			object = 'members';

		bp_filter_request( object, filter, scope, 'div.' + object, search_terms, 1, j.cookie('bp-' + object + '-extras') );

		return false;
	});

	/* All pagination links run through this function */
	j('div#content').click( function(event) {
		var target = j(event.target);

		if ( target.hasClass('button') )
			return true;

		if ( target.parent().parent().hasClass('pagination') && !target.parent().parent().hasClass('no-ajax') ) {
			if ( target.hasClass('dots') || target.hasClass('current') )
				return false;

			if ( j('div.item-list-tabs li.selected').length )
				var el = j('div.item-list-tabs li.selected');
			else
				var el = j('li.filter select');

			var page_number = 1;
			var css_id = el.attr('id').split( '-' );
			var object = css_id[0];
			var search_terms = false;

			if ( j('div.dir-search input').length )
				search_terms = j('div.dir-search input').val();

			if ( j(target).hasClass('next') )
				var page_number = Number( j('div.pagination span.current').html() ) + 1;
			else if ( j(target).hasClass('prev') )
				var page_number = Number( j('div.pagination span.current').html() ) - 1;
			else
				var page_number = Number( j(target).html() );

			bp_filter_request( object, j.cookie('bp-' + object + '-filter'), j.cookie('bp-' + object + '-scope'), 'div.' + object, search_terms, page_number, j.cookie('bp-' + object + '-extras') );

			return false;
		}

	});

	/**** New Forum Directory Post **************************************/

	/* Hit the "New Topic" button on the forums directory page */
	j('a#new-topic-button').click( function() {
		if ( !j('div#new-topic-post').length )
			return false;

		if ( j('div#new-topic-post').is(":visible") )
			j('div#new-topic-post').slideUp(200);
		else
			j('div#new-topic-post').slideDown(200);

		return false;
	});

	/* Cancel the posting of a new forum topic */
	j('input#submit_topic_cancel').click( function() {
		if ( !j('div#new-topic-post').length )
			return false;

		j('div#new-topic-post').slideUp(200);
		return false;
	});

	/* Clicking a forum tag */
	j('div#forum-directory-tags a').click( function() {
		bp_filter_request( 'forums', 'tags', j.cookie('bp-forums-scope'), 'div.forums', j(this).html(), 1, j.cookie('bp-forums-extras') );
		return false;
	});

	/** Invite Friends Interface ****************************************/

	/* Select a user from the list of friends and add them to the invite list */
	j("div#invite-list input").click( function() {
		j('.ajax-loader').toggle();

		var friend_id = j(this).val();

		if ( j(this).attr('checked') == true )
			var friend_action = 'invite';
		else
			var friend_action = 'uninvite';

		j('div.item-list-tabs li.selected').addClass('loading');

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

			j('div.item-list-tabs li.selected').removeClass('loading');
		});
	});

	/* Remove a user from the list of users to invite to a group */
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

	/** Friendship Requests **************************************/

	/* Accept and Reject friendship request buttons */
	j("ul#friend-list a.accept, ul#friend-list a.reject").click( function() {
		var button = j(this);
		var li = j(this).parents('ul#friend-list li');
		var action_div = j(this).parents('li div.action');

		var id = li.attr('id').substr( 11, li.attr('id').length );
		var link_href = button.attr('href');

		var nonce = link_href.split('_wpnonce=');
			nonce = nonce[1];

		if ( j(this).hasClass('accept') ) {
			var action = 'accept_friendship';
			action_div.children('a.reject').css( 'visibility', 'hidden' );
		} else {
			var action = 'reject_friendship';
			action_div.children('a.accept').css( 'visibility', 'hidden' );
		}

		button.addClass('loading');

		j.post( ajaxurl, {
			action: action,
			'cookie': encodeURIComponent(document.cookie),
			'id': id,
			'_wpnonce': nonce
		},
		function(response) {
			button.removeClass('loading');

			if ( response[0] + response[1] == '-1' ) {
				li.prepend( response.substr( 2, response.length ) );
				li.children('div#message').hide().fadeIn(200);
			} else {
				button.fadeOut( 100, function() {
					if ( j(this).hasClass('accept') )
						j(this).html( bp_terms_accepted ).fadeIn(50);
					else
						j(this).html( bp_terms_rejected ).fadeIn(50);
				});
			}
		});

		return false;
	});

	/* Add / Remove friendship buttons */
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

	j('table tr, div.message-box, ul#topic-post-list li').each( function(i) {
		if ( i % 2 != 1 )
			j(this).addClass('alt');
	});

	/** Private Messaging ******************************************/

	/* AJAX send reply functionality */
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

	/* Marking private messages as read and unread */
	j("a#mark_as_read, a#mark_as_unread").click(function() {
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
			if(j(this).is(':checked')) {
				if ( j('tr#m-' + j(this).attr('value')).hasClass(currentClass) ) {
					checkboxes_tosend += j(this).attr('value');
					j('tr#m-' + j(this).attr('value')).removeClass(currentClass);
					j('tr#m-' + j(this).attr('value')).addClass(newClass);
					var thread_count = j('tr#m-' + j(this).attr('value') + ' td span.unread-count').html();

					j('tr#m-' + j(this).attr('value') + ' td span.unread-count').html(unreadCount);
					j('tr#m-' + j(this).attr('value') + ' td span.unread-count').css('display', unreadCountDisplay);
					var inboxcount = j('a#user-messages strong').html().substr( 1, j('a#user-messages strong').html().length );
					var inboxcount = inboxcount.substr( 0, inboxcount.length - 1 );

					if ( !inboxcount.length )
						inboxcount = 0;
					if ( parseInt(inboxcount) == inboxCount ) {
						j('a#user-messages strong').css('display', unreadCountDisplay);
						j('a#user-messages strong').html( '(' + unreadCount + ')' );
					} else {
						if ( 'read' == currentClass )
							j('a#user-messages strong').html('(' + ( parseInt(inboxcount) + 1 ) + ')');
						else
							j('a#user-messages strong').html('(' + ( parseInt(inboxcount) - thread_count ) + ')');
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
	});

	/* Selecting unread and read messages in inbox */
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

	/* Bulk delete messages */
	j("a#delete_inbox_messages").click( function() {
		checkboxes_tosend = '';
		checkboxes = j("#message-threads tr td input[type='checkbox']");

		j('div#message').remove();
		j(this).addClass('loading');

		j(checkboxes).each( function(i) {
			if( j(this).is(':checked') )
				checkboxes_tosend += j(this).attr('value') + ',';
		});

		if ( '' == checkboxes_tosend ) {
			j(this).removeClass('loading');
			return false;
		}

		j.post( ajaxurl, {
			action: 'messages_delete',
			'thread_ids': checkboxes_tosend
		}, function(response) {
			if ( response[0] + response[1] == "-1" ) {
				j('#message-threads').prepend( response.substr( 2, response.length ) );
			} else {
				j('#message-threads').before( '<div id="message" class="updated"><p>' + response + '</p></div>' );

				j(checkboxes).each( function(i) {
					if( j(this).is(':checked') )
						j(this).parent().parent().fadeOut(150);
				});
			}

			j('div#message').hide().slideDown(150);
			j("a#delete_inbox_messages").removeClass('loading');
		});
		return false;
	});

	/* Close site wide notices in the sidebar */
	j("a#close-notice").click( function() {
		j(this).addClass('loading');
		j('div#sidebar div.error').remove();

		j.post( ajaxurl, {
			action: 'messages_close_notice',
			'notice_id': j('.notice').attr('rel').substr( 2, j('.notice').attr('rel').length )
		},
		function(response) {
			j("a#close-notice").removeClass('loading');

			if ( response[0] + response[1] == '-1' ) {
				j('.notice').prepend( response.substr( 2, response.length ) );
				j( 'div#sidebar div.error').hide().fadeIn( 200 );
			} else {
				j('.notice').slideUp( 100 );
			}
		});
		return false;
	});

	/* Admin Bar Javascript */
	j("#wp-admin-bar ul.main-nav li").mouseover( function() {
		j(this).addClass('sfhover');
	});

	j("#wp-admin-bar ul.main-nav li").mouseout( function() {
		j(this).removeClass('sfhover');
	});

	/* Clear BP cookies on logout */
	j('a.logout').click( function() {
		j.cookie('bp-activity-scope', null, {path: '/'});
		j.cookie('bp-activity-filter', null, {path: '/'});
		j.cookie('bp-activity-oldestpage', null, {path: '/'});

		var objects = [ 'members', 'groups', 'blogs', 'forums' ];
		j(objects).each( function(i) {
			j.cookie('bp-' + objects[i] + '-scope', null, {path: '/'} );
			j.cookie('bp-' + objects[i] + '-filter', null, {path: '/'} );
			j.cookie('bp-' + objects[i] + '-extras', null, {path: '/'} );
		});
	});
});

/* Setup activity scope and filter based on the current cookie settings. */
function bp_init_activity() {
	/* Reset the page */
	j.cookie( 'bp-activity-oldestpage', 1, {path: '/'} );

	if ( null != j.cookie('bp-activity-filter') && j('#activity-filter-select').length )
		j('#activity-filter-select select option[value=' + j.cookie('bp-activity-filter') + ']').attr( 'selected', 'selected' );

	/* Activity Tab Set */
	if ( null != j.cookie('bp-activity-scope') && j('div.activity-type-tabs').length ) {
		j('div.activity-type-tabs li').each( function() {
			j(this).removeClass('selected');
		});
		j('li#activity-' + j.cookie('bp-activity-scope') + ', div.item-list-tabs li.current').addClass('selected');
	}
}

/* Setup object scope and filter based on the current cookie settings for the object. */
function bp_init_objects(objects) {
	j(objects).each( function(i) {
		if ( null != j.cookie('bp-' + objects[i] + '-filter') && j('li#' + objects[i] + '-order-select select').length )
			j('li#' + objects[i] + '-order-select select option[value=' + j.cookie('bp-' + objects[i] + '-filter') + ']').attr( 'selected', 'selected' );

		if ( null != j.cookie('bp-' + objects[i] + '-scope') && j('div.' + objects[i]).length ) {
			j('div.item-list-tabs li').each( function() {
				j(this).removeClass('selected');
			});
			j('div.item-list-tabs li#' + objects[i] + '-' + j.cookie('bp-' + objects[i] + '-scope') + ', div.item-list-tabs#object-nav li.current').addClass('selected');
		}
	});
}

/* Filter the current content list (groups/members/blogs/topics) */
function bp_filter_request( object, filter, scope, target, search_terms, page, extras ) {
	if ( 'activity' == object )
		return false;

	if ( j.query.get('s') )
		search_terms = j.query.get('s');

	if ( null == scope )
		scope = 'all';

	/* Save the settings we want to remain persistent to a cookie */
	j.cookie( 'bp-' + object + '-scope', scope, {path: '/'} );
	j.cookie( 'bp-' + object + '-filter', filter, {path: '/'} );
	j.cookie( 'bp-' + object + '-extras', extras, {path: '/'} );

	/* Set the correct selected nav and filter */
	j('div.item-list-tabs li').each( function() {
		j(this).removeClass('selected');
	});
	j('div.item-list-tabs li#' + object + '-' + scope + ', div.item-list-tabs#object-nav li.current').addClass('selected');
	j('div.item-list-tabs li.selected').addClass('loading');
	j('div.item-list-tabs select option[value=' + filter + ']').attr( 'selected', 'selected' );

	if ( 'friends' == object )
		object = 'members';

	if ( bp_ajax_request )
		bp_ajax_request.abort();

	bp_ajax_request = j.post( ajaxurl, {
		action: object + '_filter',
		'cookie': encodeURIComponent(document.cookie),
		'object': object,
		'filter': filter,
		'search_terms': search_terms,
		'scope': scope,
		'page': page,
		'extras': extras
	},
	function(response)
	{
		j(target).fadeOut( 100, function() {
			j(this).html(response);
			j(this).fadeIn(100);
	 	});
		j('div.item-list-tabs li.selected').removeClass('loading');
	});
}

/* Activity Loop Requesting */
function bp_activity_request(scope, filter) {
	/* Save the type and filter to a session cookie */
	j.cookie( 'bp-activity-scope', scope, {path: '/'} );
	j.cookie( 'bp-activity-filter', filter, {path: '/'} );
	j.cookie( 'bp-activity-oldestpage', 1 );

	/* Remove selected and loading classes from tabs */
	j('div.item-list-tabs li').each( function() {
		j(this).removeClass('selected loading');
	});
	/* Set the correct selected nav and filter */
	j('li#activity-' + scope + ', div.item-list-tabs li.current').addClass('selected');
	j('div#object-nav.item-list-tabs li.selected, div.activity-type-tabs li.selected').addClass('loading');
	j('#activity-filter-select select option[value=' + filter + ']').attr( 'selected', 'selected' );

	/* Reload the activity stream based on the selection */
	j('.widget_bp_activity_widget h2 span.ajax-loader').show();

	if ( bp_ajax_request )
		bp_ajax_request.abort();

	bp_ajax_request = j.post( ajaxurl, {
		action: 'activity_widget_filter',
		'cookie': encodeURIComponent(document.cookie),
		'_wpnonce_activity_filter': j("input#_wpnonce_activity_filter").val(),
		'scope': scope,
		'filter': filter
	},
	function(response)
	{
		j('.widget_bp_activity_widget h2 span.ajax-loader').hide();

		j('div.activity').fadeOut( 100, function() {
			j(this).html(response.contents);
			j(this).fadeIn(100);
		});

		/* Update the feed link */
		if ( null != response.feed_url )
			j('.directory div#subnav li.feed a, .home-page div#subnav li.feed a').attr('href', response.feed_url);

		j('div.item-list-tabs li.selected').removeClass('loading');

		/* Selectively hide comments */
		bp_dtheme_hide_comments();

	}, 'json' );
}

/* Hide long lists of activity comments, only show the latest five root comments. */
function bp_dtheme_hide_comments() {
	var comments_divs = j('div.activity-comments');

	if ( !comments_divs.length )
		return false;

	comments_divs.each( function() {
		if ( j(this).children('ul').children('li').length < 5 ) return;

		var comments_div = j(this);
		var parent_li = comments_div.parents('ul#activity-stream > li');
		var comment_lis = j(this).children('ul').children('li');
		var comment_count = ' ';

		if ( j('li#' + parent_li.attr('id') + ' a.acomment-reply span').length )
			var comment_count = j('li#' + parent_li.attr('id') + ' a.acomment-reply span').html();

		comment_lis.each( function(i) {
			/* Show the latest 5 root comments */
			if ( i < comment_lis.length - 5 ) {
				j(this).addClass('hidden');
				j(this).toggle();

				if ( !i )
					j(this).before( '<li class="show-all"><a href="#' + parent_li.attr('id') + '/show-all/" title="' + bp_terms_show_all_comments + '">' + bp_terms_show_all + ' ' + comment_count + ' ' + bp_terms_comments + '</a></li>' );
			}
		});

	});
}

/* Helper Functions */

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
jQuery.cookie=function(name,value,options){if(typeof value!='undefined'){options=options||{};if(value===null){value='';options.expires=-1;}var expires='';if(options.expires&&(typeof options.expires=='number'||options.expires.toUTCString)){var date;if(typeof options.expires=='number'){date=new Date();date.setTime(date.getTime()+(options.expires*24*60*60*1000));}else{date=options.expires;}expires='; expires='+date.toUTCString();}var path=options.path?'; path='+(options.path):'';var domain=options.domain?'; domain='+(options.domain):'';var secure=options.secure?'; secure':'';document.cookie=[name,'=',encodeURIComponent(value),expires,path,domain,secure].join('');}else{var cookieValue=null;if(document.cookie&&document.cookie!=''){var cookies=document.cookie.split(';');for(var i=0;i<cookies.length;i++){var cookie=jQuery.trim(cookies[i]);if(cookie.substring(0,name.length+1)==(name+'=')){cookieValue=decodeURIComponent(cookie.substring(name.length+1));break;}}}return cookieValue;}};

/* jQuery querystring plugin */
eval(function(p,a,c,k,e,d){e=function(c){return(c<a?'':e(parseInt(c/a)))+((c=c%a)>35?String.fromCharCode(c+29):c.toString(36))};if(!''.replace(/^/,String)){while(c--){d[e(c)]=k[c]||e(c)}k=[function(e){return d[e]}];e=function(){return'\\w+'};c=1};while(c--){if(k[c]){p=p.replace(new RegExp('\\b'+e(c)+'\\b','g'),k[c])}}return p}('M 6(A){4 $11=A.11||\'&\';4 $V=A.V===r?r:j;4 $1p=A.1p===r?\'\':\'[]\';4 $13=A.13===r?r:j;4 $D=$13?A.D===j?"#":"?":"";4 $15=A.15===r?r:j;v.1o=M 6(){4 f=6(o,t){8 o!=1v&&o!==x&&(!!t?o.1t==t:j)};4 14=6(1m){4 m,1l=/\\[([^[]*)\\]/g,T=/^([^[]+)(\\[.*\\])?$/.1r(1m),k=T[1],e=[];19(m=1l.1r(T[2]))e.u(m[1]);8[k,e]};4 w=6(3,e,7){4 o,y=e.1b();b(I 3!=\'X\')3=x;b(y===""){b(!3)3=[];b(f(3,L)){3.u(e.h==0?7:w(x,e.z(0),7))}n b(f(3,1a)){4 i=0;19(3[i++]!=x);3[--i]=e.h==0?7:w(3[i],e.z(0),7)}n{3=[];3.u(e.h==0?7:w(x,e.z(0),7))}}n b(y&&y.T(/^\\s*[0-9]+\\s*$/)){4 H=1c(y,10);b(!3)3=[];3[H]=e.h==0?7:w(3[H],e.z(0),7)}n b(y){4 H=y.B(/^\\s*|\\s*$/g,"");b(!3)3={};b(f(3,L)){4 18={};1w(4 i=0;i<3.h;++i){18[i]=3[i]}3=18}3[H]=e.h==0?7:w(3[H],e.z(0),7)}n{8 7}8 3};4 C=6(a){4 p=d;p.l={};b(a.C){v.J(a.Z(),6(5,c){p.O(5,c)})}n{v.J(1u,6(){4 q=""+d;q=q.B(/^[?#]/,\'\');q=q.B(/[;&]$/,\'\');b($V)q=q.B(/[+]/g,\' \');v.J(q.Y(/[&;]/),6(){4 5=1e(d.Y(\'=\')[0]||"");4 c=1e(d.Y(\'=\')[1]||"");b(!5)8;b($15){b(/^[+-]?[0-9]+\\.[0-9]*$/.1d(c))c=1A(c);n b(/^[+-]?[0-9]+$/.1d(c))c=1c(c,10)}c=(!c&&c!==0)?j:c;b(c!==r&&c!==j&&I c!=\'1g\')c=c;p.O(5,c)})})}8 p};C.1H={C:j,1G:6(5,1f){4 7=d.Z(5);8 f(7,1f)},1h:6(5){b(!f(5))8 d.l;4 K=14(5),k=K[0],e=K[1];4 3=d.l[k];19(3!=x&&e.h!=0){3=3[e.1b()]}8 I 3==\'1g\'?3:3||""},Z:6(5){4 3=d.1h(5);b(f(3,1a))8 v.1E(j,{},3);n b(f(3,L))8 3.z(0);8 3},O:6(5,c){4 7=!f(c)?x:c;4 K=14(5),k=K[0],e=K[1];4 3=d.l[k];d.l[k]=w(3,e.z(0),7);8 d},w:6(5,c){8 d.N().O(5,c)},1s:6(5){8 d.O(5,x).17()},1z:6(5){8 d.N().1s(5)},1j:6(){4 p=d;v.J(p.l,6(5,7){1y p.l[5]});8 p},1F:6(Q){4 D=Q.B(/^.*?[#](.+?)(?:\\?.+)?$/,"$1");4 S=Q.B(/^.*?[?](.+?)(?:#.+)?$/,"$1");8 M C(Q.h==S.h?\'\':S,Q.h==D.h?\'\':D)},1x:6(){8 d.N().1j()},N:6(){8 M C(d)},17:6(){6 F(G){4 R=I G=="X"?f(G,L)?[]:{}:G;b(I G==\'X\'){6 1k(o,5,7){b(f(o,L))o.u(7);n o[5]=7}v.J(G,6(5,7){b(!f(7))8 j;1k(R,5,F(7))})}8 R}d.l=F(d.l);8 d},1B:6(){8 d.N().17()},1D:6(){4 i=0,U=[],W=[],p=d;4 16=6(E){E=E+"";b($V)E=E.B(/ /g,"+");8 1C(E)};4 1n=6(1i,5,7){b(!f(7)||7===r)8;4 o=[16(5)];b(7!==j){o.u("=");o.u(16(7))}1i.u(o.P(""))};4 F=6(R,k){4 12=6(5){8!k||k==""?[5].P(""):[k,"[",5,"]"].P("")};v.J(R,6(5,7){b(I 7==\'X\')F(7,12(5));n 1n(W,12(5),7)})};F(d.l);b(W.h>0)U.u($D);U.u(W.P($11));8 U.P("")}};8 M C(1q.S,1q.D)}}(v.1o||{});',62,106,'|||target|var|key|function|value|return|||if|val|this|tokens|is||length||true|base|keys||else||self||false|||push|jQuery|set|null|token|slice|settings|replace|queryObject|hash|str|build|orig|index|typeof|each|parsed|Array|new|copy|SET|join|url|obj|search|match|queryString|spaces|chunks|object|split|get||separator|newKey|prefix|parse|numbers|encode|COMPACT|temp|while|Object|shift|parseInt|test|decodeURIComponent|type|number|GET|arr|EMPTY|add|rx|path|addFields|query|suffix|location|exec|REMOVE|constructor|arguments|undefined|for|empty|delete|remove|parseFloat|compact|encodeURIComponent|toString|extend|load|has|prototype'.split('|'),0,{}))
