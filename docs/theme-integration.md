# Theme integration guide

RMD Focal Zoom Point stores a **focal point** (x/y in percent) and a **zoom** factor (1–3) on every image, optionally with own values per **crop variant** (e.g. one for round crops). This guide explains what works in any theme without changes and what a theme has to do for the rest.

## In short

| Part | Works automatically? | Theme work |
| --- | --- | --- |
| Editing: media library field, block editor panel, REST | Yes | – |
| Focal point on `<img>` (content images, `wp_get_attachment_image()`, featured image block) | Yes | Crop with `object-fit: cover` as usual |
| Zoom on core image and featured image blocks | Yes | Optional: match the border radius of the frame |
| Zoom on images in your own templates | No | Add the class `rmd-fzp-frame` to the clipping container |
| Images you output as CSS background | No | Use `rmd_fzp_style()` and `var(--rmd-fzp-pos)` |
| Your own blocks | No | Register them with the filter `rmd_fzp_blocks` |
| Own values per crop (variants) | For core image and featured image blocks with an aspect ratio or the "Rounded" style | Define your crops with `rmd_fzp_variants`, name the variant where you output an image |

Everything degrades gracefully: without the plugin, or for images without values, nothing is added to the markup.

## How it works

For every `<img>` it can reach, the plugin adds inline styles:

```html
<img class="wp-image-42 rmd-fzp-zoom" data-rmd-fzp="1"
     style="object-position: 30% 20%; --rmd-fzp-x: 30%; --rmd-fzp-y: 20%; --rmd-fzp-pos: 30% 20%; --rmd-fzp-zoom: 1.5;">
```

- `object-position` only has an effect when the image is cropped (`object-fit: cover`). On images that are not cropped it does nothing, so it is safe everywhere. An inline style also wins over theme CSS, unless the theme uses `!important`.
- An `object-position` that is already inline (cover block or media & text with their own focal point) is kept. A per-use override from the block editor replaces it.
- The class `rmd-fzp-zoom` is only added when the zoom is above 1.
- `data-rmd-fzp` marks markup the plugin has handled.

Zoom needs a clipping frame. The plugin stylesheet (`assets/css/focal-zoom.css`) scales a zoomed image **only inside an element with the class `rmd-fzp-frame`**. It moves the image so the focal point sits as close to the centre of the frame as the image edges allow. This is also how zoom lets a focal point move sideways in a tall image shown in a circle: without zoom, the image does not stick out at the sides. With zoom it does, so it can move.

The stylesheet uses the individual transform properties `scale` and `translate`, not `transform`. A theme's own `transform` (hover zoom, parallax) still applies on top of them.

## 1. Cropped images: nothing to do

Images from post content, `wp_get_attachment_image()`, `the_post_thumbnail()` and the core image and featured image blocks get their values automatically. Crop them as usual:

```css
.card__image img {
	width: 100%;
	height: 100%;
	object-fit: cover;
	/* No object-position here – or keep it as a fallback, the inline style wins. */
}
```

Do not set `object-position` with `!important`, or the focal point cannot take effect.

## 2. Zoom in your own templates

Add the class `rmd-fzp-frame` to the element that clips the image. The plugin stylesheet gives it `overflow: hidden`.

```php
<div class="card__image rmd-fzp-frame">
	<?php echo wp_get_attachment_image( $image_id, 'large' ); ?>
</div>
```

Things to keep in mind:

- The frame must have the size of the visible image area (the image usually fills it with `width: 100%; height: 100%; object-fit: cover`).
- Rounded corners: put the radius on the frame. While an image is zoomed, the plugin removes the image's own radius (`border-radius: 0 !important`), because a scaled radius would cut odd shapes into the frame.
- Do not set `scale` or `translate` (the individual properties) on images in a frame. `transform` is fine.
- The plugin sets `transform-origin: 50% 50%` on zoomed images. If your hover effect relies on another origin, set it on a wrapper instead.

The plugin already wraps zoomed images of the core image and featured image blocks in `<span class="rmd-fzp-frame rmd-fzp-wrap">`. For the block style "Rounded" it rounds the frame. If your theme gives content images a radius, repeat it for the frame:

```css
.entry-content .wp-block-image img,
.entry-content .wp-block-image .rmd-fzp-wrap {
	border-radius: 18px;
}
```

