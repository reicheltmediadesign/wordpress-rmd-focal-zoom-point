=== RMD Focal Zoom Point ===
Contributors: reicheltmediadesign
Tags: focal point, image, crop, zoom, media library
Requires at least: 6.8
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 0.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Set a focal point and zoom on every image in the media library or right where it is used. Cropped images keep their important part in view, in any theme.

== Description ==

Themes crop images all the time: wide banners, square cards, round portraits. RMD Focal Zoom Point stores the important part of each image and a zoom factor, and applies them wherever the image is cropped.

* Focal point and zoom in the media library, with previews of typical crops.
* The same control in the block editor sidebar, saved to the image or only for one block.
* Zoom keeps the focal point near the middle and also lets you move the focus sideways in a tall image shown as a circle.
* Works with any theme for cropped images (object-fit: cover); images that are not cropped look as before.
* Theme API for background images, own templates and own blocks (docs/theme-integration.md, also under Media → Focal Point & Zoom).

== Installation ==

1. Upload the plugin zip under Plugins → Add New → Upload, or copy the folder to `wp-content/plugins/`.
2. Activate the plugin.
3. Open an image in the media library and use the field "Focal point & zoom".

== Frequently Asked Questions ==

= Why does zoom have no effect in my theme? =

Zoom needs a container that clips the image. Core image and featured image blocks get one from the plugin; for images in your own templates add the class `rmd-fzp-frame` to the clipping element. See docs/theme-integration.md.

= My hero uses a background image. =

Print `rmd_fzp_style( $image_id )` into its style attribute and use `background-position: var(--rmd-fzp-pos, center)`.

== Changelog ==

= 0.1.0 =
* Initial release.
