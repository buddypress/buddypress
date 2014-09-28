(function( $, undefined ) {
	var mentionsQueryCache = [],
		mentionsItem;

	/**
	 * Adds BuddyPress @mentions to form inputs.
	 *
	 * @param {array|object} options If array, becomes the suggestions' data source. If object, passed as config to $.atwho().
	 * @since BuddyPress (2.1.0)
	 */
	$.fn.bp_mentions = function( options ) {
		if ( $.isArray( options ) ) {
			options = { data: options };
		}

		/**
		 * Default options for at.js; see https://github.com/ichord/At.js/.
		 */
		var suggestionsDefaults = {
			delay:               200,
			hide_without_suffix: true,
			insert_tpl:          '</>${atwho-data-value}</>', // For contentEditable, the fake tags make jQuery insert a textNode.
			limit:               10,
			start_with_space:    false,
			suffix:              '',

			callbacks: {
				/**
				 * Custom filter to only match the start of spaced words.
				 * Based on the core/default one.
				 *
				 * @param {string} query
				 * @param {array} data
				 * @param {string} search_key
				 * @return {array}
				 * @since BuddyPress (2.1.0)
				 */
				filter: function( query, data, search_key ) {
					var item, _i, _len, _results = [],
					regxp = new RegExp( '^' + query + '| ' + query, 'ig' ); // start of string, or preceded by a space.

					for ( _i = 0, _len = data.length; _i < _len; _i++ ) {
						item = data[ _i ];
						if ( item[ search_key ].toLowerCase().match( regxp ) ) {
							_results.push( item );
						}
					}

					return _results;
				},

				/**
				 * Removes some spaces around highlighted string and tweaks regex to allow spaces
				 * (to match display_name). Based on the core default.
				 *
				 * @param {unknown} li
				 * @param {string} query
				 * @return {string}
				 * @since BuddyPress (2.1.0)
				 */
				highlighter: function( li, query ) {
					if ( ! query ) {
						return li;
					}

					var regexp = new RegExp( '>(\\s*|[\\w\\s]*)(' + this.at.replace( '+', '\\+') + '?' + query.replace( '+', '\\+' ) + ')([\\w ]*)\\s*<', 'ig' );
					return li.replace( regexp, function( str, $1, $2, $3 ) {
						return '>' + $1 + '<strong>' + $2 + '</strong>' + $3 + '<';
					});
				},

				/**
				 * Reposition the suggestion list dynamically.
				 *
				 * @param {unknown} offset
				 * @since BuddyPress (2.1.0)
				 */
				before_reposition: function( offset ) {
					var $view = $( '#atwho-ground-' + this.id + ' .atwho-view' ),
						caret = this.$inputor.caret( 'offset', { iframe: $( '#content_ifr' )[0] } ).left,
						move;

					// If the caret is past horizontal half, then flip it, yo.
					if ( caret > ( $( 'body' ).width() / 2 ) ) {
						$view.addClass( 'right' );
						move = caret - offset.left - this.view.$el.width();
					} else {
						$view.removeClass( 'right' );
						move = caret - offset.left + 1;
					}

					offset.top  += 1;
					offset.left += move;
				},

				/**
				 * Override default behaviour which inserts junk tags in the WordPress Visual editor.
				 *
				 * @param {unknown} $inputor Element which we're inserting content into.
				 * @param {string) content The content that will be inserted.
				 * @param {string) suffix Applied to the end of the content string.
				 * @return {string}
				 * @since BuddyPress (2.1.0)
				 */
				inserting_wrapper: function( $inputor, content, suffix ) {
					var new_suffix = ( suffix === '' ) ? suffix : suffix || ' ';
					return '' + content + new_suffix;
				}
			}
		},

		/**
		 * Default options for our @mentions; see https://github.com/ichord/At.js/.
		 */
		mentionsDefaults = {
			callbacks: {
				/**
				 * If there are no matches for the query in this.data, then query BuddyPress.
				 *
				 * @param {string} query Partial @mention to search for.
				 * @param {function} render_view Render page callback function.
				 * @since BuddyPress (2.1.0)
				 */
				remote_filter: function( query, render_view ) {
					var self = $( this );

					mentionsItem = mentionsQueryCache[ query ];
					if ( typeof mentionsItem === 'object' ) {
						render_view( mentionsItem );
						return;
					}

					if ( self.xhr ) {
						self.xhr.abort();
					}

					self.xhr = $.getJSON( ajaxurl, { 'action': 'bp_get_suggestions', 'term': query, 'type': 'members' } )
						/**
						 * Success callback for the @suggestions lookup.
						 *
						 * @param {object} response Details of users matching the query.
						 * @since BuddyPress (2.1.0)
						 */
						.done(function( response ) {
							if ( ! response.success ) {
								return;
							}

							var data = $.map( response.data,
								/**
								 * Create a composite index to determine ordering of results;
								 * nicename matches will appear on top.
								 *
								 * @param {array} suggestion A suggestion's original data.
								 * @return {array} A suggestion's new data.
								 * @since BuddyPress (2.1.0)
								 */
								function( suggestion ) {
									suggestion.search = suggestion.search || suggestion.ID + ' ' + suggestion.name;
									return suggestion;
								}
							);

							mentionsQueryCache[ query ] = data;
							render_view( data );
						});
				}
			},

			data: $.map( options.data,
				/**
				 * Create a composite index to search against of nicename + display name.
				 * This will also determine ordering of results, so nicename matches will appear on top.
				 *
				 * @param {array} suggestion A suggestion's original data.
				 * @return {array} A suggestion's new data.
				 * @since BuddyPress (2.1.0)
				 */
				function( suggestion ) {
					suggestion.search = suggestion.search || suggestion.ID + ' ' + suggestion.name;
					return suggestion;
				}
			),

			at:         '@',
			search_key: 'search',
			tpl:        '<li data-value="@${ID}"><img src="${image}" /><span class="username">@${ID}</span><small>${name}</small></li>'
		},

		opts = $.extend( true, {}, suggestionsDefaults, mentionsDefaults, options );
		return $.fn.atwho.call( this, opts );
	};

	$( document ).ready(function() {
		var users = [];

		if ( typeof window.BP_Suggestions === 'object' ) {
			users = window.BP_Suggestions.friends || users;
		}

		// Activity/reply, post comments, dashboard post 'text' editor.
		$( '.bp-suggestions, #comments form textarea, .wp-editor-area' ).bp_mentions( users );
	});
})( jQuery );
