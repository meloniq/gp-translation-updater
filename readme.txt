=== GP Translation Updater ===
Contributors: meloniq
Tags: glotpress, translation, updater
Tested up to: 6.8
Stable tag: 1.0
License: GPLv2
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Adds a support for automatic updates of translations from custom GlotPress instances.

== Description ==

Adds a support for automatic updates of translations from custom [GlotPress](https://wordpress.org/plugins/glotpress/) instances.
Requires the [GlotPress Translation API](https://wordpress.org/plugins/gp-translation-api/) plugin to be installed and activated on the GlotPress instance.


== External services ==

This plugin connects to an API of your GlotPress instance, to obtain translation updates information, it's needed to tell the WordPress update system if there are updates available.

It sends the GlotPress project path, locales in use, and current translations data, every time the WordPress update system checks for updates.


== Changelog ==

= 1.0 =
* Initial release.
