/**
 * Media library: focal point picker (click or drag) and zoom for every
 * [data-rmd-fzp-field], see includes/Admin/MediaField.php. Uses event
 * delegation because the media modal renders its fields after page load.
 *
 * A "change" event on a named input finishes an edit; the media modal saves
 * the attachment fields on that event. The previews read the custom
 * properties set here.
 */
( function () {
	const MIN_ZOOM = 1;
	const MAX_ZOOM = 3;

	function clamp( value, min, max, fallback ) {
		return Math.min(
			max,
			Math.max( min, isNaN( value ) ? fallback : value )
		);
	}

	function parts( field ) {
		return {
			x: field.querySelector( '[data-rmd-fzp-x]' ),
			y: field.querySelector( '[data-rmd-fzp-y]' ),
			zoom: field.querySelector( '[data-rmd-fzp-zoom]' ),
			range: field.querySelector( '[data-rmd-fzp-zoom-range]' ),
		};
	}

	function read( field ) {
		const inputs = parts( field );
		return {
			x: clamp( parseFloat( inputs.x.value ), 0, 100, 50 ),
			y: clamp( parseFloat( inputs.y.value ), 0, 100, 50 ),
			zoom: clamp(
				parseFloat( inputs.zoom.value ),
				MIN_ZOOM,
				MAX_ZOOM,
				MIN_ZOOM
			),
		};
	}

	function show( field, point, syncInputs ) {
		field.style.setProperty( '--rmd-fzp-x', point.x + '%' );
		field.style.setProperty( '--rmd-fzp-y', point.y + '%' );
		field.style.setProperty(
			'--rmd-fzp-pos',
			point.x + '% ' + point.y + '%'
		);
		field.style.setProperty( '--rmd-fzp-zoom', String( point.zoom ) );

		const inputs = parts( field );
		inputs.range.value = point.zoom;
		if ( syncInputs ) {
			inputs.x.value = point.x;
			inputs.y.value = point.y;
			inputs.zoom.value = point.zoom;
		}
	}

	function commit( field ) {
		parts( field ).x.dispatchEvent(
			new Event( 'change', { bubbles: true } )
		);
	}

	function fromPointer( picker, event ) {
		const field = picker.closest( '[data-rmd-fzp-field]' );
		const rect = picker.getBoundingClientRect();
		const point = read( field );
		point.x =
			Math.round(
				clamp(
					( ( event.clientX - rect.left ) / rect.width ) * 100,
					0,
					100,
					50
				) * 10
			) / 10;
		point.y =
			Math.round(
				clamp(
					( ( event.clientY - rect.top ) / rect.height ) * 100,
					0,
					100,
					50
				) * 10
			) / 10;
		show( field, point, true );
	}

	document.addEventListener( 'pointerdown', function ( event ) {
		const picker = event.target.closest(
			'[data-rmd-fzp-field] [data-rmd-fzp-picker]'
		);
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
		if (
			event.target.matches( '[data-rmd-fzp-field] [data-rmd-fzp-picker]' )
		) {
			event.target.classList.remove( 'is-dragging' );
		}
	} );

	document.addEventListener( 'pointermove', function ( event ) {
		const picker = event.target.closest(
			'[data-rmd-fzp-field] [data-rmd-fzp-picker]'
		);
		if ( picker && picker.hasPointerCapture( event.pointerId ) ) {
			fromPointer( picker, event );
		}
	} );

	document.addEventListener( 'pointerup', function ( event ) {
		const picker = event.target.closest(
			'[data-rmd-fzp-field] [data-rmd-fzp-picker]'
		);
		if ( picker ) {
			commit( picker.closest( '[data-rmd-fzp-field]' ) );
		}
	} );

	document.addEventListener( 'input', function ( event ) {
		const field = event.target.closest( '[data-rmd-fzp-field]' );
		if ( ! field ) {
			return;
		}
		if ( event.target.matches( '[data-rmd-fzp-zoom-range]' ) ) {
			parts( field ).zoom.value = event.target.value;
		}
		show( field, read( field ), false );
	} );

	// The range has no name, so its change is passed on to a named input.
	document.addEventListener(
		'change',
		function ( event ) {
			if (
				event.target.matches(
					'[data-rmd-fzp-field] [data-rmd-fzp-zoom-range]'
				)
			) {
				event.stopPropagation();
				commit( event.target.closest( '[data-rmd-fzp-field]' ) );
			}
		},
		true
	);

	document.addEventListener( 'click', function ( event ) {
		const reset = event.target.closest(
			'[data-rmd-fzp-field] [data-rmd-fzp-reset]'
		);
		if ( ! reset ) {
			return;
		}
		const field = reset.closest( '[data-rmd-fzp-field]' );
		show( field, { x: 50, y: 50, zoom: MIN_ZOOM }, true );
		commit( field );
	} );
} )();