## 3. Images you output yourself

`wp_get_attachment_image()` takes the plugin's values automatically. Pass `rmd_fzp` in the attributes to use other values for this one image, or `false` to leave it untouched:

```php
// Values for this use only (x and y in percent, zoom 1–3).
echo wp_get_attachment_image( $id, 'large', false, [
	'rmd_fzp' => [ 'x' => 40, 'y' => 25, 'zoom' => 1.6 ],
] );

// Without focal point and zoom.
echo wp_get_attachment_image( $id, 'large', false, [ 'rmd_fzp' => false ] );
```

For an `<img>` you build by hand, use `rmd_fzp_image_tag()`:

```php
$html = sprintf( '<img src="%s" alt="">', esc_url( $url ) );
if ( function_exists( 'rmd_fzp_image_tag' ) ) {
	$html = rmd_fzp_image_tag( $html, $id );
}
echo $html;
```

## 4. Background images

The plugin cannot know where a theme shows an image as a CSS background. Print the custom properties with `rmd_fzp_style()` and use them in your CSS:

```php
$style = 'background-image: url(' . esc_url( wp_get_attachment_image_url( $id, 'full' ) ) . ');';
if ( function_exists( 'rmd_fzp_style' ) ) {
	$style .= ' ' . rmd_fzp_style( $id );
}
printf( '<div class="hero" style="%s">…</div>', esc_attr( $style ) );
```

```css
.hero {
	background-size: cover;
	background-position: var(--rmd-fzp-pos, center);
}
```

`rmd_fzp_style()` returns `--rmd-fzp-x`, `--rmd-fzp-y`, `--rmd-fzp-pos`, `--rmd-fzp-zoom` and `--rmd-fzp-ratio` (width / height of the image). It returns an empty string for images without values.

Zoom on a background needs the size that `cover` would give, and only your theme knows the box. Two ways:

- **Recommended:** show the image as an `<img>` that fills the box (`position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover`) inside an element with `rmd-fzp-frame`. Focal point and zoom then work automatically, as in section 2.
- **Box with a known size**, e.g. a background fixed to the viewport (`background-attachment: fixed`): compute `cover` yourself and multiply it by the zoom:

```css
.hero {
	background-attachment: fixed;
	background-position: var(--rmd-fzp-pos, center);
	/* cover for the viewport, times the zoom */
	background-size: calc(max(100vw, 100vh * var(--rmd-fzp-ratio, 1.5)) * var(--rmd-fzp-zoom, 1)) auto;
}
```

With `background-attachment: fixed` the position refers to the viewport, not to the element. Horizontally it matches exactly; vertically it is only approximate for elements that are lower than the window.

## 5. Your own blocks

Register blocks that show an image with the filter `rmd_fzp_blocks`. The value is the name of the attribute that holds the attachment ID:

```php
add_filter( 'rmd_fzp_blocks', function ( array $blocks ) {
	$blocks['mytheme/hero']     = 'imageId';
	$blocks['mytheme/parallax'] = 'imageId';
	return $blocks;
} );
```

Add the filter before `init` (in `functions.php` or when your plugin loads). For a registered block, the plugin does the following:

- **Block editor:** shows the "Focal point & zoom" panel with the switch "Adjust for this block only", and adds the attribute `rmdFzp` (`{ x, y, zoom }`) for the override. The attribute lives in the block comment, not in the saved markup, so static blocks do not become invalid.
- **Output:** if the block contains an `<img>` with the class `wp-image-{ID}`, the values go onto that image. Add `rmd-fzp-frame` to its clipping container for zoom. Otherwise the custom properties and `data-rmd-fzp` go onto the block's outer element, together with the class `rmd-fzp-zoom` when zoomed. This is the case for background images (see section 4).
- **Editor canvas:** the block wrapper gets the custom properties and the class `rmd-fzp-editor`, plus `rmd-fzp-zoom-on` when zoomed. Rules based on `var(--rmd-fzp-pos)` therefore show the result while editing too. For images inside such a block, the plugin stylesheet applies `object-position` and zoom in the editor and clips at the image's parent element.

The editor script is enqueued at priority 5 of `enqueue_block_editor_assets`, before block scripts. This way the attribute is also added to blocks that register in their own editor script.

