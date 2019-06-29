/* global bp, BP_Nouveau, _, Backbone */
/* @since 3.0.0 */
/* @version 5.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {
	bp.Nouveau = bp.Nouveau || {};

	// Bail if not set
	if ( typeof bp.Nouveau.Activity === 'undefined' || typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	/**
	 * [Activity description]
	 * @type {Object}
	 */
	bp.Nouveau.Activity.postForm = {
		start: function() {
			this.views           = new Backbone.Collection();
			this.ActivityObjects = new bp.Collections.ActivityObjects();
			this.buttons         = new Backbone.Collection();

			this.postFormView();
		},

		postFormView: function() {
			// Do not carry on if the main element is not available.
			if ( ! $( '#bp-nouveau-activity-form' ).length ) {
				return;
			}

			// Create the BuddyPress Uploader
			var postForm = new bp.Views.PostForm();

			// Add it to views
			this.views.add( { id: 'post_form', view: postForm } );

			// Display it
			postForm.inject( '#bp-nouveau-activity-form' );
		}
	};

	if ( typeof bp.View === 'undefined' ) {
		// Extend wp.Backbone.View with .prepare() and .inject()
		bp.View = bp.Backbone.View.extend( {
			inject: function( selector ) {
				this.render();
				$(selector).html( this.el );
				this.views.ready();
			},

			prepare: function() {
				if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
					return this.model.toJSON();
				} else {
					return {};
				}
			}
		} );
	}

	/** Models ****************************************************************/

	// The Activity to post
	bp.Models.Activity = Backbone.Model.extend( {
		defaults: {
			user_id:   0,
			item_id:   0,
			object:   '',
			content:  ''
		}
	} );

	// Object, the activity is attached to (group or blog or any other)
	bp.Models.ActivityObject = Backbone.Model.extend( {
		defaults: {
			id          : 0,
			name        : '',
			avatar_url  : '',
			object_type : 'group'
		}
	} );

	/** Collections ***********************************************************/

	// Objects, the activity can be attached to (groups or blogs or any others)
	bp.Collections.ActivityObjects = Backbone.Collection.extend( {
		model: bp.Models.ActivityObject,

		sync: function( method, model, options ) {

			if ( 'read' === method ) {
				options = options || {};
				options.context = this;
				options.data = _.extend( options.data || {}, {
					action: 'bp_nouveau_get_activity_objects'
				} );

				return bp.ajax.send( options );
			}
		},

		parse: function( resp ) {
			if ( ! _.isArray( resp ) ) {
				resp = [resp];
			}

			return resp;
		}

	} );

	/** Views *****************************************************************/

	// Feedback messages
	bp.Views.activityFeedback = bp.View.extend( {
		tagName  : 'div',
		id       : 'message',
		template : bp.template( 'activity-post-form-feedback' ),

		initialize: function() {
			this.model = new Backbone.Model();

			if ( this.options.value ) {
				this.model.set( 'message', this.options.value, { silent: true } );
			}

			this.type  = 'info';

			if ( ! _.isUndefined( this.options.type ) && 'info' !== this.options.type ) {
				this.type = this.options.type;
			}

			this.el.className = 'bp-messages bp-feedback ' + this.type ;
		}
	} );

	// Regular input
	bp.Views.ActivityInput = bp.View.extend( {
		tagName  : 'input',

		attributes: {
			type : 'text'
		},

		initialize: function() {
			if ( ! _.isObject( this.options ) ) {
				return;
			}

			_.each( this.options, function( value, key ) {
				this.$el.prop( key, value );
			}, this );
		}
	} );

	// The content of the activity
	bp.Views.WhatsNew = bp.View.extend( {
		tagName   : 'textarea',
		className : 'bp-suggestions',
		id        : 'whats-new',

		attributes: {
			name         : 'whats-new',
			cols         : '50',
			rows         : '4',
			placeholder  : BP_Nouveau.activity.strings.whatsnewPlaceholder,
			'aria-label' : BP_Nouveau.activity.strings.whatsnewLabel
		},

		initialize: function() {
			this.on( 'ready', this.adjustContent, this );

			this.options.activity.on( 'change:content', this.resetContent, this );
		},

		adjustContent: function() {

			// First adjust layout
			this.$el.css( {
				resize: 'none',
				height: '50px'
			} );

			// Check for mention
			var	mention = bp.Nouveau.getLinkParams( null, 'r' ) || null;

			if ( ! _.isNull( mention ) ) {
				this.$el.text( '@' + _.escape( mention ) + ' ' );
				this.$el.focus();
			}
		},

		resetContent: function( activity ) {
			if ( _.isUndefined( activity ) ) {
				return;
			}

			this.$el.val( activity.get( 'content' ) );
		}
	} );

	bp.Views.WhatsNewPostIn = bp.View.extend( {
		tagName:   'select',
		id:        'whats-new-post-in',

		attributes: {
			name         : 'whats-new-post-in',
			'aria-label' : BP_Nouveau.activity.strings.whatsnewpostinLabel
		},

		events: {
			change: 'change'
		},

		keys: [],

		initialize: function() {
			this.model = new Backbone.Model();

			this.filters = this.options.filters || {};

			// Build `<option>` elements.
			this.$el.html( _.chain( this.filters ).map( function( filter, value ) {
				return {
					el: $( '<option></option>' ).val( value ).html( filter.text )[0],
					priority: filter.priority || 50
				};
			}, this ).sortBy( 'priority' ).pluck( 'el' ).value() );
		},

		change: function() {
			var filter = this.filters[ this.el.value ];
			if ( filter ) {
				this.model.set( { 'selected': this.el.value, 'placeholder': filter.autocomplete_placeholder } );
			}
		}
	} );

	bp.Views.Item = bp.View.extend( {
		tagName:   'li',
		className: 'bp-activity-object',
		template:  bp.template( 'activity-target-item' ),

		attributes: {
			role: 'checkbox'
		},

		initialize: function() {
			if ( this.model.get( 'selected' ) ) {
				this.el.className += ' selected';
			}
		},

		events: {
			click : 'setObject'
		},

		setObject:function( event ) {
			event.preventDefault();

			if ( true === this.model.get( 'selected' ) ) {
				this.model.clear();
			} else {
				this.model.set( 'selected', true );
			}
		}
	} );

	bp.Views.AutoComplete = bp.View.extend( {
		tagName : 'ul',
		id      : 'whats-new-post-in-box-items',

		events: {
			keyup :  'autoComplete'
		},

		initialize: function() {
			var autocomplete = new bp.Views.ActivityInput( {
				type        : 'text',
				id          : 'activity-autocomplete',
				placeholder : this.options.placeholder || ''
			} ).render();

			this.$el.prepend( $( '<li></li>' ).html( autocomplete.$el ) );

			this.on( 'ready', this.setFocus, this );
			this.collection.on( 'add', this.addItemView, this );
			this.collection.on( 'reset', this.cleanView, this );
		},

		setFocus: function() {
			this.$el.find( '#activity-autocomplete' ).focus();
		},

		addItemView: function( item ) {
			this.views.add( new bp.Views.Item( { model: item } ) );
		},

		autoComplete: function() {
			var search = $( '#activity-autocomplete' ).val();

			// Reset the collection before starting a new search
			this.collection.reset();

			if ( 2 > search.length ) {
				return;
			}

			this.collection.fetch( {
				data: {
					type   : this.options.type,
					search : search,
					nonce  : BP_Nouveau.nonces.activity
				},
				success : _.bind( this.itemFetched, this ),
				error   : _.bind( this.itemFetched, this )
			} );
		},

		itemFetched: function( items ) {
			if ( ! items.length ) {
				this.cleanView();
			}
		},

		cleanView: function() {
			_.each( this.views._views[''], function( view ) {
					view.remove();
			} );
		}
	} );

	bp.Views.FormAvatar = bp.View.extend( {
		tagName  : 'div',
		id       : 'whats-new-avatar',
		template : bp.template( 'activity-post-form-avatar' ),

		initialize: function() {
			this.model = new Backbone.Model( _.pick( BP_Nouveau.activity.params, [
				'user_id',
				'avatar_url',
				'avatar_width',
				'avatar_height',
				'avatar_alt',
				'user_domain'
			] ) );

			if ( this.model.has( 'avatar_url' ) ) {
				this.model.set( 'display_avatar', true );
			}
		}
	} );

	bp.Views.FormContent = bp.View.extend( {
		tagName  : 'div',
		id       : 'whats-new-content',

		initialize: function() {
			this.$el.html( $('<div></div>' ).prop( 'id', 'whats-new-textarea' ) );
			this.views.set( '#whats-new-textarea', new bp.Views.WhatsNew( { activity: this.options.activity } ) );
		}
	} );

	bp.Views.FormOptions = bp.View.extend( {
		tagName  : 'div',
		id       : 'whats-new-options',
		template : bp.template( 'activity-post-form-options' )
	} );

	bp.Views.BeforeFormInputs = bp.View.extend( {
		tagName  : 'div',
		template : bp.template( 'activity-before-post-form-inputs' )
	} );

	bp.Views.FormTarget = bp.View.extend( {
		tagName   : 'div',
		id        : 'whats-new-post-in-box',
		className : 'in-profile',

		initialize: function() {
			var select = new bp.Views.WhatsNewPostIn( { filters: BP_Nouveau.activity.params.objects } );
			this.views.add( select );

			select.model.on( 'change', this.attachAutocomplete, this );
			bp.Nouveau.Activity.postForm.ActivityObjects.on( 'change:selected', this.postIn, this );
		},

		attachAutocomplete: function( model ) {
			if ( 0 !== bp.Nouveau.Activity.postForm.ActivityObjects.models.length ) {
				bp.Nouveau.Activity.postForm.ActivityObjects.reset();
			}

			// Clean up views
			_.each( this.views._views[''], function( view ) {
				if ( ! _.isUndefined( view.collection ) ) {
					view.remove();
				}
			} );

			if ( 'profile' !== model.get( 'selected') ) {
				this.views.add( new bp.Views.AutoComplete( {
					collection:   bp.Nouveau.Activity.postForm.ActivityObjects,
					type:         model.get( 'selected' ),
					placeholder : model.get( 'placeholder' )
				} ) );

				// Set the object type
				this.model.set( 'object', model.get( 'selected' ) );

			} else {
				this.model.set( { object: 'user', item_id: 0 } );
			}

			this.updateDisplay();
		},

		postIn: function( model ) {
			if ( _.isUndefined( model.get( 'id' ) ) ) {
				// Reset the item id
				this.model.set( 'item_id', 0 );

				// When the model has been cleared, Attach Autocomplete!
				this.attachAutocomplete( new Backbone.Model( { selected: this.model.get( 'object' ) } ) );
				return;
			}

			// Set the item id for the selected object
			this.model.set( 'item_id', model.get( 'id' ) );

			// Set the view to the selected object
			this.views.set( '#whats-new-post-in-box-items', new bp.Views.Item( { model: model } ) );
		},

		updateDisplay: function() {
			if ( 'user' !== this.model.get( 'object' ) ) {
				this.$el.removeClass( );
			} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
				this.$el.addClass( 'in-profile' );
			}
		}
	} );

	/**
	 * Now build the buttons!
	 * @type {[type]}
	 */
	bp.Views.FormButtons = bp.View.extend( {
		tagName : 'div',
		id      : 'whats-new-actions',

		initialize: function() {
			this.views.add( new bp.View( { tagName: 'ul', id: 'whats-new-buttons' } ) );

			_.each( this.collection.models, function( button ) {
				this.addItemView( button );
			}, this );

			this.collection.on( 'change:active', this.isActive, this );
		},

		addItemView: function( button ) {
			this.views.add( '#whats-new-buttons', new bp.Views.FormButton( { model: button } ) );
		},

		isActive: function( button ) {
			// Clean up views
			_.each( this.views._views[''], function( view, index ) {
				if ( 0 !== index ) {
					view.remove();
				}
			} );

			// Then loop threw all buttons to update their status
			if ( true === button.get( 'active' ) ) {
				_.each( this.views._views['#whats-new-buttons'], function( view ) {
					if ( view.model.get( 'id') !== button.get( 'id' ) ) {
						// Silently update the model
						view.model.set( 'active', false, { silent: true } );

						// Remove the active class
						view.$el.removeClass( 'active' );

						// Trigger an even to let Buttons reset
						// their modifications to the activity model
						this.collection.trigger( 'reset:' + view.model.get( 'id' ), this.model );
					}
				}, this );

				// Tell the active Button to load its content
				this.collection.trigger( 'display:' + button.get( 'id' ), this );

			// Trigger an even to let Buttons reset
			// their modifications to the activity model
			} else {
				this.collection.trigger( 'reset:' + button.get( 'id' ), this.model );
			}
		}
	} );

	bp.Views.FormButton = bp.View.extend( {
		tagName   : 'li',
		className : 'whats-new-button',
		template  : bp.template( 'activity-post-form-buttons' ),

		events: {
			click : 'setActive'
		},

		setActive: function( event ) {
			var isActive = this.model.get( 'active' ) || false;

			// Stop event propagation
			event.preventDefault();

			if ( false === isActive ) {
				this.$el.addClass( 'active' );
				this.model.set( 'active', true );
			} else {
				this.$el.removeClass( 'active' );
				this.model.set( 'active', false );
			}
		}
	} );

	bp.Views.FormSubmit = bp.View.extend( {
		tagName   : 'div',
		id        : 'whats-new-submit',
		className : 'in-profile',

		initialize: function() {
			var reset = new bp.Views.ActivityInput( {
				type  : 'reset',
				id    : 'aw-whats-new-reset',
				className : 'text-button small',
				value : BP_Nouveau.activity.strings.cancelButton
			} );

			var submit = new bp.Views.ActivityInput( {
				type  : 'submit',
				id    : 'aw-whats-new-submit',
				className : 'button',
				name  : 'aw-whats-new-submit',
				value : BP_Nouveau.activity.strings.postUpdateButton
			} );

			this.views.set( [ submit, reset ] );

			this.model.on( 'change:object', this.updateDisplay, this );
		},

		updateDisplay: function( model ) {
			if ( _.isUndefined( model ) ) {
				return;
			}

			if ( 'user' !== model.get( 'object' ) ) {
				this.$el.removeClass( 'in-profile' );
			} else if ( ! this.$el.hasClass( 'in-profile' ) ) {
				this.$el.addClass( 'in-profile' );
			}
		}
	} );

	bp.Views.PostForm = bp.View.extend( {
		tagName   : 'form',
		className : 'activity-form',
		id        : 'whats-new-form',

		attributes: {
			name   : 'whats-new-form',
			method : 'post'
		},

		events: {
			'focus #whats-new' : 'displayFull',
			'reset'            : 'resetForm',
			'submit'           : 'postUpdate',
			'keydown'          : 'postUpdate'
		},

		initialize: function() {
			this.model = new bp.Models.Activity( _.pick(
				BP_Nouveau.activity.params,
				['user_id', 'item_id', 'object' ]
			) );
			this.options.backcompat = BP_Nouveau.activity.params.backcompat;
			var staticViews = [
				new bp.Views.FormAvatar(),
				new bp.Views.FormContent( { activity: this.model } )
			];

			// Backcompat to take the `bp_before_activity_post_form` action in account.
			if ( true === this.options.backcompat.before_post_form ) {
				staticViews.unshift( new bp.Views.BeforeFormInputs() );
			}

			// Clone the model to set the resetted one
			this.resetModel = this.model.clone();

			this.views.set( staticViews );

			this.model.on( 'change:errors', this.displayFeedback, this );
		},

		displayFull: function( event ) {
			var numStaticViews = true === this.options.backcompat.before_post_form ? 3 : 2;

			// Remove feedback.
			this.cleanFeedback();

			if ( numStaticViews !== this.views._views[''].length ) {
				return;
			}

			$( event.target ).css( {
				resize : 'vertical',
				height : 'auto'
			} );

			this.$el.addClass( 'activity-form-expanded' );

			// Add the container view for buttons or custom fields.
			if ( true === this.options.backcompat.post_form_options ) {
				this.views.add( new bp.Views.FormOptions( { model: this.model } ) );
			} else {
				this.views.add( new bp.View( { id: 'whats-new-options' } ) );
			}

			// Attach buttons
			if ( ! _.isUndefined( BP_Nouveau.activity.params.buttons ) ) {
				// Global
				bp.Nouveau.Activity.postForm.buttons.set( BP_Nouveau.activity.params.buttons );
				this.views.add( '#whats-new-options', new bp.Views.FormButtons( { collection: bp.Nouveau.Activity.postForm.buttons, model: this.model } ) );
			}

			// Select box for the object
			if ( ! _.isUndefined( BP_Nouveau.activity.params.objects ) && 1 < _.keys( BP_Nouveau.activity.params.objects ).length ) {
				this.views.add( '#whats-new-options', new bp.Views.FormTarget( { model: this.model } ) );
			}

			this.views.add( '#whats-new-options', new bp.Views.FormSubmit( { model: this.model } ) );
		},

		resetForm: function() {
			var self = this, indexStaticViews = self.options.backcompat.before_post_form ? 2 : 1;

			_.each( this.views._views[''], function( view, index ) {
				if ( index > indexStaticViews ) {
					view.remove();
				}
			} );

			$( '#whats-new' ).css( {
				resize : 'none',
				height : '50px'
			} );

			this.$el.removeClass( 'activity-form-expanded' );

			// Reset the model
			this.model.clear();
			this.model.set( this.resetModel.attributes );
		},

		cleanFeedback: function() {
			_.each( this.views._views[''], function( view ) {
				if ( 'message' === view.$el.prop( 'id' ) ) {
					view.remove();
				}
			} );
		},

		displayFeedback: function( model ) {
			if ( _.isUndefined( this.model.get( 'errors' ) ) ) {
				this.cleanFeedback();
			} else {
				this.views.add( new bp.Views.activityFeedback( model.get( 'errors' ) ) );
			}
		},

		postUpdate: function( event ) {
			var self = this,
			    meta = {};

			if ( event ) {
				if ( 'keydown' === event.type && ( 13 !== event.keyCode || ! event.ctrlKey ) ) {
					return event;
				}

				event.preventDefault();
			}

			// Set the content and meta
			_.each( this.$el.serializeArray(), function( pair ) {
				pair.name = pair.name.replace( '[]', '' );
				if ( 'whats-new' === pair.name ) {
					self.model.set( 'content', pair.value );
				} else if ( -1 === _.indexOf( ['aw-whats-new-submit', 'whats-new-post-in'], pair.name ) ) {
					if ( _.isUndefined( meta[ pair.name ] ) ) {
						meta[ pair.name ] = pair.value;
					} else {
						if ( ! _.isArray( meta[ pair.name ] ) ) {
							meta[ pair.name ] = [ meta[ pair.name ] ];
						}

						meta[ pair.name ].push( pair.value );
					}
				}
			} );

			// Silently add meta
			this.model.set( meta, { silent: true } );

			var data = {
				'_wpnonce_post_update': BP_Nouveau.activity.params.post_nonce
			};

			// Add the Akismet nonce if it exists.
			if ( $('#_bp_as_nonce').val() ) {
				data._bp_as_nonce = $('#_bp_as_nonce').val();
			}

			bp.ajax.post( 'post_update', _.extend( data, this.model.attributes ) ).done( function( response ) {
				var store       = bp.Nouveau.getStorage( 'bp-activity' ),
					searchTerms = $( '[data-bp-search="activity"] input[type="search"]' ).val(), matches = {},
					toPrepend = false;

				// Look for matches if the stream displays search results.
				if ( searchTerms ) {
					searchTerms = new RegExp( searchTerms, 'im' );
					matches = response.activity.match( searchTerms );
				}

				/**
				 * Before injecting the activity into the stream, we need to check the filter
				 * and search terms are consistent with it when posting from a single item or
				 * from the Activity directory.
				 */
				if ( ( ! searchTerms || matches ) ) {
					toPrepend = ! store.filter || 0 === parseInt( store.filter, 10 ) || 'activity_update' === store.filter;
				}

				/**
				 * In the Activity directory, we also need to check the active scope.
				 * eg: An update posted in a private group should only show when the
				 * "My Groups" tab is active.
				 */
				if ( toPrepend && response.is_directory ) {
					toPrepend = ( 'all' === store.scope && ( 'user' === self.model.get( 'object' ) || false === response.is_private ) ) || ( self.model.get( 'object' ) + 's'  === store.scope );
				}

				// Reset the form
				self.resetForm();

				// Display a successful feedback if the acticity is not consistent with the displayed stream.
				if ( ! toPrepend ) {
					self.views.add( new bp.Views.activityFeedback( { value: response.message, type: 'updated' } ) );

				// Inject the activity into the stream only if it hasn't been done already (HeartBeat).
				} else if ( ! $( '#activity-' + response.id  ).length ) {

					// It's the very first activity, let's make sure the container can welcome it!
					if ( ! $( '#activity-stream ul.activity-list').length ) {
						$( '#activity-stream' ).html( $( '<ul></ul>').addClass( 'activity-list item-list bp-list' ) );
					}

					// Prepend the activity.
					bp.Nouveau.inject( '#activity-stream ul.activity-list', response.activity, 'prepend' );
				}
			} ).fail( function( response ) {

				self.model.set( 'errors', { type: 'error', value: response.message } );
			} );
		}
	} );

	bp.Nouveau.Activity.postForm.start();

} )( bp, jQuery );
