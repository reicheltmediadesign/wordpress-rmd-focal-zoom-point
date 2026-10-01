/**
 * Values and data access shared by the editor parts.
 */
import { useSelect, useDispatch } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import { store as noticesStore } from '@wordpress/notices';
import { useCallback, useEffect, useMemo, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

const config = window.rmdFzpConfig || {};

export const ATTRIBUTE = 'rmdFzp';
export const META = '_rmd_fzp';
export const MIN_ZOOM = Number( config.minZoom ) || 1;
export const MAX_ZOOM = Number( config.maxZoom ) || 3;
export const BLOCKS = config.blocks || { 'core/image': 'id' };
export const CENTER = Object.freeze( { x: 50, y: 50, zoom: MIN_ZOOM } );

const SAVE_DELAY = 600;
const EMPTY = Object.freeze( { media: null, meta: null, canEdit: false } );

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
 * Edited attachment record (a pick shows before it is saved), its point and
 * whether the current user may change the image.
 *
 * @param {number} id Attachment ID, 0 for none.
 */
export function useImagePoint( id ) {
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

	const point = useMemo( () => normalize( meta ) || CENTER, [ meta ] );
	return { media, point, canEdit };
}

/**
 * Writes a point to the image: shown at once, saved shortly after the last
 * change (and right away when the control goes away).
 *
 * @param {number} id Attachment ID.
 */
export function useSaveImagePoint( id ) {
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
		( point ) => {
			editEntityRecord( 'postType', 'attachment', id, {
				meta: { [ META ]: isDefault( point ) ? null : point },
			} );
			clearTimeout( timer.current );
			timer.current = setTimeout( save, SAVE_DELAY );
		},
		[ id, editEntityRecord, save ]
	);
}
