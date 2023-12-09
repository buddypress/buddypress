/**
 * The Twenty Nineteen theme's priority menu script customized for BP Menus.
 *
 * Credits: the WordPress team.
 *
 * @since 12.0.0
 */
(function() {

	/**
	 * Debounce.
	 *
	 * @param {Function} func
	 * @param {number} wait
	 * @param {boolean} immediate
	 */
	function debounce(func, wait, immediate) {
		'use strict';

		var timeout;
		wait      = (typeof wait !== 'undefined') ? wait : 20;
		immediate = (typeof immediate !== 'undefined') ? immediate : true;

		return function() {

			var context = this, args = arguments;
			var later = function() {
				timeout = null;

				if (!immediate) {
					func.apply(context, args);
				}
			};

			var callNow = immediate && !timeout;

			clearTimeout(timeout);
			timeout = setTimeout(later, wait);

			if (callNow) {
				func.apply(context, args);
			}
		};
	}

	/**
	 * Prepends an element to a container.
	 *
	 * @param {Element} container
	 * @param {Element} element
	 */
	function prependElement(container, element) {
		if (element && container.firstChild) {
			return container.insertBefore(element, container.firstChild);
		} else if ( element ) {
			return container.appendChild(element);
		}
	}

	/**
	 * Shows an element by adding a hidden className.
	 *
	 * @param {Element} element
	 */
	function showButton(element) {
		// classList.remove is not supported in IE11.
		element.className = element.className.replace('is-empty', '');
	}

	/**
	 * Hides an element by removing the hidden className.
	 *
	 * @param {Element} element
	 */
	function hideButton(element) {
		// classList.add is not supported in IE11.
		if (!element.classList.contains('is-empty')) {
			element.className += ' is-empty';
		}
	}

	/**
	 * Returns the currently available space in the menu container.
	 *
	 * @returns {number} Available space
	 */
	function getAvailableSpace( button, container ) {
		return container.offsetWidth - button.offsetWidth - 22;
	}

	/**
	 * Returns whether the current menu is overflowing or not.
	 *
	 * @returns {boolean} Is overflowing
	 */
	function isOverflowingNavivation( list, button, container ) {
		return list.offsetWidth > getAvailableSpace( button, container );
	}

	/**
	 * Set menu container variable.
	 */
	var priorityNavContainers = document.querySelectorAll('.bp-priority-nav');
	var breaks                = {
		'object-nav': [],
		subnav: []
	};

	/**
	 * Let’s bail if we our menu doesn't exist.
	 */
	if ( ! priorityNavContainers ) {
		return;
	}

	/**
	 * Refreshes the list item from the menu depending on the menu size.
	 */
	function updateNavigationMenu( container, menu ) {

		/**
		 * Let’s bail if our menu is empty.
		 */
		if ( ! container.parentNode.querySelector('.bp-priority-' + menu + '-nav-items[id]') ) {
			return;
		}

		// Adds the necessary UI to operate the menu.
		var visibleList  = container.parentNode.querySelector('.bp-priority-' + menu + '-nav-items[id]');
		var hiddenList   = visibleList.nextElementSibling.querySelector('.hidden-items');
		var toggleButton = visibleList.nextElementSibling.querySelector('.bp-priority-nav-more-toggle');

		if ( isOverflowingNavivation( visibleList, toggleButton, container ) ) {
			if ( ! visibleList.firstChild ) {
				return;
			}

			// Record the width of the list.
			breaks[menu].push( visibleList.offsetWidth );
			// Move last item to the hidden list.
			prependElement( hiddenList, ! visibleList.lastChild || null === visibleList.lastChild ? visibleList.previousElementSibling : visibleList.lastChild );
			// Show the toggle button.
			showButton( toggleButton );

		} else {

			// There is space for another item in the nav.
			if ( getAvailableSpace( toggleButton, container ) > breaks[menu][breaks[menu].length - 1] ) {
				// Move the item to the visible list.
				visibleList.appendChild( hiddenList.firstChild.nextSibling );
				breaks[menu].pop();
			}

			// Hide the dropdown btn if hidden list is empty.
			if (breaks[menu].length < 2) {
				hideButton( toggleButton );
			}
		}

		// Recur if the visible list is still overflowing the nav.
		if ( isOverflowingNavivation( visibleList, toggleButton, container ) ) {
			updateNavigationMenu( container, menu );
		}
	}

	function updateNavigationMenuAll() {
		priorityNavContainers.forEach( function( navContainer ) {
			updateNavigationMenu( navContainer, navContainer.getAttribute( 'id' ) );
		} );
	}

	/**
	 * Run our priority+ function as soon as the document is `ready`.
	 */
	document.addEventListener( 'DOMContentLoaded', function() {
		updateNavigationMenuAll();
	});

	/**
	 * Run our priority+ function on load.
	 */
	window.addEventListener( 'load', function() {
		updateNavigationMenuAll();
	});

	/**
	 * Run our priority+ function every time the window resizes.
	 */
	var isResizing = false;
	window.addEventListener( 'resize',
		debounce( function() {
			if ( isResizing ) {
				return;
			}

			isResizing = true;
			setTimeout( function() {
				updateNavigationMenuAll();
				isResizing = false;
			}, 150 );
		} )
	);

	/**
	 * Run our priority+ function.
	 */
	updateNavigationMenuAll();

})();
