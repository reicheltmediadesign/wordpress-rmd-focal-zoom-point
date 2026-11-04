# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

### Added

- Own focal point and zoom per crop variant. In the media library, “All crops” edits the general values; clicking a crop’s preview (or its button) edits only that crop. Crops without own values use the general values.
- Crop variants `wide` (16 / 5), `landscape` (3 / 2), `portrait` (3 / 4) and `round` (square & circle), filter `rmd_fzp_variants` with `key`, `label`, `ratio`, `round`.
- Core image and featured image blocks use the variant matching their aspect ratio or the “Rounded” style, also in the editor canvas. The block panel offers “Own values for …” for that crop.
- Theme API: argument `rmd_fzp_variant` of `wp_get_attachment_image()`, parameter `$variant` of `rmd_fzp_point()`, `rmd_fzp_style()` and `rmd_fzp_image_tag()`, filters `rmd_fzp_block_variant` (PHP) and `rmdFzp.blockVariant` (JS), prop `variant` of `FocalZoomControl`, `useImagePoint( id, variant )`, `normalizeFocus()`, `blockVariant()` and `variants` in `window.rmdFocalZoomPoint`.

### Changed

- Post meta `_rmd_fzp` may contain `variants` (`{ key: { x, y, zoom } }`). Existing values stay valid.
- `useImagePoint()` also returns `focus`, everything stored on the image.

### Deprecated

- Filter `rmd_fzp_previews`, use `rmd_fzp_variants`. Entries of the old filter get their keys from the labels.

## [0.1.2] – 2026-10-01

### Added

- The focal point picker in the media library shows the center guides while dragging.

### Changed

- Center guides swapped colors: vertical pink, horizontal turquoise.
- Crop previews in the media library are laid out in a grid with at most two columns, so they stay readable in the narrow sidebar of the attachment details.

## [0.1.1] – 2026-10-01

### Added

- Dashed center guides in the media library crop previews (vertical turquoise, horizontal pink), hidden on hover.

## [0.1.0] – 2026-10-01

### Added

- Focal point and zoom (1–3) stored on every image (post meta `_rmd_fzp`, also in the REST API).
- Field “Focal point & zoom” in the media library with picker, number inputs, zoom slider and crop previews (filter `rmd_fzp_previews`).
- Panel “Focal point & zoom” in the block editor for image blocks and blocks registered with `rmd_fzp_blocks`. Changes are saved to the image, or only for one block (“Adjust for this block only”, attribute `rmdFzp`). Live preview in the canvas.
- Inline `object-position` and custom properties (`--rmd-fzp-x`, `-y`, `-pos`, `-zoom`, `-ratio`) on content images, `wp_get_attachment_image()` and supported blocks. An existing `object-position` (cover, media & text) is kept.
- Zoom inside clipping frames (`rmd-fzp-frame`); core image and featured image blocks are wrapped automatically.
- The cover block takes over the image’s focal point when an image is chosen.
- Theme API: `rmd_fzp_point()`, `rmd_fzp_style()`, `rmd_fzp_image_tag()`, the `rmd_fzp` argument of `wp_get_attachment_image()`, `window.rmdFocalZoomPoint` in the editor.
- Theme integration guide (`docs/theme-integration.md`), also shown under Media → Focal Point & Zoom.
- Automatic updates from GitHub releases.
