<?php
/**
 * Plugin Name:       Perxel Toolkit
 * Plugin URI:        https://github.com/perxel/wp-perxel-toolkit
 * Description:        Toggleable admin/editor features and third-party plugin integrations shared across Perxel projects.
 * Version:           0.0.6
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Perxel
 * Author URI:        https://perxel.com/
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       perxel-toolkit
 *
 * @package Perxel_Toolkit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PXTK_VERSION', '0.0.6' );
define( 'PXTK_FILE', __FILE__ );
define( 'PXTK_DIR', __DIR__ );
define( 'PXTK_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'PXTK_OPTION_KEY', 'pxtk_settings' );

/**
 * Human-readable product name. A brand name, deliberately not translated.
 */
define( 'PXTK_NAME', 'Perxel Toolkit' );

/**
 * PSR-4-ish autoloader for Perxel_Toolkit\* -> includes/*.php. The namespace
 * root matches the slug (perxel-toolkit -> Perxel_Toolkit) so WordPress Plugin
 * Check accepts it as the plugin prefix with no suppression.
 */
spl_autoload_register(
	static function ( $class_name ) {
		if ( strpos( $class_name, 'Perxel_Toolkit\\' ) !== 0 ) {
			return;
		}

		$relative = substr( $class_name, strlen( 'Perxel_Toolkit\\' ) );
		$path     = PXTK_DIR . '/includes/' . str_replace( '\\', '/', $relative ) . '.php';

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
if ( ! defined( 'PERXEL_UI_SHOWCASE_HOSTED' ) ) {
	define( 'PERXEL_UI_SHOWCASE_HOSTED', true );
}

if ( is_readable( PXTK_DIR . '/vendor/perxel-ui/loader.php' ) ) {
	require_once PXTK_DIR . '/vendor/perxel-ui/loader.php';
	Perxel_UI_Loader::register( '0.23.0', PXTK_DIR . '/vendor/perxel-ui', PXTK_URL . '/vendor/perxel-ui' );
}

register_activation_hook( __FILE__, array( 'Perxel_Toolkit\Plugin', 'activate' ) );

add_action(
	'plugins_loaded',
	static function () {
		// Translations for a wordpress.org-hosted plugin load automatically since
		// WP 4.6 - no load_plugin_textdomain() call needed. JS strings are wired
		// per-screen with wp_set_script_translations() (see Admin::assets).
		Perxel_Toolkit\Plugin::instance()->boot();
	}
);
