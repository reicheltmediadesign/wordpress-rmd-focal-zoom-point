# Changelog

All notable changes to this plugin are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [Unreleased]

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
