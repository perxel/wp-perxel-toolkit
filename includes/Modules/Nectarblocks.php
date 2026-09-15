<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Nectar Blocks integration: when the Nectar Blocks plugin (which supplies
 * its own block library) is active, the inserter should offer those blocks
 * - plus any of the project's own custom blocks - and not WordPress's
 * default core blocks.
 *
 * Unlike the original per-project hardcoded blocklist (naming every core
 * block to hide), this allowlists by prefix: any registered block whose
 * name does NOT start with `core/` is kept (Nectar Blocks' own blocks, the
 * project's custom blocks, and any other plugin's blocks); every `core/*`
 * block is hidden unless a project opts one back in via the
 * `pxtk_nectarblocks_keep_core_blocks` filter. That also means a future
 * WordPress core block is hidden automatically, with no list to maintain.
 */
class Nectarblocks extends Module {

	const PLUGIN_FILE = 'nectar-blocks/nectar-blocks.php';

	public static function slug(): string {
		return 'nectarblocks';
	}

	public static function label(): string {
		return __( 'Nectar Blocks', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Hide default core blocks - keep only Nectar Blocks and the project\'s own blocks in the inserter.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'integration';
	}

	public static function dependency(): array {
		return array(
			'label'       => __( 'Nectar Blocks', 'perxel-toolkit' ),
			'check'       => static function () {
				if ( ! function_exists( 'is_plugin_active' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}
				return is_plugin_active( self::PLUGIN_FILE );
			},
			'wporg_slug'  => 'nectar-blocks',
			'install_url' => 'https://wordpress.org/plugins/nectar-blocks/',
		);
	}

	public function register(): void {
		add_filter( 'allowed_block_types_all', array( $this, 'filter_allowed_blocks' ) );
	}

	/**
	 * @param bool|array $allowed_block_types Currently allowed block types.
	 * @return array
	 */
	public function filter_allowed_blocks( $allowed_block_types ) {
		/**
		 * Core block names to keep even though this module hides `core/*`
		 * by default, e.g. `array( 'core/quote', 'core/list' )` for a blog.
		 *
		 * @param string[] $keep_core Core block names to keep.
		 */
		$keep_core = (array) apply_filters( 'pxtk_nectarblocks_keep_core_blocks', array() );

		$registered = array_keys( \WP_Block_Type_Registry::get_instance()->get_all_registered() );

		return array_values(
			array_filter(
				$registered,
				static function ( $name ) use ( $keep_core ) {
					if ( 0 !== strpos( $name, 'core/' ) ) {
						return true;
					}
					return in_array( $name, $keep_core, true );
				}
			)
		);
	}
}
