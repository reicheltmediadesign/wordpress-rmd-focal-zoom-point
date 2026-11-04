/**
 * Values and data access shared by the editor parts.
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';
import { applyFilters } from '@wordpress/hooks';
import { useCallback, useEffect, useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const config = window.rmdFzpConfig || {};

export const ATTRIBUTE = 'rmdFzp';
export const META = '_rmd_fzp';
export const MIN_ZOOM = Number( config.minZoom ) || 1;
export const MAX_ZOOM = Number( config.maxZoom ) || 3;
export const BLOCKS = config.blocks || { 'core/image': 'id' };
export const VARIANTS = Array.isArray( config.variants ) ? config.variants : [];
export const CENTER = Object.freeze( { x: 50, y: 50, zoom: MIN_ZOOM } );

const SAVE_DELAY = 600;
const EMPTY = Object.freeze( { media: null, meta: null, canEdit: false } );
const KEY_PATTERN = /^[a-z0-9][a-z0-9_-]{0,39}$/;
const RATIO_PATTERN = /^\s*(\d+(?:\.\d+)?)\s*(?:\/\s*(\d+(?:\.\d+)?)\s*)?$/;
const ROUNDED_PATTERN = /(^|\s)is-style-rounded(\s|$)/;

const clamp = ( value, min, max ) => Math.min( max, Math.max( min, value ) );

/**
 * Same rules as Domain\FocalPoint::from() in PHP.
 *
 * @param {*} value Raw value.
 * @return {{x: number, y: number, zoom: number}|null} Normalized point.
 */
export function normalize( value ) {
	if ( ! value || typeof value !== 'object' ) {
		return null;
	}
	const x = Number( value.x );
	const y = Number( value.y );
	if (
		value.x === undefined ||
		value.y === undefined ||
		isNaN( x ) ||
		isNaN( y )
	) {
		return null;
	}
	const zoom = Number( value.zoom );
	return {
		x: Math.round( clamp( x, 0, 100 ) * 10 ) / 10,
		y: Math.round( clamp( y, 0, 100 ) * 10 ) / 10,
		zoom:
			Math.round(
				clamp( isNaN( zoom ) ? MIN_ZOOM : zoom, MIN_ZOOM, MAX_ZOOM ) *
					100
			) / 100,
	};
}

export const isDefault = ( point ) =>
	! point || ( point.x === 50 && point.y === 50 && point.zoom === MIN_ZOOM );

const baseOf = ( focus ) =>
	focus ? { x: focus.x, y: focus.y, zoom: focus.zoom } : { ...CENTER };

const compose = ( base, variants ) =>
	Object.keys( variants ).length ? { ...base, variants } : base;

/**
 * Same rules as Domain\ImageFocus::from() in PHP: general values plus own
 * values per crop variant, `{ x, y, zoom, variants?: { key: point } }`.
 *
 * @param {*} value Raw value, e.g. the post meta.
 * @return {Object|null} Normalized value, null if nothing is valid.
 */
export function normalizeFocus( value ) {
	if ( ! value || typeof value !== 'object' ) {
		return null;
	}
	const base = normalize( value );
	const variants = {};
	if ( value.variants && typeof value.variants === 'object' ) {
		Object.keys( value.variants ).forEach( ( key ) => {
			const point = KEY_PATTERN.test( key )
				? normalize( value.variants[ key ] )
				: null;
			if ( point ) {
				variants[ key ] = point;
			}
		} );
	}
	if ( ! base && ! Object.keys( variants ).length ) {
		return null;
	}
	return compose( base || { ...CENTER }, variants );
}

export const hasVariant = ( focus, variant ) =>
	!! ( variant && focus?.variants?.[ variant ] );

/**
 * Values for one use, as Domain\ImageFocus::resolve() in PHP.
 *
 * @param {Object|null} focus   Normalized value.
 * @param {string|null} variant Variant key.
 * @return {Object|null} Point, null if there is nothing to apply.
 */
export function resolve( focus, variant ) {
	if ( hasVariant( focus, variant ) ) {
		return focus.variants[ variant ];
	}
	const base = baseOf( focus );
	return focus && ! isDefault( base ) ? base : null;
}

/**
 * New value with other general values (variant null) or other values for
 * one variant.
 *
 * @param {Object|null} focus   Normalized value.
 * @param {string|null} variant Variant key.
 * @param {Object}      point   Normalized point.
 * @return {Object} New value.
 */
export function withPoint( focus, variant, point ) {
	const variants = { ...( focus?.variants || {} ) };
	if ( variant ) {
		variants[ variant ] = point;
	}
	return compose( variant ? baseOf( focus ) : point, variants );
}

export function withoutVariant( focus, variant ) {
	const variants = { ...( focus?.variants || {} ) };
	delete variants[ variant ];
	return compose( baseOf( focus ), variants );
}

const isEmpty = ( focus ) =>
	! focus ||
	( isDefault( baseOf( focus ) ) &&
		! Object.keys( focus.variants || {} ).length );

/**
 * Width / height from "16/9", "3 / 2", "1.5" or a number, as
 * Domain\Variant::parse_ratio() in PHP.
 *
 * @param {*} value Raw value, e.g. the aspectRatio attribute.
 * @return {number|null} Ratio, null for "auto" or invalid values.
 */
