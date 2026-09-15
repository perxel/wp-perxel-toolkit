<?php
/**
 * Plugin Name:       Perxel Example
 * Plugin URI:        https://github.com/perxel/wp-example
 * Description:        A short description of what this plugin does.
 * Version:           0.0.1
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Perxel
 * Author URI:        https://perxel.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       perxel-example
 *
 * @package Perxel_Example
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PXEX_VERSION', '0.0.1' );
define( 'PXEX_FILE', __FILE__ );
define( 'PXEX_DIR', __DIR__ );
define( 'PXEX_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PXEX_OPTION_KEY', 'pxex_settings' );

/**
 * Human-readable product name. A brand name, deliberately not translated.
 */
define( 'PXEX_NAME', 'Perxel Example' );

/**
 * PSR-4-ish autoloader for Perxel_Example\* -> includes/*.php. The namespace
 * root matches the slug (perxel-example -> Perxel_Example) so WordPress Plugin
 * Check accepts it as the plugin prefix with no suppression.
 */
spl_autoload_register(
	static function ( $class_name ) {
		if ( strpos( $class_name, 'Perxel_Example\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, strlen( 'Perxel_Example\\' ) );
		$path     = PXEX_DIR . '/includes/' . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $path ) ) {
			require $path;
		}
	}
);

/**
 * Shared Perxel admin-UI kit. Standalone, versioned independently of this
 * plugin (github.com/perxel/wp-plugin-ui); vendored into vendor/perxel-ui/ via
 * bin/update-ui.sh. Overwriting it can never change plugin behaviour - the
 * loader keeps the highest registered version across active plugins and a
 * second copy is inert. We host the kit's component showcase as a hidden
 * maintainer-only screen, so suppress its own Tools page.
 */
define( 'PERXEL_UI_SHOWCASE_HOSTED', true );

if ( is_readable( PXEX_DIR . '/vendor/perxel-ui/loader.php' ) ) {
	require_once PXEX_DIR . '/vendor/perxel-ui/loader.php';
	Perxel_UI_Loader::register( '0.21.0', PXEX_DIR . '/vendor/perxel-ui', PXEX_URL . '/vendor/perxel-ui' );
}

register_activation_hook( __FILE__, array( 'Perxel_Example\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		// Translations for a wordpress.org-hosted plugin load automatically since
		// WP 4.6 - no load_plugin_textdomain() call needed. JS strings are wired
		// per-screen with wp_set_script_translations() (see Admin::assets).
		Perxel_Example\Plugin::instance()->boot();
	}
);
