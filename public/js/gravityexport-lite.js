var gfexcel_sortable;

( function ( $, sprintf ) {

	// Delay before starting the light-up animation, allowing the DOM to update after appending.
	var LIGHT_UP_DELAY = 10;

	// Duration of the light-up highlight effect on a moved field.
	var LIGHT_UP_DURATION = 200;

	/**
	 * Filter sortable field lists based on search input.
	 *
	 * @param {jQuery} $container The sort fields container.
	 * @param {string} searchTerm The search term to filter by.
	 * @param {Object} strings The i18n strings object.
	 * @param {Array} buttons Array of button objects with $button, enableLabel, disableLabel properties.
	 */
	var filterFields = function ( $container, searchTerm, strings, buttons ) {
		var $lists = $container.find( 'ul.fields-select' );
		var $status = $container.find( '.gk-gravityexport-sort-fields__search-status' );
		var $statusText = $container.find( '.gk-gravityexport-sort-fields__search-status-text' );
		var normalizedSearch = searchTerm.toLowerCase().trim();
		var totalVisible = 0;

		$lists.each( function () {
			var $list = $( this );

			$list.find( 'li' ).each( function () {
				var $item = $( this );
				var fieldLabel = $item.find( '.field span' ).text().toLowerCase();

				if ( normalizedSearch === '' || fieldLabel.indexOf( normalizedSearch ) !== -1 ) {
					$item.removeClass( 'gk-gravityexport-sort-fields__hidden' ).attr( 'aria-hidden', 'false' );
					totalVisible++;
				} else {
					$item.addClass( 'gk-gravityexport-sort-fields__hidden' ).attr( 'aria-hidden', 'true' );
				}
			} );
		} );

		// Update status message
		if ( normalizedSearch === '' ) {
			$status.removeClass( 'is-active is-no-results' );
		} else {
			var statusMessage;
			if ( totalVisible === 0 ) {
				statusMessage = strings.no_fields_match;
				$status.addClass( 'is-no-results' );
			} else if ( totalVisible === 1 ) {
				statusMessage = strings.one_field_matches;
				$status.removeClass( 'is-no-results' );
			} else {
				statusMessage = strings.n_fields_match.replace( '%d', totalVisible );
				$status.removeClass( 'is-no-results' );
			}
			$statusText.text( statusMessage );
			$status.addClass( 'is-active' );
		}

		// Update button labels based on search state
		if ( buttons && buttons.length ) {
			var isSearchActive = normalizedSearch !== '';
			buttons.forEach( function ( btn ) {
				var newLabel = isSearchActive ? btn.visibleLabel : btn.allLabel;
				btn.$button.text( newLabel );
			} );
		}

		// Refresh sortable to account for hidden items
		$lists.sortable( 'refresh' );
	};

	var updateLists = function ( $elements ) {
		$elements.each( function ( i, el ) {
			var $input = $( el ).prevAll( 'input[type=hidden]' );
			$input.val( $( el ).sortable( 'toArray', { attribute: 'data-value' } ).join( ',' ) );
		} )
	};

	gfexcel_sortable = function ( elements, connector_class ) {
		var $elements = $( elements );
		var strings = typeof gravityexport_lite_strings !== 'undefined'
			? gravityexport_lite_strings
			: {
				'enable': 'Enable all',
				'disable': 'Disable all',
				'enable_visible': 'Enable visible',
				'disable_visible': 'Disable visible',
				'no_fields_match': 'No fields match your search.',
				'one_field_matches': '1 field matches your search.',
				'n_fields_match': '%d fields match your search.',
				'field_moved': '%1$s moved to %2$s.',
				'fields_moved': '%1$d fields moved to %2$s.',
				'one_field_moved': '1 field moved to %2$s.'
			};

		// Track buttons for label updates during search
		var buttons = [];

		// Initialize container references
		var $container = $elements.closest( '.gk-gravityexport-sort-fields' );
		var $searchInput = $container.find( '.gk-gravityexport-sort-fields__search-input' );
		var $clearSearch = $container.find( '.gk-gravityexport-sort-fields__clear-search' );

		/**
		 * Clear search and reset filter.
		 */
		var clearSearch = function () {
			$searchInput.val( '' );
			filterFields( $container, '', strings, buttons );
			$searchInput.focus();
		};

		if ( $searchInput.length ) {
			var debouncedFilter = _.debounce( function () {
				filterFields( $container, $searchInput.val(), strings, buttons );
			}, 150 );

			$searchInput.on( 'input', debouncedFilter );

			// Prevent form submission on Enter, clear search on Escape
			$searchInput.on( 'keydown', function ( e ) {
				if ( e.key === 'Enter' ) {
					e.preventDefault();
				} else if ( e.key === 'Escape' ) {
					clearSearch();
				}
			} );

			// Handle search input clear button (native browser clear)
			$searchInput.on( 'search', function () {
				filterFields( $container, $searchInput.val(), strings, buttons );
			} );

			// Handle clear search link click
			$clearSearch.on( 'click', function ( e ) {
				e.preventDefault();
				clearSearch();
			} );
		}

		$elements.each( function () {
			var $list = $( this );
			var send_to = '#' + $list.data( 'send-to' );
			var isEnableButton = send_to.indexOf( 'enabled' ) > 0;
			var allLabel = isEnableButton ? strings.enable : strings.disable;
			var visibleLabel = isEnableButton ? strings.enable_visible : strings.disable_visible;
			var $move_all_button = $( '<button type="button">' + allLabel + '</button>' );

			// Store button reference for label updates during search
			buttons.push( {
				$button: $move_all_button,
				allLabel: allLabel,
				visibleLabel: visibleLabel
			} );

			$move_all_button
				// Move visible items to the `send-to` list when clicked.
				.on( 'click', function () {
					// Only move items that are not hidden by search filter
					var $itemsToMove = $list.find( 'li:not(.gk-gravityexport-sort-fields__hidden)' );
					var count = $itemsToMove.length;

					if ( count === 0 ) {
						return;
					}

					var $targetList = $( send_to );
					var targetLabel = $targetList.data( 'list-label' ) || '';

					$itemsToMove.appendTo( $targetList );
					$elements.sortable( 'refresh' );
					updateLists( $elements );

					// Announce the action to screen readers
					var message;
					if ( count === 1 ) {
						message = sprintf( strings.one_field_moved, count, targetLabel );
					} else {
						message = sprintf( strings.fields_moved, count, targetLabel );
					}
					wp.a11y.speak( message );
				} );

			// Add the button before the list.
			$( this ).before( $move_all_button );
		} );

		$elements.sortable( {
			connectWith: '.' + connector_class,
			update: function () {
				updateLists( $elements );
			}
		} ).disableSelection();

		/**
		 * Move a single field item to its target list.
		 *
		 * @param {jQuery} $moveButton The move button that was activated.
		 */
		var moveField = function ( $moveButton ) {
			var $element = $moveButton.closest( 'li' );
			var $sourceList = $element.closest( 'ul' );
			var send_to = '#' + $sourceList.data( 'send-to' );
			var $targetList = $( send_to );
			var fieldLabel = $element.find( '.field span' ).text();
			var targetLabel = $targetList.data( 'list-label' ) || '';

			$element.appendTo( $targetList );
			setTimeout( function () {
				$element.addClass( 'light-up' );
				setTimeout( function () {
					$element.removeClass( 'light-up' );
				}, LIGHT_UP_DURATION );
			}, LIGHT_UP_DELAY );
			$elements.sortable( 'refresh' );
			updateLists( $elements );

			// Announce the action to screen readers
			var message = sprintf( strings.field_moved, fieldLabel, targetLabel );
			wp.a11y.speak( message );
		};

		$elements
			.on( 'click', '.move', function () {
				moveField( $( this ) );
			} )
			// Keyboard support for move button
			.on( 'keydown', '.move', function ( e ) {
				if ( e.key === 'Enter' || e.key === ' ' ) {
					e.preventDefault();
					moveField( $( this ) );
				}
			} );
	};

	$( document ).ready( function () {
		const $embedShortcodeEl = $( '#embed_code' );
		const secret = $embedShortcodeEl.data( 'secret' );

		$( '#start_date, #end_date' ).datepicker( { dateFormat: 'yy-mm-dd', changeMonth: true, changeYear: true } );

		$( '#file_extension' ).on( 'change', function () {
			const shortcode = $embedShortcodeEl.val();
			const has_type = shortcode.match( / type=/ );
			const regex = has_type ? / type="[^"]*"/ : /]$/;

			let type = ` type="${ $( this ).val() }"`;

			if ( !has_type ) {
				type += ']';
			}

			$embedShortcodeEl.val( shortcode.replace( regex, type ) );
		} );

		$( '#has_embed_secret' ).on( 'change', function () {
			let embedShortcode = $embedShortcodeEl.val();

			if ( !embedShortcode ) {
				return;
			}

			if ( $( this ).is( ':checked' ) ) {
				embedShortcode = embedShortcode.replace( /]$/, ` secret="${ secret }"]` );
			} else {
				embedShortcode = embedShortcode.replace( / secret="[^"]+"/, '' );
			}

			$embedShortcodeEl.val( embedShortcode );
		} );
	} );
} )( jQuery, wp.i18n.sprintf );
