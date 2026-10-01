# RMD Focal Zoom Point

WordPress plugin that stores a **focal point** and a **zoom** factor on every image. Wherever a theme crops an image (wide banner, square card, round portrait), the important part stays in view. Built by [reichelt media.design](https://reicheltmedia.design).

## Features

- **Set it once on the image.** A "Focal point & zoom" field in the media library: click the important part or drag the dot, set the zoom, check previews of typical crops.
- **Or right where the image is used.** A panel in the block editor sidebar for image blocks and blocks your theme registers. Changes are saved to the image; the switch "Adjust for this block only" keeps different values for one place.
- **Zoom** enlarges the image around the focal point and keeps it as close to the middle as the edges allow. It also lets you move the focus sideways in a tall image shown in a square or circle, where the focal point alone has no room.
- **Works with any theme** for cropped `<img>` elements (`object-fit: cover`): content images, `wp_get_attachment_image()`, featured images, core image blocks with an aspect ratio or the "Rounded" style. Images that are not cropped look exactly as before.
- **Live preview** in the block editor canvas.
- **Cover block:** when you choose an image, its focal point is copied into the cover block's own focal point.
- **Theme API** for background images, own templates and own blocks; see the [theme integration guide](docs/theme-integration.md).
- Automatic updates from GitHub releases.

## Requirements

WordPress 6.8 or newer, PHP 8.1 or newer.

## Installation

1. Download `rmd-focal-zoom-point.zip` from the latest [release](https://github.com/reicheltmediadesign/wordpress-rmd-focal-zoom-point/releases). **Do not use the “Source code” downloads** – they lack the built editor script, translations and the update library.
2. In WordPress go to Plugins → Add New → Upload Plugin, choose the zip and activate it.
3. Open an image in the media library and use the field **Focal point & zoom**.

Later releases are offered under Dashboard → Updates like any other plugin.

## Usage

**Media → Focal Point & Zoom** explains the plugin to editors and shows the theme integration guide for developers.

| Where | What |
| --- | --- |
| Media library (modal and edit screen) | Field "Focal point & zoom": picker, horizontal/vertical in percent, zoom 1–3, reset, previews |
| Block editor → image block | Panel "Focal point & zoom"; values are saved to the image, or only for this block |
| Block editor → blocks registered by the theme | Same panel |
| Cover block | Takes the image's focal point when the image is chosen |

## For theme developers

What works automatically, and what a theme has to do for zoom on its own containers, background images and own blocks, is described in **[docs/theme-integration.md](docs/theme-integration.md)**. The guide is also shipped with the plugin and shown in the admin under Media → Focal Point & Zoom.

The short version:

```php
// Background image: custom properties for the style attribute.
$style .= function_exists( 'rmd_fzp_style' ) ? rmd_fzp_style( $image_id ) : '';

// Own blocks: panel and per-block override.
add_filter( 'rmd_fzp_blocks', fn( $blocks ) => $blocks + [ 'mytheme/hero' => 'imageId' ] );
```

```css
.hero { background-position: var(--rmd-fzp-pos, center); }
```

```html
<!-- Zoom in own templates: mark the clipping container. -->
<div class="card__image rmd-fzp-frame"><img …></div>
```

## Privacy

The plugin stores no personal data and makes no external requests, except for checking GitHub for plugin updates from the WordPress admin.

## Uninstalling

Focal points and zoom values stay on the images (post meta `_rmd_fzp`). They are part of your content, and reinstalling the plugin brings them back.

## License

GPL-2.0-or-later. Uses [plugin-update-checker](https://github.com/YahnisElsts/plugin-update-checker) (MIT).
