<?php

namespace Perxel_Toolkit\Modules;

use Perxel_Toolkit\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The fixed list of modules this plugin ships, and the boot loop that wires
 * each one's hooks when it is both enabled in Settings and available (its
 * dependency, if any, is satisfied).
 */
class Registry {

	const MODULES = array(
		Editor_Restrictions::class,
		Admin_Page_Guard::class,
		Featured_Image_Column::class,
		Featured_Posts::class,
		Disable_Comments::class,
		Media_Sizes::class,
		Gravity_Forms::class,
		Acf::class,
		Nectarblocks::class,
	);

	/**
	 * @return class-string<Module>[]
	 */
	public static function all(): array {
		return self::MODULES;
	}

	/**
	 * @param string $group 'feature' or 'integration'.
	 * @return class-string<Module>[]
	 */
	public static function by_group( string $group ): array {
		return array_values(
			array_filter(
				self::MODULES,
				static function ( $module ) use ( $group ) {
					return $module::group() === $group;
				}
			)
		);
	}

	/**
	 * @return class-string<Module>|null
	 */
	public static function find( string $slug ) {
		foreach ( self::MODULES as $module ) {
			if ( $module::slug() === $slug ) {
				return $module;
			}
		}
		return null;
	}

	public static function boot(): void {
		foreach ( self::MODULES as $module ) {
			if ( Settings::is_module_enabled( $module::slug() ) && $module::is_available() ) {
				( new $module() )->register();
			}
		}
	}
}
