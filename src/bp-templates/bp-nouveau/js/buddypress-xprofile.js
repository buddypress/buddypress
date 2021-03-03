/* global BP_Nouveau */
/* @since 3.0.0 */
/* @version 8.0.0 */

( function( $ ) {

	// Bail if not set.
	if ( typeof BP_Nouveau === 'undefined' ) {
		return;
	}

	/**
	 * This an ugly copy from Legacy's buddypress.js for now
	 *
	 * This really needs to be improved !
	 */

	/** Profile Visibility Settings *********************************/

	// Initially hide the 'field-visibility-settings' block.
	$( '.field-visibility-settings' ).addClass( 'bp-hide' );
	// Add initial aria state to button.
	$( '.visibility-toggle-link' ).attr( 'aria-expanded', 'false' );

	$( '.visibility-toggle-link' ).on( 'click', function( event ) {
		event.preventDefault();

		$( this ).attr('aria-expanded', 'true');

		$( this ).parent().addClass( 'field-visibility-settings-hide bp-hide' )

			.siblings( '.field-visibility-settings' ).removeClass( 'bp-hide' ).addClass( 'field-visibility-settings-open' );
	} );

	$( '.field-visibility-settings-close' ).on( 'click', function( event ) {
		event.preventDefault();

		var settings_div = $( this ).parent(),
			vis_setting_text = settings_div.find( 'input:checked' ).parent().text();

		settings_div.removeClass( 'field-visibility-settings-open' ).addClass( 'bp-hide' )
			.siblings( '.field-visibility-settings-toggle' )
				.children( '.current-visibility-level' ).text( vis_setting_text ).end()
				.addClass( 'bp-show' ).removeClass( 'field-visibility-settings-hide bp-hide' );
				$( '.visibility-toggle-link').attr( 'aria-expanded', 'false' );
	} );

	$( '#profile-edit-form input:not(:submit), #profile-edit-form textarea, #profile-edit-form select, #signup_form input:not(:submit), #signup_form textarea, #signup_form select' ).on( 'change', function() {
		var shouldconfirm = true;

		$( '#profile-edit-form input:submit, #signup_form input:submit' ).on( 'click', function() {
			shouldconfirm = false;
		} );

		window.onbeforeunload = function() {
			if ( shouldconfirm ) {
				return BP_Nouveau.unsaved_changes;
			}
		};
	} );

	window.clear = function( container ) {
		if ( ! container ) {
			return;
		}

		container = container.replace( '[', '\\[' ).replace( ']', '\\]' );

		if ( $( '#' + container + ' option' ).length ) {
			$.each( $( '#' + container + ' option' ), function( c, option ) {
				$( option ).prop( 'selected', false );
			} );
		} else if ( $( '#' + container + ' [type=radio]' ).length ) {
			$.each( $( '#' + container + ' [type=radio]' ), function( c, checkbox ) {
				$( checkbox ).prop( 'checked', false );
			} );
		}
	};
} )( jQuery );
