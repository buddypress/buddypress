// Use the bp global.
window.bp = window.bp || {};

/**
 * Clears the checked/selected options of a radio button or a multiple select.
 *
 * @since 10.0.0
 * @param {HTMLElement} container The HTMLElement containing the options to clear.
 * @returns {void}
 */
bp.clear = ( container ) => {
	const optionsContainer = document.getElementById( container );

	if ( ! optionsContainer ) {
		return;
	}

	const checkedRadio = optionsContainer.querySelector( 'input:checked' );
	const allOptions = optionsContainer.querySelectorAll( 'option' );

	if ( checkedRadio ) {
		checkedRadio.checked = '';
	}

	if ( allOptions ) {
		allOptions.forEach( ( option ) => {
			option.selected = false;
		} );
	}
};

document.querySelectorAll( '.visibility-toggle-link' ).forEach( ( button ) => {
	button.addEventListener( 'click', ( event ) => {
		event.preventDefault();

		const changeButton = event.target;
		const changeButtonContainer = changeButton.closest( '.field-visibility-settings-toggle' );
		const settingsContainer = changeButtonContainer.nextElementSibling;

		// Hides the "Change" button.
		changeButton.setAttribute( 'aria-expanded', true );
		changeButtonContainer.style.display = 'none';

		// Displays the settings visibility container.
		settingsContainer.style.display = 'block';
	} );
} );

document.querySelectorAll( '.field-visibility-settings-close' ).forEach( ( button ) => {
	button.addEventListener( 'click', ( event ) => {
		event.preventDefault();

		const closeButton = event.target;
		const settingsContainer = closeButton.closest( '.field-visibility-settings' );
		const changeButtonContainer = settingsContainer.previousElementSibling;
		const currentVisibility = settingsContainer.querySelector( 'input:checked' ).nextElementSibling.innerHTML;

		// Closes the visibility settings options.
		settingsContainer.style.display = 'none';

		// Displays the current visibility.
		changeButtonContainer.querySelector( '.visibility-toggle-link' ).setAttribute( 'aria-expanded', false );
		changeButtonContainer.querySelector( '.current-visibility-level' ).innerHTML = currentVisibility;
		changeButtonContainer.style.display = 'block';
	} );
} );
