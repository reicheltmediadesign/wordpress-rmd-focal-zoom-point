/**
 * Media library: focal point picker (click or drag) and zoom for every
 * [data-rmd-fzp-field], see includes/Admin/MediaField.php. Uses event
 * delegation because the media modal renders its fields after page load.
 *
 * The controls edit one target: the general values ("" = all crops) or one
 * crop variant. Each target keeps its values in hidden inputs
 * ([data-rmd-fzp-store]); a variant without own values has them disabled, so
 * they are not submitted and the variant uses the general values.
 *
 * A "change" event on an input makes the media modal save the attachment
 * fields. The modal then renders the field again; the hidden "active" input
 * tells the server which target to show.
 */
( function () {
	const MIN_ZOOM = 1;
	const MAX_ZOOM = 3;
	const CENTER = { x: 50, y: 50, zoom: MIN_ZOOM };
	const FIELD = '[data-rmd-fzp-field]';

	function clamp( value, min, max, fallback ) {
		return Math.min(
			max,
			Math.max( min, isNaN( value ) ? fallback : value )
		);
	}

	function clean( point ) {
		return {
			x: Math.round( clamp( point.x, 0, 100, 50 ) * 10 ) / 10,
			y: Math.round( clamp( point.y, 0, 100, 50 ) * 10 ) / 10,
			zoom:
				Math.round(
					clamp( point.zoom, MIN_ZOOM, MAX_ZOOM, MIN_ZOOM ) * 100
				) / 100,
		};
	}

	function controls( field ) {
		return {
			x: field.querySelector( '[data-rmd-fzp-x]' ),
			y: field.querySelector( '[data-rmd-fzp-y]' ),
			zoom: field.querySelector( '[data-rmd-fzp-zoom]' ),
			range: field.querySelector( '[data-rmd-fzp-zoom-range]' ),
		};
	}

	function activeInput( field ) {
		return field.querySelector( '[data-rmd-fzp-active]' );
	}

	function active( field ) {
		return activeInput( field ).value;
	}

	function store( field, target ) {
		const inputs = {};
		field
			.querySelectorAll( '[data-rmd-fzp-store="' + target + '"]' )
			.forEach( ( input ) => {
				inputs[ input.dataset.rmdFzpAxis ] = input;
			} );
		return inputs;
	}

	function hasOwn( field, target ) {
		return '' === target || ! store( field, target ).x.disabled;
	}

	function pointOf( field, target ) {
		const inputs = store( field, hasOwn( field, target ) ? target : '' );
		return clean( {
			x: parseFloat( inputs.x.value ),
			y: parseFloat( inputs.y.value ),
			zoom: parseFloat( inputs.zoom.value ),
		} );
	}

	function write( field, target, point ) {
		const inputs = store( field, target );
		Object.keys( inputs ).forEach( ( axis ) => {
			inputs[ axis ].value = point[ axis ];
			inputs[ axis ].disabled = false;
		} );
	}

	function inherit( field, target ) {
		const inputs = store( field, target );
		Object.keys( inputs ).forEach( ( axis ) => {
			inputs[ axis ].disabled = true;
		} );
	}

	function properties( element, point ) {
		element.style.setProperty( '--rmd-fzp-x', point.x + '%' );
		element.style.setProperty( '--rmd-fzp-y', point.y + '%' );
		element.style.setProperty(
			'--rmd-fzp-pos',
			point.x + '% ' + point.y + '%'
		);
		element.style.setProperty( '--rmd-fzp-zoom', String( point.zoom ) );
	}

	/**
	 * Shows the state of the hidden inputs: dot, controls, target buttons and
	 * previews.
	 *
	 * @param {Element} field        Field.
	 * @param {boolean} syncControls Also write the number inputs (not while the user types in them).
	 */
	function render( field, syncControls ) {
		const target = active( field );
		const point = pointOf( field, target );
		properties( field, point );
		field.classList.toggle( 'is-variant', '' !== target );
		field.classList.toggle( 'has-own', hasOwn( field, target ) );

		const inputs = controls( field );
		inputs.range.value = point.zoom;
		if ( syncControls ) {
			inputs.x.value = point.x;
			inputs.y.value = point.y;
			inputs.zoom.value = point.zoom;
		}

		field
			.querySelectorAll( '[data-rmd-fzp-target]' )
			.forEach( ( button ) => {
				const key = button.dataset.rmdFzpTarget;
				button.setAttribute(
					'aria-pressed',
					key === target ? 'true' : 'false'
				);
				button.classList.toggle(
					'has-own',
					'' !== key && hasOwn( field, key )
				);
			} );

		field
			.querySelectorAll( '[data-rmd-fzp-preview]' )
			.forEach( ( preview ) => {
				const key = preview.dataset.rmdFzpPreview;
				preview.classList.toggle( 'has-own', hasOwn( field, key ) );
				properties(
					preview.querySelector( '.rmd-fzp-preview__frame' ),
					pointOf( field, key )
				);
			} );
	}

	function edit( field, point, syncControls ) {
		write( field, active( field ), clean( point ) );
		render( field, syncControls );
	}

	function commit( field ) {
		activeInput( field ).dispatchEvent(
			new Event( 'change', { bubbles: true } )
		);
	}

	function readControls( field ) {
		const inputs = controls( field );
		return {
			x: parseFloat( inputs.x.value ),
			y: parseFloat( inputs.y.value ),
			zoom: parseFloat( inputs.zoom.value ),
		};
	}

	function fromPointer( picker, event ) {
		const field = picker.closest( FIELD );
		const rect = picker.getBoundingClientRect();
		const point = pointOf( field, active( field ) );
		point.x = ( ( event.clientX - rect.left ) / rect.width ) * 100;
		point.y = ( ( event.clientY - rect.top ) / rect.height ) * 100;
		edit( field, point, true );
	}

	document.addEventListener( 'pointerdown', function ( event ) {
		const picker = event.target.closest( FIELD + ' [data-rmd-fzp-picker]' );
		if ( ! picker ) {
			return;
		}
		event.preventDefault();
		picker.setPointerCapture( event.pointerId );
		picker.classList.add( 'is-dragging' );
		fromPointer( picker, event );
	} );

	// Also fires on pointercancel, so the guides never stay visible.
	document.addEventListener( 'lostpointercapture', function ( event ) {
		if ( event.target.matches( FIELD + ' [data-rmd-fzp-picker]' ) ) {
			event.target.classList.remove( 'is-dragging' );
		}
	} );

	document.addEventListener( 'pointermove', function ( event ) {
		const picker = event.target.closest( FIELD + ' [data-rmd-fzp-picker]' );
		if ( picker && picker.hasPointerCapture( event.pointerId ) ) {
			fromPointer( picker, event );
		}
	} );

	document.addEventListener( 'pointerup', function ( event ) {
		const picker = event.target.closest( FIELD + ' [data-rmd-fzp-picker]' );
		if ( picker ) {
			commit( picker.closest( FIELD ) );
		}
	} );

	document.addEventListener( 'input', function ( event ) {
		const field = event.target.closest( FIELD );
		if ( ! field || event.target.matches( '[data-rmd-fzp-store]' ) ) {
			return;
		}
		if ( event.target.matches( '[data-rmd-fzp-zoom-range]' ) ) {
			controls( field ).zoom.value = event.target.value;
		}
		edit( field, readControls( field ), false );
	} );

	// The visible controls have no name; a finished edit is passed on to a
	// named input, so the media modal saves it.
	document.addEventListener(
		'change',
		function ( event ) {
			const field = event.target.closest( FIELD );
			if (
				field &&
				event.target.matches(
					'[data-rmd-fzp-x], [data-rmd-fzp-y], [data-rmd-fzp-zoom], [data-rmd-fzp-zoom-range]'
				)
			) {
				event.stopPropagation();
				render( field, true );
				commit( field );
			}
		},
		true
	);

	document.addEventListener( 'click', function ( event ) {
		const field = event.target.closest( FIELD );
		if ( ! field ) {
			return;
		}

		const target = event.target.closest( '[data-rmd-fzp-target]' );
		if ( target ) {
			activeInput( field ).value = target.dataset.rmdFzpTarget;
			render( field, true );
			return;
		}

		if ( event.target.closest( '[data-rmd-fzp-reset]' ) ) {
			edit( field, CENTER, true );
			commit( field );
			return;
		}

		if ( event.target.closest( '[data-rmd-fzp-inherit]' ) ) {
			inherit( field, active( field ) );
			render( field, true );
			commit( field );
		}
	} );
} )();
