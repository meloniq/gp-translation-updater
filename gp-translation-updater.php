<?php
/**
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
 *
 * Network:           true
 *
 * @package Meloniq\GpTranslationUpdater
 */

namespace Meloniq\GpTranslationUpdater;

// If this file is accessed directly, then abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'GPUPD_TD', 'gp-translation-updater' );

/**
 * Setup plugin data.
 *
 * @return void
 */
function setup() {
	global $gpupd_updater;

	require_once trailingslashit( __DIR__ ) . 'src/class-updater.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-plugins-updater.php';
	require_once trailingslashit( __DIR__ ) . 'src/class-themes-updater.php';

	$gpupd_updater['plugins-updater'] = new Plugins_Updater();
	$gpupd_updater['themes-updater']  = new Themes_Updater();
}
add_action( 'after_setup_theme', 'Meloniq\GpTranslationUpdater\setup' );
