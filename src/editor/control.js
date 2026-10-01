/**
 * Focal point picker with zoom for one image. Without onChange it edits the
 * image itself (saved right away); with value/onChange it is controlled, e.g.
 * for a per-block override. Exposed to themes as
 * window.rmdFocalZoomPoint.FocalZoomControl.
 */
import {
	Button,
	FocalPointPicker,
	Notice,
	RangeControl,
	Spinner,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import {
	CENTER,
	MAX_ZOOM,
	MIN_ZOOM,
	imageUrl,
	isDefault,
	normalize,
	useImagePoint,
	useSaveImagePoint,
} from './data';

const fromPicker = ( value ) => ( {
	x: Math.round( value.x * 1000 ) / 10,
	y: Math.round( value.y * 1000 ) / 10,
} );

export function FocalZoomControl( { id, value, onChange } ) {
	const { media, point: imagePoint, canEdit } = useImagePoint( id );
	const saveImage = useSaveImagePoint( id );
	const controlled = typeof onChange === 'function';

	if ( ! id ) {
		return null;
	}
	if ( ! media ) {
		return <Spinner />;
	}
	if ( ! controlled && ! canEdit ) {
		return (
			<Notice status="info" isDismissible={ false }>
				{ __(
					'You are not allowed to change this image. You can still adjust it for this block only.',
					'rmd-focal-zoom-point'
				) }
			</Notice>
		);
	}

	const point = controlled ? normalize( value ) || imagePoint : imagePoint;
	const set = ( next ) => {
		const clean = normalize( next ) || CENTER;
		if ( controlled ) {
			onChange( clean );
		} else {
			saveImage( clean );
		}
	};

	return (
		<div className="rmd-fzp-control">
			<FocalPointPicker
				__nextHasNoMarginBottom
				url={ imageUrl( media ) }
				value={ { x: point.x / 100, y: point.y / 100 } }
				onDrag={ ( next ) =>
					set( { ...point, ...fromPicker( next ) } )
				}
				onChange={ ( next ) =>
					set( { ...point, ...fromPicker( next ) } )
				}
			/>
			<RangeControl
				__nextHasNoMarginBottom
				__next40pxDefaultSize
				label={ __( 'Zoom', 'rmd-focal-zoom-point' ) }
				help={ __(
					'Enlarges the image around the focal point. Also lets you move the focus sideways in a tall image shown in a square or circle.',
					'rmd-focal-zoom-point'
				) }
				value={ point.zoom }
				min={ MIN_ZOOM }
				max={ MAX_ZOOM }
				step={ 0.05 }
				withInputField
				onChange={ ( zoom ) =>
					set( { ...point, zoom: zoom ?? MIN_ZOOM } )
				}
			/>
			<Button
				variant="secondary"
				size="small"
				disabled={ isDefault( point ) }
				onClick={ () => set( CENTER ) }
			>
				{ __( 'Reset', 'rmd-focal-zoom-point' ) }
			</Button>
		</div>
	);
}