export function parseRatio( value ) {
	if ( typeof value === 'number' ) {
		return value > 0 ? value : null;
	}
	const match =
		typeof value === 'string' ? RATIO_PATTERN.exec( value ) : null;
	if ( ! match ) {
		return null;
	}
	const width = parseFloat( match[ 1 ] );
	const height = match[ 2 ] ? parseFloat( match[ 2 ] ) : 1;
	return width > 0 && height > 0 ? width / height : null;
}

/**
 * Same rules as Domain\Variant::match() in PHP: round crops take a round
 * variant, others the one with the closest aspect ratio.
 *
 * @param {number|null} ratio Width / height of the crop.
 * @param {boolean}     round Circular crop.
 * @return {string|null} Variant key.
 */
export function matchVariant( ratio, round ) {
	let candidates = VARIANTS;
	let aspect = ratio;
	if ( round ) {
		candidates = VARIANTS.filter( ( variant ) => variant.round );
		aspect = aspect ?? 1;
	}
	if ( ! aspect || aspect <= 0 || ! candidates.length ) {
		return null;
	}
	let best = null;
	let distance = Infinity;
	candidates.forEach( ( variant ) => {
		const current = Math.abs( Math.log( variant.ratio / aspect ) );
		if ( current < distance - 1e-9 ) {
			best = variant.key;
			distance = current;
		}
	} );
	return best;
}

/**
 * Crop variant of a block, as Blocks::variant() in PHP. Themes adjust it with
 * the JS filter rmdFzp.blockVariant( variant, name, attributes ).
 *
 * @param {string} name       Block name.
 * @param {Object} attributes Block attributes.
 * @return {string|null} Variant key.
 */
export function blockVariant( name, attributes ) {
	const ratio = parseRatio( attributes?.aspectRatio );
	const round = ROUNDED_PATTERN.test( attributes?.className || '' );
	const variant =
		ratio !== null || round ? matchVariant( ratio, round ) : null;
	const filtered = applyFilters(
		'rmdFzp.blockVariant',
		variant,
		name,
		attributes
	);
	return VARIANTS.some( ( item ) => item.key === filtered ) ? filtered : null;
}

export const variantLabel = ( key ) =>
	VARIANTS.find( ( variant ) => variant.key === key )?.label || key;

/**
 * Custom properties as the PHP side writes them.
 *
 * @param {{x: number, y: number, zoom: number}} point Point.
 * @return {Object} Style object.
 */
export function styleFor( point ) {
	return {
		'--rmd-fzp-x': point.x + '%',
		'--rmd-fzp-y': point.y + '%',
		'--rmd-fzp-pos': point.x + '% ' + point.y + '%',
		'--rmd-fzp-zoom': String( point.zoom ),
	};
}

export function imageUrl( media ) {
	const sizes =
		( media && media.media_details && media.media_details.sizes ) || {};
	const size = sizes.medium_large || sizes.large || sizes.full;
	return size ? size.source_url : media?.source_url;
}

/**
 * Edited attachment record (a pick shows before it is saved), its values and
 * whether the current user may change the image.
 *
 * `focus` is everything stored on the image (null if nothing). `point` are the
 * values for the given crop variant: its own ones, else the general ones,
 * else the center.
 *
 * @param {number}      id      Attachment ID, 0 for none.
 * @param {string|null} variant Crop variant of the use.
 */
export function useImagePoint( id, variant = null ) {
	const { media, meta, canEdit } = useSelect(
		( select ) => {
			if ( ! id ) {
				return EMPTY;
			}
			const core = select( coreStore );
			const record = core.getEntityRecord( 'postType', 'attachment', id );
			const edited = record
				? core.getEditedEntityRecord( 'postType', 'attachment', id )
				: null;
			return {
				media: record || null,
				meta: edited && edited.meta ? edited.meta[ META ] : null,
				canEdit: !! core.canUser( 'update', {
					kind: 'postType',
					name: 'attachment',
					id,
				} ),
			};
		},
		[ id ]
	);

	const focus = useMemo( () => normalizeFocus( meta ), [ meta ] );
	const point = useMemo(
		() => resolve( focus, variant ) || CENTER,
		[ focus, variant ]
	);
	return { media, focus, point, canEdit };
}

/**
 * Writes the values of an image (shape of normalizeFocus()): shown at once,
 * saved shortly after the last change (and right away when the control goes
 * away).
 *
 * @param {number} id Attachment ID.
 */
export function useSaveImageFocus( id ) {
	const { editEntityRecord, saveEditedEntityRecord } =
		useDispatch( coreStore );
	const { createErrorNotice } = useDispatch( noticesStore );
	const timer = useRef( null );

	const save = useCallback( () => {
		timer.current = null;
		saveEditedEntityRecord( 'postType', 'attachment', id, {
			throwOnError: true,
		} ).catch( ( error ) => {
			createErrorNotice(
				__(
					'The focal point could not be saved to the image:',
					'rmd-focal-zoom-point'
				) +
					' ' +
					( error?.message || String( error ) ),
				{ type: 'snackbar' }
			);
		} );
	}, [ id, saveEditedEntityRecord, createErrorNotice ] );

	useEffect(
		() => () => {
			if ( timer.current ) {
				clearTimeout( timer.current );
				save();
			}
		},
		[ save ]
	);

	return useCallback(
		( focus ) => {
			editEntityRecord( 'postType', 'attachment', id, {
				meta: { [ META ]: isEmpty( focus ) ? null : focus },
			} );
			clearTimeout( timer.current );
			timer.current = setTimeout( save, SAVE_DELAY );
		},
		[ id, editEntityRecord, save ]
	);
}