## 6. Crop variants

One focal point does not always fit every crop: the landscape and portrait crops look right, but in a circle the face sits off-centre. Editors can therefore set **own values per crop variant**. Each variant is a preview below the picker in the media library; clicking a preview edits that crop only. Variants without own values use the general values ("All crops").

### Defining the variants

The defaults are `wide` (16 / 5), `landscape` (3 / 2), `portrait` (3 / 4) and `round` (square & circle). Replace them with the crops your theme uses:

```php
add_filter( 'rmd_fzp_variants', function () {
	return [
		[ 'key' => 'hero', 'label' => __( 'Hero', 'mytheme' ), 'ratio' => '1440 / 380' ],
		[ 'key' => 'card', 'label' => __( 'Card', 'mytheme' ), 'ratio' => '4 / 3' ],
		[ 'key' => 'team', 'label' => __( 'Team portrait', 'mytheme' ), 'ratio' => '1', 'round' => true ],
	];
} );
```

- `key`: lowercase letters, digits, `-` and `_`, at most 40 characters. It is stored with the image, so keep it stable. Without a key, one is derived from the label.
- `ratio`: width / height of the crop, also the aspect ratio of the preview.
- `round`: circular crop.

Values for a variant your theme no longer defines stay on the image and come back when the variant is defined again.

The old filter `rmd_fzp_previews` (before 0.2.0) still works but is deprecated. Its entries get their keys from the labels.

### Which variant applies

- **Core image block:** the block style "Rounded" takes the round variant. An aspect ratio set in the block takes the variant with the closest ratio; round variants count as squares. Without aspect ratio and rounding the image is not cropped, and the general values apply.
- **Featured image block:** the same, from its aspect ratio.
- **Your own blocks:** the same rules, if they have the attributes `aspectRatio` or `className`. Otherwise set the variant with the filters below.
- **Your templates:** name the variant where you output the image (see below). Without one, the general values apply.

A per-block override ("Adjust for this block only") always wins over the variant.

```php
// wp_get_attachment_image(): the argument rmd_fzp_variant.
echo wp_get_attachment_image( $id, 'medium', false, [ 'rmd_fzp_variant' => 'team' ] );

// The functions take the variant as their last parameter.
$style = rmd_fzp_style( $id, null, false, 'hero' );
$html  = rmd_fzp_image_tag( $html, $id, null, 'card' );
$point = rmd_fzp_point( $id, null, 'team' );
```

Own blocks that always crop the same way set their variant with a PHP filter for the front end and a JS filter for the editor canvas and panel:

```php
add_filter( 'rmd_fzp_block_variant', function ( $variant, $name, $attrs ) {
	return 'mytheme/team-member' === $name ? 'team' : $variant;
}, 10, 3 );
```

```js
wp.hooks.addFilter( 'rmdFzp.blockVariant', 'mytheme/variant', ( variant, name ) =>
	'mytheme/team-member' === name ? 'team' : variant
);
```

In the block editor the panel of such a block shows the switch "Own values for …". Switched on, the control edits the values of that variant on the image; switched off, it edits the general values.

## 7. The control in your own editor panels

Themes with their own editor panels (e.g. a page hero in the document sidebar) can reuse the control. Add `rmd-fzp-editor` to your script's dependencies and use `window.rmdFocalZoomPoint`:

| Export | Purpose |
| --- | --- |
| `FocalZoomControl` | `{ id }` edits the image and saves it right away; `{ id, variant }` edits the own values of a crop variant; `{ id, value, onChange }` is controlled (your own storage). |
| `useImagePoint( id, variant )` | Hook returning `{ media, focus, point, canEdit }`. `point` is `{ x, y, zoom }` for the variant (its own values, else the general ones, else the centre); `variant` is optional. `focus` is everything stored, `null` if nothing. |
| `styleFor( point )` | Style object with the custom properties, for a preview element. |
| `normalize( value )` | Clamps and rounds a point; `null` if it is invalid. |
| `normalizeFocus( value )` | The same for the stored value with variants. |
| `blockVariant( name, attributes )` | Variant key of a block (aspect ratio, "Rounded", filter `rmdFzp.blockVariant`), `null` if unknown. |
| `variants` | The crop variants: `[ { key, label, ratio, round } ]`, `ratio` as a number. |

