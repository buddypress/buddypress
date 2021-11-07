/* global bpGroupManageMembersSettings, _, Backbone */
/* @version 5.0.0 */

( function( wp, bp, $ ) {

	// Bail if not set
	if ( typeof bpGroupManageMembersSettings === 'undefined' ) {
		return;
	}

	// Copy useful WP Objects into BP.
	_.extend( bp, _.pick( wp, 'Backbone', 'template' ) );

	bp.Models      = bp.Models || {};
	bp.Collections = bp.Collections || {};
	bp.Views       = bp.Views || {};

	/**
	 * Model for the Member of the displayed group.
	 */
	bp.Models.groupMember = Backbone.Model.extend( {
		defaults: {
			id: 0,
			name: '',
			avatar_urls : {},
			is_admin: false,
			is_banned: false,
			is_confirmed: false,
			is_mod: false,
			link: ''
		},
		options : {
			path: bpGroupManageMembersSettings.path,
			type: 'POST',
			data: {},
			dataType: 'json'
		},

		initialize: function() {
			// Make sure to reset data & path on model's sync.
			this.on( 'sync', this.resetRequestOptions, this );
		},

		resetRequestOptions: function() {
			this.options.data = {};
			this.options.path = bpGroupManageMembersSettings.path;
		},

		sync: function( method, model, options ) {
			options  = options || {};
			options.context = this;
			var data = options.data || {};
			this.options.path = this.options.path.concat( '/' + model.get( 'id' ) );

			_.extend( options, this.options );
			_.extend( options.data, data );

			if ( 'delete' === method || 'update' === method ) {
				if ( 'delete' === method ) {
					options.headers = { 'X-HTTP-Method-Override': 'DELETE' };
				} else {
					options.headers = { 'X-HTTP-Method-Override': 'PUT' };
				}

				return wp.apiRequest( options );
			}
		},

		parse: function( response ) {
			if ( _.isArray( response ) ) {
				response = _.first( response );
			}

			return response;
		}
	} );

	/**
	 * Collection for the Members of the displayed group.
	 */
	bp.Collections.groupMembers = Backbone.Collection.extend( {
		model: bp.Models.groupMember,
		options : {
			path: bpGroupManageMembersSettings.path,
			type: 'GET',
			data: {},
			dataType: 'json'
		},

		initialize: function() {
			// Make sure to reset data on collection's reset.
			this.on( 'reset', function() {
				this.options.data = {};
			}, this );
		},

		sync: function( method, collection, options ) {
			options  = options || {};
			options.context = this;
			var data = options.data || {};

			_.extend( options, this.options );
			_.extend( options.data, data );

			if ( 'read' === method ) {
				var self = this, success = options.success;
				options.success = function( data, textStatus, request ) {
					if ( ! _.isUndefined( request ) ) {
						self.totalPages        = parseInt( request.getResponseHeader( 'X-WP-TotalPages' ), 10 );
						self.totalGroupMembers = parseInt( request.getResponseHeader( 'X-WP-Total' ), 10 );
					}

					self.currentPage = options.data.page || 1;

					if ( success ) {
						return success.apply( this, arguments );
					}
				};

				return wp.apiRequest( options );
			}
		}
	} );

	// Extend wp.Backbone.View with .prepare().
	bp.View = bp.View || bp.Backbone.View.extend( {
		prepare: function() {
			if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
				return this.model.toJSON();
			} else {
				return {};
			}
		}
	} );

	bp.Views.GroupMemberUpdatingInfo = bp.View.extend( {
		tagName: 'p',
		template : bp.template( 'bp-manage-members-updating' ),

		initialize: function() {
			this.model = new Backbone.Model( {
				type: this.options.value
			} );
		}
	} );

	bp.Views.GroupMemberErrorInfo = bp.View.extend( {
		tagName: 'p',
		template : bp.template( 'bp-manage-members-error' ),

		initialize: function() {
			this.model = new Backbone.Model( {
				message: this.options.value
			} );
		}
	} );

	bp.Views.GroupsMembersLabel = bp.Views.GroupMemberUpdatingInfo.extend( {
		tagName: 'label',
		template:  bp.template( 'bp-manage-members-label' )
	} );

	bp.Views.GroupRolesDropDown = bp.View.extend( {
		tagName: 'select',
		filters: _.extend( { all: { name: bpGroupManageMembersSettings.strings.allMembers } }, bpGroupManageMembersSettings.roles ),

		events: {
			change: 'change'
		},

		initialize: function() {
			if ( this.options.omits ) {
				this.filters = _.omit( this.filters, this.options.omits );
			}

			// Build `<option>` elements.
			this.$el.html( _.chain( this.filters ).map( function( filter, value ) {
				var optionOutput = $( '<option></option>' ).val( value ).html( filter.name )[0];

				if ( this.options.currentRole && value === this.options.currentRole ) {
					return {
						el: $( optionOutput ).prop( 'selected', true )
					};
				} else {
					return {
						el: optionOutput
					};
				}
			}, this ).pluck( 'el' ).value() );
		},

		change: function( event ) {
			var role =  $( event.target ).val(), queryArgs = { roles: [ role ] };

			if ( ! this.collection ) {
				return;
			}

			if ( 'all' === role ) {
				// Unset the current role.
				this.collection.currentRole = '';

				queryArgs = { 'exclude_admins': false };
			} else {
				// Set the current role.
				this.collection.currentRole = role;
			}

			// Reset the current page.
			this.collection.currentPage = 1;

			queryArgs.page = 1;
			$( '#manage-members-search' ).val( '' );

			this.collection.fetch( {
				data: queryArgs,
				reset: true
			} );
		}
	} );

	bp.Views.GroupMembersSearch = bp.View.extend( {
		className: 'bp-dir-search-form',
		tagName: 'form',
		template:  bp.template( 'bp-manage-members-search' ),

		events: {
			'click #manage-members-search-submit' : 'searchMember'
		},

		searchMember: function( event ) {
			event.preventDefault();

			var searchTerms = $( '#manage-members-search' ).val(),
			    queryArgs = _.extend( this.collection.options.data, { search: searchTerms, page: 1 } );

			// Reset the current page.
			this.collection.currentPage = 1;

			if ( ! this.collection.currentRole ) {
				queryArgs.exclude_admins = false;
			} else {
				queryArgs.roles = [ this.collection.currentRole ];
			}

			this.collection.fetch( {
				data: queryArgs,
				reset: true
			} );
		}
	} );

	bp.Views.GroupsMembersPagination = bp.View.extend( {
		className: 'bp-pagination',
		template:  bp.template( 'bp-manage-members-paginate' ),

		events: {
			'click .group-members-paginate-button' : 'queryPage'
		},

		initialize: function() {
			this.collection.on( 'reset', this.setPagination, this );
		},

		setPagination: function( collection ) {
			var attributes = _.pick( collection, [ 'currentPage', 'totalGroupMembers', 'totalPages' ] );

			if ( attributes.totalPages > 1 ) {
				attributes.nextPage = attributes.currentPage + 1;
				attributes.prevPage = attributes.currentPage - 1;
			}

			this.model = new Backbone.Model( attributes );
			this.render();
		},

		queryPage: function( event ) {
			event.preventDefault();

			var page = $( event.currentTarget ).data( 'page' ),
			    searchTerms = $( '#manage-members-search' ).val(),
			    queryArgs = _.extend( this.collection.options.data, { search: searchTerms, page: page } );

			if ( ! this.collection.currentRole ) {
				queryArgs.exclude_admins = false;
			} else {
				queryArgs.roles = [ this.collection.currentRole ];
			}

			this.collection.fetch( {
				data: queryArgs,
				reset: true
			} );
		}
	} );

	bp.Views.GroupMembersNoMatches = bp.View.extend( {
		tagName: 'tr',
		template : bp.template( 'bp-manage-members-empty-row' )
	} );

	bp.Views.GroupMembersListRow = bp.View.extend( {
		tagName: 'tr',
		template : bp.template( 'bp-manage-members-row' ),

		events: {
			'click .group-member-actions a' : 'doMemberAction',
			'change .group-member-edit select' : 'editMemberRole'
		},

		initialize: function() {
			var roleProps = [ 'is_admin', 'is_banned', 'is_confirmed', 'is_mod' ],
				self = this;

			_.each( bpGroupManageMembersSettings.roles, function( props ) {
				if ( _.isMatch( self.model.attributes,  _.pick( props, roleProps ) ) ) {
					self.model.set( 'role', _.pick( props, ['id', 'name'] ), { silent: true } );
				}
			} );

			this.model.collection.on( 'reset', this.clearRow, this );
		},

		clearRow: function() {
			this.views.view.remove();
		},

		renderEditForm: function() {
			var userId = this.model.get( 'id' );

			this.render();

			this.views.set( '#edit-group-member-' + userId, [
				new bp.Views.GroupsMembersLabel( { value: userId, attributes: { 'for': 'group-member' + userId + '-role' } } ),
				new bp.Views.GroupRolesDropDown( { id: 'group-member' + userId + '-role', omits: [ 'all', 'banned' ], currentRole: this.model.get( 'role' ).id } ).render()
			] );
		},

		resetRow: function() {
			this.model.set( 'editing', false );

			return this.render();
		},

		getRoleObject: function( roleId ) {
			var roles = bpGroupManageMembersSettings.roles;

			if ( _.isUndefined( roles[ roleId ] ) ) {
				return {};
			}

			return _.extend(
				{ role: _.pick( roles[ roleId ], ['id', 'name'] ) },
				_.pick( roles[ roleId ], [ 'is_admin', 'is_banned', 'is_confirmed', 'is_mod' ] )
			);
		},

		doMemberAction: function( event ) {
			event.preventDefault();

			var action = $( event.target ).data( 'action' ), self = this;

			if ( 'edit' === action ) {
				this.model.set( 'editing', true );
				return this.renderEditForm();

			} else if ( 'abort' === action ) {
				return this.resetRow();

			} else if ( 'ban' === action || 'unban' === action ) {
				var newRole = ( 'ban' === action ) ? 'banned' : 'member', roleObject = this.getRoleObject( newRole );

				if ( ! roleObject ) {
					return this.resetRow();
				} else {
					this.model.set( 'managingBan', true );
					this.render();
				}

				// Display user feedback.
				this.views.set( '#edit-group-member-' + this.model.get( 'id' ), new bp.Views.GroupMemberUpdatingInfo( { value: action } ).render() );

				// Update Group member's role.
				this.model.save( roleObject, {
					wait: true,
					data: { action: action },
					success: function( model) {
						self.model.collection.remove( model );
						return self.clearRow();
					},
					error: function( model, response ) {
						self.views.set( '#edit-group-member-' + model.get( 'id' ), new bp.Views.GroupMemberErrorInfo( { value: response.responseJSON.message } ).render() );

						// Make sure to reset request options.
						model.resetRequestOptions();
						model.set( 'managingBan', false );
					}
				} );
			} else if ( 'remove' === action ) {
				this.model.set( 'removing', true );
				this.render();

				// Display user feedback.
				this.views.set( '#edit-group-member-' + this.model.get( 'id' ), new bp.Views.GroupMemberUpdatingInfo( { value: action } ).render() );

				// Destroy the membership model.
				this.model.destroy( {
					wait: true,
					data: {},
					success: function() {
						return self.clearRow();
					},
					error: function( model, response ) {
						self.views.set( '#edit-group-member-' + model.get( 'id' ), new bp.Views.GroupMemberErrorInfo( { value: response.responseJSON.message } ).render() );

						// Make sure to reset request options.
						model.resetRequestOptions();
						model.set( 'removing', false );
					}
				} );
			}
		},

		editMemberRole: function( event ) {
			var newRole = $( event.target ).val(), roleObject = this.getRoleObject( newRole ),
			    currentRole = this.model.get( 'role').id, roleAction = 'promote', self = this;

			if ( newRole === this.model.get( 'role' ).id || ! roleObject ) {
				return this.resetRow();
			}

			this.views.set( '#edit-group-member-' + this.model.get( 'id' ), new bp.Views.GroupMemberUpdatingInfo().render() );

			if ( 'admin' === currentRole || ( 'mod' === currentRole && 'member' === newRole ) ) {
				roleAction = 'demote';
			}

			// Update Group member's role
			this.model.save( roleObject, {
				wait: true,
				data: {
					action: roleAction,
					role: newRole
				},
				success: function( model ) {
					if ( self.model.collection.currentRole && newRole !== self.model.collection.currentRole ) {
						self.model.collection.remove( model );
						return self.clearRow();
					} else {
						return self.resetRow();
					}
				},
				error: function( model, response ) {
					self.views.set( '#edit-group-member-' + model.get( 'id' ), new bp.Views.GroupMemberErrorInfo( { value: response.responseJSON.message } ).render() );

					// Make sure to reset request options.
					model.resetRequestOptions();
					model.set( 'editing', false );
				}
			} );
		}
	} );

	bp.Views.GroupMembersListHeader = bp.View.extend( {
		tagName: 'thead',
		template : bp.template( 'bp-manage-members-header' )
	} );

	bp.Views.GroupMembersListTable = bp.View.extend( {
		tagName: 'tbody',

		initialize: function() {
			var preloaded = bpGroupManageMembersSettings.preloaded || {},
			    models = [];

			this.collection.on( 'reset', this.addListTableRows, this );

			if ( preloaded.body && preloaded.body.length > 0 ) {
				_.each( preloaded.body, function( member ) {
					models.push( new bp.Models.groupMember( member ) );
				} );

				this.collection.currentPage = 1;
				if ( preloaded.headers && preloaded.headers[ 'X-WP-TotalPages' ] ) {
					this.collection.totalPages = parseInt( preloaded.headers[ 'X-WP-TotalPages' ], 10 );
				}

				if ( preloaded.headers && preloaded.headers[ 'X-WP-Total' ] ) {
					this.collection.totalGroupMembers = parseInt( preloaded.headers[ 'X-WP-Total' ], 10 );
				}

				this.collection.reset( models );
			} else {
				this.collection.fetch( {
					data: { 'exclude_admins': false },
					reset: true
				} );
			}
		},

		addListTableRows: function( collection ) {
			if ( this.views._views ) {
				var noMembersRow = _.findWhere( this.views._views[''] , { id: 'bp-no-group-members' } );

				if ( noMembersRow ) {
					noMembersRow.remove();
				}
			}

			if ( ! collection.length ) {
				this.views.add( new bp.Views.GroupMembersNoMatches( { id: 'bp-no-group-members' } ) );
			} else {
				_.each( collection.models, function( member ) {
					this.views.add( new bp.Views.GroupMembersListRow( { model: member } ) );
				}, this );
			}
		}
	} );

	bp.Views.GroupMembersUI = bp.View.extend( {
		className: 'group-members',

		initialize: function() {
			var groupMembers = new bp.Collections.groupMembers();

			// Set filters.
			this.views.set( '#group-roles-filter', [
				new bp.Views.GroupsMembersLabel( { attributes: { 'for': 'group-members-role-filter' } } ),
				new bp.Views.GroupRolesDropDown( { id: 'group-members-role-filter', collection: groupMembers } )
			] );

			// Set the search form.
			this.views.set( '#group-members-search-form', new bp.Views.GroupMembersSearch( { id: 'group-members-search', collection: groupMembers } ) );

			// Set Paginate links.
			this.views.set( '#group-members-pagination', new bp.Views.GroupsMembersPagination( { collection: groupMembers } ) );

			// Set Group members list header and body.
			this.views.set( '#group-members-list-table', [
				new bp.Views.GroupMembersListHeader(),
				new bp.Views.GroupMembersListTable( { collection: groupMembers } )
			] );
		}
	} );

	// Inject the UI to manage Group Members into the DOM.
	bp.manageGroupMembersUI = new bp.Views.GroupMembersUI( { el:'#group-manage-members-ui' } ).render();

} )( window.wp || {}, window.bp || {}, jQuery );
