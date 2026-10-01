/**
 * Block editor integration:
 *
 * - adds the override attribute (rmdFzp) to the supported blocks,
 * - a "Focal point & zoom" panel for them,
 * - the values on the block wrapper in the canvas, so the preview crops like
 *   the page (assets/css/focal-zoom.css, .rmd-fzp-editor),
 * - takes an image's focal point over into the cover block's own focal point
 *   when an image is chosen there,
 * - window.rmdFocalZoomPoint for themes, see docs/theme-integration.md.
 */
import { addFilter } from '@wordpress/hooks';
import { createHigherOrderComponent } from '@wordpress/compose';
import { InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import { useEffect, useRef } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

import { FocalZoomControl } from './control';
import {
	ATTRIBUTE,
	BLOCKS,
	isDefault,
	normalize,
	styleFor,
	useImagePoint,
} from './data';

addFilter(
	'blocks.registerBlockType',
	'rmd-fzp/attribute',
	( settings, name ) => {
		if ( ! BLOCKS[ name ] ) {
			return settings;
		}
		return {
			...settings,
			attributes: {
				...settings.attributes,
				[ ATTRIBUTE ]: { type: 'object' },
			},
		};
	}
);

function BlockPanel( { id, attributes, setAttributes } ) {
	const override = normalize( attributes[ ATTRIBUTE ] );
	const { point: imagePoint } = useImagePoint( id );

	return (
		<PanelBody
			title={ __( 'Focal point & zoom', 'rmd-focal-zoom-point' ) }
			initialOpen={ !! override }
		>
			<ToggleControl
				__nextHasNoMarginBottom
				label={ __(
					'Adjust for this block only',
					'rmd-focal-zoom-point'
				) }
				help={
					override
						? __(
								'These values apply only here. The image keeps its own values elsewhere.',
								'rmd-focal-zoom-point'
							)
						: __(
								'Changes are saved to the image right away and apply wherever it is used.',
								'rmd-focal-zoom-point'
							)
				}
				checked={ !! override }
				onChange={ ( on ) =>
					setAttributes( {
						[ ATTRIBUTE ]: on ? { ...imagePoint } : undefined,
					} )
				}
			/>
			{ override ? (
				<FocalZoomControl
					id={ id }
					value={ override }
					onChange={ ( next ) =>
						setAttributes( { [ ATTRIBUTE ]: next } )
					}
				/>
			) : (
				<FocalZoomControl id={ id } />
			) }
		</PanelBody>
	);
}

function CoverSync( { attributes, setAttributes } ) {
	const id = attributes.id || 0;
	const { media, point } = useImagePoint( id );
	const first = useRef( true );
	const pending = useRef( 0 );

	useEffect( () => {
		if ( first.current ) {
			first.current = false;
			return;
		}
		pending.current = id;
	}, [ id ] );

	useEffect( () => {
		if ( ! pending.current || pending.current !== id || ! media ) {
			return;
		}
		pending.current = 0;
		if ( ! isDefault( point ) ) {
			setAttributes( {
				focalPoint: { x: point.x / 100, y: point.y / 100 },
			} );
		}
	}, [ id, media, point, setAttributes ] );

	return null;
}

const withPanel = createHigherOrderComponent(
	( BlockEdit ) => ( props ) => {
		if ( 'core/cover' === props.name ) {
			return (
				<>
					<BlockEdit { ...props } />
					<CoverSync
						attributes={ props.attributes }
						setAttributes={ props.setAttributes }
					/>
				</>
			);
		}

		const attribute = BLOCKS[ props.name ];
		const id = attribute ? Number( props.attributes[ attribute ] ) || 0 : 0;
		if ( ! id || ! props.isSelected ) {
			return <BlockEdit { ...props } />;
		}

		return (
			<>
				<BlockEdit { ...props } />
				<InspectorControls>
					<BlockPanel
						id={ id }
						attributes={ props.attributes }
						setAttributes={ props.setAttributes }
					/>
				</InspectorControls>
			</>
		);
	},
	'withRmdFzpPanel'
);

const withPreview = createHigherOrderComponent(
	( BlockListBlock ) => ( props ) => {
		const attribute = BLOCKS[ props.name ];
		const id = attribute ? Number( props.attributes[ attribute ] ) || 0 : 0;
		const override = attribute
			? normalize( props.attributes[ ATTRIBUTE ] )
			: null;
		const { point } = useImagePoint( override ? 0 : id );

		let active = null;
		if ( override ) {
			active = override;
		} else if ( id && ! isDefault( point ) ) {
			active = point;
		}
		if ( ! active ) {
			return <BlockListBlock { ...props } />;
		}

		const wrapperProps = {
			...props.wrapperProps,
			style: {
				...( props.wrapperProps?.style || {} ),
				...styleFor( active ),
			},
		};
		const className = [
			props.className,
			'rmd-fzp-editor',
			active.zoom > 1 ? 'rmd-fzp-zoom-on' : '',
		]
			.filter( Boolean )
			.join( ' ' );

		return (
			<BlockListBlock
				{ ...props }
				wrapperProps={ wrapperProps }
				className={ className }
			/>
		);
	},
	'withRmdFzpPreview'
);

addFilter( 'editor.BlockEdit', 'rmd-fzp/panel', withPanel );
addFilter( 'editor.BlockListBlock', 'rmd-fzp/preview', withPreview );

window.rmdFocalZoomPoint = {
	FocalZoomControl,
	useImagePoint,
	styleFor,
	normalize,
};
