/* global _ */
/* @version 3.0.0 */
window.wp = window.wp || {};

( function( wp, $ ) {

	if ( undefined === typeof wp.customizer ) {
		return;
	}

	$( document ).ready( function() {

		// If the Main Group setting is disabled, hide all others
		$( wp.customize.control( 'group_front_page' ).selector ).on( 'click', 'input[type=checkbox]', function( event ) {
			var checked = $( event.currentTarget ).prop( 'checked' ), controller = $( event.delegateTarget ).prop( 'id' );

			_.each( wp.customize.section( 'bp_nouveau_group_front_page' ).controls(), function( control ) {
				if ( control.selector !== '#' + controller ) {
					if ( true === checked ) {
						$( control.selector ).show();
					} else {
						$( control.selector ).hide();
					}
				}
			} );
		} );

		// If the Main User setting is disabled, hide all others
		$( wp.customize.control( 'user_front_page' ).selector ).on( 'click', 'input[type=checkbox]', function( event ) {
			var checked = $( event.currentTarget ).prop( 'checked' ), controller = $( event.delegateTarget ).prop( 'id' );

			_.each( wp.customize.section( 'bp_nouveau_user_front_page' ).controls(), function( control ) {
				if ( control.selector !== '#' + controller ) {
					if ( true === checked ) {
						$( control.selector ).show();
					} else {
						$( control.selector ).hide();
					}
				}
			} );
		} );

		$( 'ul#customize-control-group_nav_order, ul#customize-control-user_nav_order' ).sortable( {
			cursor    : 'move',
			axis      : 'y',
			opacity   : 1,
			items     : 'li:not(.ui-sortable-disabled)',
			tolerance : 'intersect',

			update: function() {
				var order = [];

				$( this ).find( '[data-bp-nav]' ).each( function( s, slug ) {
					order.push( $( slug ).data( 'bp-nav' ) );
				} );

				if ( order.length ) {
					$( '#bp_item_' + $( this ).data( 'bp-type' ) ).val( order.join() ).trigger( 'change' );
				}
			}
		} ).disableSelection();

	} );

} )( window.wp, jQuery );
