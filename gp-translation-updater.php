<?php
/*
 * Plugin Name:       GP Translation Updater
 * Plugin URI:        https://blog.meloniq.net/gp-translation-updater/
 *
 * Description:       Adds a support for automatic updates of translations from custom GlotPress instances.
 * Tags:              glotpress, translation, updater
 *
 * Requires at least: 4.9
 * Requires PHP:      7.4
 * Version:           1.0
 *
 * Author:            MELONIQ.NET
 * Author URI:        https://meloniq.net/
 *
 * License:           GPLv2
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.html
 *
 * Text Domain:       gp-translation-updater
 */

namespace Meloniq\GpTranslationUpdater;

// If this file is accessed directly, then abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GPUPD_TD', 'gp-translation-updater' );


