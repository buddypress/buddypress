/* global wp, bp, BP_Nouveau, _, Backbone */
/* @version 3.0.0 */
window.wp = window.wp || {};
window.bp = window.bp || {};

( function( exports, $ ) {

	// Bail if not set
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	_.extend( bp, _.pick( wp, 'Backbone', 'ajax', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	bp.Nouveau = bp.Nouveau || {};

	/**
	 * [Nouveau description]
	 * @type {Object}
	 */
	bp.Nouveau.GroupInvites = {
		/**
		 * [start description]
		 * @return {[type]} [description]
		 */
		start: function() {
			this.scope    = null;
			this.views    = new Backbone.Collection();
			this.navItems = new Backbone.Collection();
			this.users    = new bp.Collections.Users();
			this.invites  = this.users.clone();

			// Add views
			this.setupNav();
			this.setupLoops();
			this.displayFeedback( BP_Nouveau.group_invites.loading, 'loading' );

			// Add an invite when a user is selected
			this.users.on( 'change:selected', this.addInvite, this );

			// Add an invite when a user is selected
			this.invites.on( 'change:selected', this.manageInvite, this );

			// And display the Invites nav
			this.invites.on( 'add', this.invitesNav, this );
			this.invites.on( 'reset', this.hideInviteNav, this );
		},

		setupNav: function() {
			var activeView;

			// Init the nav
			this.nav = new bp.Views.invitesNav( { collection: this.navItems } );

			// loop through available nav items to build it
			_.each( BP_Nouveau.group_invites.nav, function( item, index ) {
				if ( ! _.isObject( item ) ) {
					return;
				}

				// Reset active View
				activeView = 0;

				if ( 0 === index ) {
					this.scope = item.id;
					activeView = 1;
				}

				this.navItems.add( {
					id     : item.id,
					name   : item.caption,
					href   : item.href || '#members-list',
					active : activeView,
					hide   : _.isUndefined( item.hide ) ? 0 : item.hide
				} );
			}, this );

			// Inject the nav into the DOM
			this.nav.inject( '.bp-invites-nav' );

			// Listen to the confirm event
			this.nav.on( 'bp-invites:confirm', this.loadConfirmView, this );
			this.nav.on( 'bp-invites:loops', this.setupLoops, this );
		},

		setupLoops: function( scope ) {
			var users;

			scope = scope || this.scope;

			// Reset Views
			this.clearViews();

			// Only display the loading message if scope has changed
			if ( scope !== this.scope ) {
				// Loading
				this.displayFeedback( BP_Nouveau.group_invites.loading, 'loading' );
			}

			// Set global scope to requested one
			this.scope = scope;

			// Create the loop view
			users = new bp.Views.inviteUsers( { collection: this.users, scope: scope } );

			this.views.add( { id: 'users', view: users } );

			users.inject( '.bp-invites-content' );

			this.displayFilters( this.users );
		},

		displayFilters: function( collection ) {
			var filters_view;

			// Create the model
			this.filters = new Backbone.Model( {
				page         : 1,
				total_page   : 0,
				search_terms : '',
				scope        : this.scope
			} );

			// Use it in the filters viex
			filters_view = new bp.Views.inviteFilters( { model: this.filters, users: collection } );

			this.views.add( { id: 'filters', view: filters_view } );

			filters_view.inject( '.bp-invites-filters' );
		},

		removeFeedback: function() {
			var feedback;

			if ( ! _.isUndefined( this.views.get( 'feedback' ) ) ) {
				feedback = this.views.get( 'feedback' );
				feedback.get( 'view' ).remove();
				this.views.remove( { id: 'feedback', view: feedback } );
			}
		},

		displayFeedback: function( message, type ) {
			var feedback;

			// Make sure to remove the feedbacks
			this.removeFeedback();

			if ( ! message ) {
				return;
			}

			feedback = new bp.Views.Feedback( {
				value : message,
				type  : type || 'info'
			} );

			this.views.add( { id: 'feedback', view: feedback } );

			feedback.inject( '.bp-invites-feedback' );
		},

		addInvite: function( user ) {
			if ( true === user.get( 'selected' ) ) {
				this.invites.add( user );
			} else {
				var invite = this.invites.get( user.get( 'id' ) );

				if ( true === invite.get( 'selected' ) ) {
					this.invites.remove( invite );
				}
			}
		},

		manageInvite: function( invite ) {
			var user = this.users.get( invite.get( 'id' ) );

			// Update the user
			if ( user ) {
				user.set( 'selected', false );
			}

			// remove the invite
			this.invites.remove( invite );

			// No more invites, reset the collection
			if ( ! this.invites.length  ) {
				this.invites.reset();
			}
		},

		invitesNav: function() {
			this.navItems.get( 'invites' ).set( { active: 0, hide: 0 } );
		},

		hideInviteNav: function() {
			this.navItems.get( 'invites' ).set( { active: 0, hide: 1 } );
		},

		clearViews: function() {
			// Clear views
			if ( ! _.isUndefined( this.views.models ) ) {
				_.each( this.views.models, function( model ) {
					model.get( 'view' ).remove();
				}, this );

				this.views.reset();
			}
		},

		loadConfirmView: function() {
			this.clearViews();

			this.displayFeedback( BP_Nouveau.group_invites.invites_form, 'help' );

			// Activate the loop view
			var invites = new bp.Views.invitesEditor( { collection: this.invites } );

			this.views.add( { id: 'invites', view: invites } );

			invites.inject( '.bp-invites-content' );
		}
	};

	// Item (group or blog or any other)
	bp.Models.User = Backbone.Model.extend( {
		defaults : {
			id       : 0,
			avatar   : '',
			name     : '',
			selected : false
        }
	} );

	/** Collections ***********************************************************/

	// Items (groups or blogs or any others)
	bp.Collections.Users = Backbone.Collection.extend( {
		model: bp.Models.User,

		initialize : function() {
			this.options = { page: 1, total_page: 0, group_id: BP_Nouveau.group_invites.group_id };
		},

		sync: function( method, model, options ) {
			options         = options || {};
			options.context = this;
			options.data    = options.data || {};

			// Add generic nonce
			options.data.nonce = BP_Nouveau.nonces.groups;

			if ( this.options.group_id ) {
				options.data.group_id = this.options.group_id;
			}

			if ( 'read' === method ) {
				options.data = _.extend( options.data, {
					action: 'groups_get_group_potential_invites'
				} );

				return bp.ajax.send( options );
			}

			if ( 'create' === method ) {
				options.data = _.extend( options.data, {
					action   : 'groups_send_group_invites',
					_wpnonce : BP_Nouveau.group_invites.nonces.send_invites
				} );

				if ( model ) {
					options.data.users = model;
				}

				return bp.ajax.send( options );
			}

			if ( 'delete' === method ) {
				options.data = _.extend( options.data, {
					action   : 'groups_delete_group_invite',
					_wpnonce : BP_Nouveau.group_invites.nonces.uninvite
				} );

				if ( model ) {
					options.data.user = model;
				}

				return bp.ajax.send( options );
			}
		},

		parse: function( resp ) {

			if ( ! _.isArray( resp.users ) ) {
				resp.users = [resp.users];
			}

			_.each( resp.users, function( value, index ) {
				if ( _.isNull( value ) ) {
					return;
				}

				resp.users[index].id = value.id;
				resp.users[index].avatar = value.avatar;
				resp.users[index].name = value.name;
			} );

			if ( ! _.isUndefined( resp.meta ) ) {
				this.options.page = resp.meta.page;
				this.options.total_page = resp.meta.total_page;
			}

			return resp.users;
		}

	} );

	// Extend wp.Backbone.View with .prepare() and .inject()
	bp.Nouveau.GroupInvites.View = bp.Backbone.View.extend( {
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

	// Feedback view
	bp.Views.Feedback = bp.Nouveau.GroupInvites.View.extend( {
		tagName   : 'div',
		className : 'bp-invites-feedback',
		template  : bp.template( 'bp-group-invites-feedback' ),

		initialize: function() {
			this.model = new Backbone.Model( {
				type: this.options.type || 'info',
				message: this.options.value
			} );
		}
	} );

	bp.Views.invitesNav = bp.Nouveau.GroupInvites.View.extend( {
		tagName: 'ul',
		className: 'subnav',

		events: {
			'click .bp-invites-nav-item' : 'toggleView'
		},

		initialize: function() {
			this.collection.on( 'add', this.outputNav, this );
			this.collection.on( 'change:hide', this.showHideNavItem, this );
		},

		outputNav: function( nav ) {
			/**
			 * The delete nav is not added if no avatar
			 * is set for the object
			 */
			if ( 1 === nav.get( 'hide' ) ) {
				return;
			}

			this.views.add( new bp.Views.invitesNavItem( { model: nav } ) );
		},

		showHideNavItem: function( item ) {
			var isRendered = null;

			/**
			 * Loop in views to show/hide the nav item
			 * BuddyPress is only using this for the delete nav
			 */
			_.each( this.views._views[''], function( view ) {
				if ( 1 === view.model.get( 'hide' ) ) {
					view.remove();
				}

				// Check to see if the nav is not already rendered
				if ( item.get( 'id' ) === view.model.get( 'id' ) ) {
					isRendered = true;
				}
			} );

			// Add the Delete nav if not rendered
			if ( ! _.isBoolean( isRendered ) ) {
				item.set( 'invites_count', bp.Nouveau.GroupInvites.invites.length );
				this.outputNav( item );
			}
		},

		toggleView: function( event ) {
			var target = $( event.target );
			event.preventDefault();

			if ( ! target.data( 'nav' ) && 'SPAN' === event.target.tagName ) {
				target = $( event.target ).parent();
			}

			var current_nav_id = target.data( 'nav' );

			_.each( this.collection.models, function( nav ) {
				if ( nav.id === current_nav_id ) {
					nav.set( 'active', 1 );

					// Specific to the invites view
					if ( 'invites' === nav.id ) {
						this.trigger( 'bp-invites:confirm' );
					} else {
						this.trigger( 'bp-invites:loops', nav.id );
					}

				} else if ( 1 !== nav.get( 'hide' ) ) {
					nav.set( 'active', 0 );
				}
			}, this );
		}
	} );

	bp.Views.invitesNavItem = bp.Nouveau.GroupInvites.View.extend( {
		tagName  : 'li',
		template : bp.template( 'bp-invites-nav' ),

		initialize: function() {
			if ( 1 === this.model.get( 'active' ) ) {
				this.el.className += ' current';
			}

			if ( 'invites' === this.model.get( 'id' ) ) {
				this.el.className += ' dynamic';
			}

			if ( 'invited' === this.model.get( 'id' ) ) {
				this.el.className += ' pending';
			}

			this.model.on( 'change:active', this.toggleClass, this );
			this.on( 'ready', this.updateCount, this );

			bp.Nouveau.GroupInvites.invites.on( 'add', this.updateCount, this );
			bp.Nouveau.GroupInvites.invites.on( 'remove', this.updateCount, this );
		},

		updateCount: function( user, invite ) {
			if ( 'invites' !== this.model.get( 'id' ) ) {
				return;
			}

			var span_count = _.isUndefined( invite ) ? this.model.get( 'invites_count' ) : invite.models.length;

			if ( $( this.el ).find( 'span' ).length ) {
				$( this.el ).find( 'span' ).html( span_count );
			} else {
				$( this.el ).find( 'a' ).append( $( '<span class="count"></span>' ).html( span_count ) );
			}
		},

		toggleClass: function( model ) {
			if ( 0 === model.get( 'active' ) ) {
				$( this.el ).removeClass( 'current' );
			} else {
				$( this.el ).addClass( 'current' );
			}
		}
	} );

	bp.Views.Pagination = bp.Nouveau.GroupInvites.View.extend( {
		tagName   : 'div',
		className : 'last',
		template  :  bp.template( 'bp-invites-paginate' )
	} );

	bp.Views.inviteFilters = bp.Nouveau.GroupInvites.View.extend( {
		tagName: 'div',
		template:  bp.template( 'bp-invites-filters' ),

		events : {
			'search #group_invites_search'      : 'resetSearchTerms',
			'submit #group_invites_search_form' : 'setSearchTerms',
			'click #bp-invites-next-page'       : 'nextPage',
			'click #bp-invites-prev-page'       : 'prevPage'
		},

		initialize: function() {
			this.model.on( 'change', this.filterUsers, this );
			this.options.users.on( 'sync', this.addPaginatation, this );
		},

		addPaginatation: function( collection ) {
			_.each( this.views._views[''], function( view ) {
				view.remove();
			} );

			if ( 1 === collection.options.total_page ) {
				return;
			}

			this.views.add( new bp.Views.Pagination( { model: new Backbone.Model( collection.options ) } ) );
		},

		filterUsers: function() {
			bp.Nouveau.GroupInvites.displayFeedback( BP_Nouveau.group_invites.loading, 'loading' );

			this.options.users.reset();

			this.options.users.fetch( {
				data    : _.pick( this.model.attributes, ['scope', 'search_terms', 'page'] ),
				success : this.usersFiltered,
				error   : this.usersFilterError
			} );
		},

		usersFiltered: function() {
			bp.Nouveau.GroupInvites.removeFeedback();
		},

		usersFilterError: function( collection, response ) {
			bp.Nouveau.GroupInvites.displayFeedback( response.feedback, 'error' );
		},

		resetSearchTerms: function( event ) {
			event.preventDefault();

			if ( ! $( event.target ).val() ) {
				$( event.target ).closest( 'form' ).submit();
			} else {
				$( event.target ).closest( 'form' ).find( '[type=submit]' ).addClass('bp-show');
			}
		},

		setSearchTerms: function( event ) {
			event.preventDefault();

			this.model.set( {
				search_terms : $( event.target ).find( 'input[type=search]' ).val() || '',
				page         : 1
			} );
		},

		nextPage: function( event ) {
			event.preventDefault();

			this.model.set( 'page', this.model.get( 'page' ) + 1 );
		},

		prevPage: function( event ) {
			event.preventDefault();

			this.model.set( 'page', this.model.get( 'page' ) - 1 );
		}
	} );

	bp.Views.inviteUsers = bp.Nouveau.GroupInvites.View.extend( {
		tagName   : 'ul',
		className : 'item-list bp-list',
		id        : 'members-list',

		initialize: function() {
			// Load users for the active view
			this.requestUsers();

			this.collection.on( 'reset', this.cleanContent, this );
			this.collection.on( 'add', this.addUser, this );
		},

		requestUsers: function() {
			this.collection.reset();

			this.collection.fetch( {
				data    : _.pick( this.options, 'scope' ),
				success : this.usersFetched,
				error   : this.usersFetchError
			} );
		},

		usersFetched: function( collection, response ) {
			bp.Nouveau.GroupInvites.displayFeedback( response.feedback, 'help' );
		},

		usersFetchError: function( collection, response ) {
			var type = response.type || 'help';

			bp.Nouveau.GroupInvites.displayFeedback( response.feedback, type );
		},

		cleanContent: function() {
			_.each( this.views._views[''], function( view ) {
				view.remove();
			} );
		},

		addUser: function( user ) {
			this.views.add( new bp.Views.inviteUser( { model: user } ) );
		}
	} );

	bp.Views.inviteUser = bp.Nouveau.GroupInvites.View.extend( {
		tagName  : 'li',
		template : bp.template( 'bp-invites-users' ),

		events: {
			'click .group-add-remove-invite-button'    : 'toggleUser',
			'click .group-remove-invite-button'        : 'removeInvite'
		},

		initialize: function() {
			var invite = bp.Nouveau.GroupInvites.invites.get( this.model.get( 'id' ) );

			if ( invite ) {
				this.model.set( 'selected', true, { silent: true } );
			}
		},

		render: function() {
			if ( this.model.get( 'selected' ) ) {
				this.el.className = 'selected';
			} else {
				this.el.className = '';
			}

			bp.Nouveau.GroupInvites.View.prototype.render.apply( this, arguments );
		},

		toggleUser: function( event ) {
			event.preventDefault();

			var selected = this.model.get( 'selected' );

			if ( false === selected ) {
				this.model.set( 'selected', true );
			} else {
				this.model.set( 'selected', false );

				if ( ! bp.Nouveau.GroupInvites.invites.length  ) {
					bp.Nouveau.GroupInvites.invites.reset();
				}
			}

			// Rerender to update buttons.
			this.render();
		},

		removeInvite: function( event ) {
			event.preventDefault();

			var collection = this.model.collection;

			if ( ! collection.length ) {
				return;
			}

			collection.sync( 'delete', this.model.get( 'id' ), {
				success : _.bind( this.inviteRemoved, this ),
				error   : _.bind( this.uninviteError, this )
			} );
		},

		inviteRemoved: function( response ) {
			var collection = this.model.collection;

			if ( ! collection.length ) {
				return;
			}

			collection.remove( this.model );
			this.remove();

			bp.Nouveau.GroupInvites.removeFeedback();

			if ( false === response.has_invites ) {
				bp.Nouveau.GroupInvites.displayFeedback( response.feedback, 'success' );

				// Hide the invited nav
				bp.Nouveau.GroupInvites.navItems.get( 'invited' ).set( { active: 0, hide: 1 } );
			}
		},

		uninviteError: function( response ) {
			bp.Nouveau.GroupInvites.displayFeedback( response.feedback, 'error' );
		}
	} );

	bp.Views.invitesEditor = bp.Nouveau.GroupInvites.View.extend( {
		tagName : 'div',
		id      : 'send-invites-editor',

		events: {
			'click #bp-invites-send'  : 'sendInvites',
			'click #bp-invites-reset' : 'clearForm'
		},

		initialize: function() {
			this.views.add( new bp.Views.selectedUsers( { collection: this.collection } ) );
			this.views.add( new bp.Views.invitesForm() );

			this.collection.on( 'reset', this.cleanViews, this );
		},

		sendInvites: function( event ) {
			event.preventDefault();

			$( this.el ).addClass( 'bp-hide' );

			bp.Nouveau.GroupInvites.displayFeedback( BP_Nouveau.group_invites.invites_sending, 'info' );

			this.collection.sync( 'create', _.pluck( this.collection.models, 'id' ), {
				success : _.bind( this.invitesSent, this ),
				error   : _.bind( this.invitesError, this ),
				data    : {
					message: $( this.el ).find( 'textarea' ).val()
				}
			} );
		},

		invitesSent: function( response ) {
			this.collection.reset();

			bp.Nouveau.GroupInvites.displayFeedback( response.feedback, 'success' );

			// Display the pending invites
			if ( 1 === bp.Nouveau.GroupInvites.navItems.get( 'invited' ).get( 'hide' ) && ! BP_Nouveau.group_invites.is_group_create ) {
				bp.Nouveau.GroupInvites.navItems.get( 'invited' ).set( { active: 0, hide: 0 } );
			}
		},

		invitesError: function( response ) {
			var type = response.type || 'help';

			$( this.el ).removeClass( 'bp-hide' );

			bp.Nouveau.GroupInvites.displayFeedback( response.feedback, type );

			if ( ! _.isUndefined( response.users ) ) {
				// Display the pending invites
				if ( 1 === bp.Nouveau.GroupInvites.navItems.get( 'invited' ).get( 'hide' ) && response.users.length < this.collection.length ) {
					bp.Nouveau.GroupInvites.navItems.get( 'invited' ).set( { active: 0, hide: 0 } );
				}

				_.each( this.collection.models, function( invite ) {
					// If not an error, remove from the selection
					if ( -1 === _.indexOf( response.users, invite.get( 'id' ) ) ) {
						invite.set( 'selected', false );
					}
				}, this );
			}
		},

		clearForm: function( event ) {
			event.preventDefault();

			this.collection.reset();
		},

		cleanViews: function() {
			_.each( this.views._views[''], function( view ) {
				view.remove();
			} );

			bp.Nouveau.GroupInvites.displayFeedback( BP_Nouveau.group_invites.invites_form_reset, 'success' );
		}
	} );

	bp.Views.invitesForm = bp.Nouveau.GroupInvites.View.extend( {
		tagName  : 'div',
		id       : 'bp-send-invites-form',
		template :  bp.template( 'bp-invites-form' )
	} );

	bp.Views.selectedUsers = bp.Nouveau.GroupInvites.View.extend( {
		tagName : 'ul',

		initialize: function() {
			this.cleanContent();

			_.each( this.collection.models, function( invite ) {
				this.views.add( new bp.Views.selectedUser( { model: invite } ) );
			}, this );
		},

		cleanContent: function() {
			_.each( this.views._views[''], function( view ) {
				view.remove();
			} );
		}
	} );

	bp.Views.selectedUser = bp.Nouveau.GroupInvites.View.extend( {
		tagName  : 'li',
		template : bp.template( 'bp-invites-selection' ),

		events: {
			click : 'removeSelection'
		},

		initialize: function() {
			this.model.on( 'change:selected', this.removeView, this );

			// Build the BP Tooltip.
			if ( ! this.model.get( 'uninviteTooltip' ) ) {
				this.model.set( 'uninviteTooltip',
					BP_Nouveau.group_invites.removeUserInvite.replace( '%s', this.model.get( 'name' ) ),
					{ silent: true }
				);
			}

			this.el.id = 'uninvite-user-' + this.model.get( 'id' );
		},

		removeSelection: function( event ) {
			event.preventDefault();

			this.model.set( 'selected', false );
		},

		removeView: function( model ) {
			if ( false !== model.get( 'selected' ) ) {
				return;
			}

			this.remove();
		}
	} );

	// Launch BP Nouveau Groups
	bp.Nouveau.GroupInvites.start();

} )( bp, jQuery );