```js
const { FocalZoomControl, useImagePoint, styleFor } = window.rmdFocalZoomPoint || {};

function HeroImagePanel( { imageId } ) {
	if ( ! FocalZoomControl || ! imageId ) {
		return null;
	}
	return wp.element.createElement( FocalZoomControl, { id: imageId } );
}
```

## 8. Reference

### PHP functions

Guard calls with `function_exists()`, so the theme keeps working without the plugin.

| Function | Returns |
| --- | --- |
| `rmd_fzp_point( int $id, ?array $override = null, ?string $variant = null )` | `[ 'x' => float, 'y' => float, 'zoom' => float ]`, the centre and zoom 1 if nothing is set |
| `rmd_fzp_style( int $id, ?array $override = null, bool $object_position = false, ?string $variant = null )` | CSS custom properties for a `style` attribute, empty if nothing is set |
| `rmd_fzp_image_tag( string $html, int $id, ?array $override = null, ?string $variant = null )` | `$html` with the values on its first `<img>` |

`$variant` is the key of a crop variant: its own values are used if the image has them, else the general ones. Unknown keys are ignored. `wp_get_attachment_image()` takes it as the attribute `rmd_fzp_variant`.

### Filters

| Filter | Purpose |
| --- | --- |
| `rmd_fzp_blocks` | Blocks with panel and override: `[ 'block/name' => 'idAttribute' ]`. Default: `core/image => id`. |
| `rmd_fzp_variants` | Crop variants, shown as previews in the media library: `[ 'key', 'label', 'ratio', 'round' ]`. |
| `rmd_fzp_block_variant` | Variant of a block: `( ?string $variant, string $name, array $attrs )`. JS counterpart: `rmdFzp.blockVariant`. |
| `rmd_fzp_previews` | Deprecated since 0.2.0, use `rmd_fzp_variants`. |
| `rmd_fzp_load_style` | Return `false` to skip `assets/css/focal-zoom.css` (then add the zoom rules to your theme). |

### CSS custom properties

| Property | Value |
| --- | --- |
| `--rmd-fzp-x`, `--rmd-fzp-y` | Focal point in percent, e.g. `30%` |
| `--rmd-fzp-pos` | Both, for `object-position` or `background-position` |
| `--rmd-fzp-zoom` | Zoom factor, `1`–`3` |
| `--rmd-fzp-ratio` | Width / height of the image (only from `rmd_fzp_style()` and on block wrappers) |

Always give a fallback, e.g. `var(--rmd-fzp-pos, center)`.

### Classes and attributes

| Name | Meaning |
| --- | --- |
| `rmd-fzp-frame` | Clipping container; zoom only applies inside it |
| `rmd-fzp-wrap` | The `<span>` the plugin puts around zoomed core images |
| `rmd-fzp-zoom` | Image (or block wrapper) with zoom above 1 |
| `rmd-fzp-editor`, `rmd-fzp-zoom-on` | Block wrapper in the editor canvas |
| `data-rmd-fzp` | Markup the plugin has handled |

### Data

- Post meta `_rmd_fzp` on the attachment: `{ "x": 30, "y": 20, "zoom": 1.5 }`, with own values per crop variant `{ "x": 30, "y": 20, "zoom": 1.5, "variants": { "round": { "x": 45, "y": 25, "zoom": 1.2 } } }`. The centre without zoom and without variants is not stored.
- REST: `meta._rmd_fzp` on `/wp/v2/media/{id}`, writable for users who may edit the image. `null` deletes it.
- Block attribute `rmdFzp` for per-use overrides.

## Checklist

1. Cropped images use `object-fit: cover` without `object-position: … !important`.
2. Containers that clip images carry `rmd-fzp-frame`, with the same border radius as the image.
3. Background images print `rmd_fzp_style()` and use `var(--rmd-fzp-pos, center)`, or become `<img>` elements.
4. Own blocks are registered with `rmd_fzp_blocks`. Their editor styles use the custom properties.
5. The crop variants match the theme's crops (`rmd_fzp_variants`), and templates name the variant they crop to (`rmd_fzp_variant`).
6. All calls are guarded with `function_exists()`, and all `var()` calls have fallbacks.
