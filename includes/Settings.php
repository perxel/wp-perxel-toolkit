<?php

namespace Perxel_Toolkit;

use Perxel_Toolkit\Modules\Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings storage. One option (PXTK_OPTION_KEY) holding a
 * `modules` map of slug => enabled bool. Read through the typed accessors,
 * never get_option() directly.
 */
class Settings {

	/**
	 * @return array<string,bool> Every registered module's slug => true.
	 */
	public static function default_modules(): array {
		$defaults = array();
		foreach ( Registry::all() as $module ) {
			$defaults[ $module::slug() ] = true;
		}
		return $defaults;
	}

	public static function defaults(): array {
		return array(
			'modules' => self::default_modules(),
		);
	}

	/**
	 * @return array Saved settings merged over defaults.
	 */
	public static function all(): array {
		$saved = get_option( PXTK_OPTION_KEY, array() );
		$saved = is_array( $saved ) ? $saved : array();

		$saved['modules'] = wp_parse_args(
			isset( $saved['modules'] ) && is_array( $saved['modules'] ) ? $saved['modules'] : array(),
			self::default_modules()
		);

		return $saved;
	}

	/**
	 * @param string $key One of the defaults keys.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	public static function is_module_enabled( string $slug ): bool {
		$modules = self::get( 'modules' );
		return ! empty( $modules[ $slug ] );
	}

	/**
	 * @param array $values Partial or full settings.
	 */
	public static function update( array $values ): void {
		update_option( PXTK_OPTION_KEY, wp_parse_args( $values, self::all() ) );
	}

	public static function reset(): void {
		update_option( PXTK_OPTION_KEY, self::defaults() );
	}

	/**
	 * Sanitise a raw settings-form submission into a storable array.
	 *
	 * A module whose dependency isn't currently satisfied renders a
	 * disabled checkbox, which browsers never include in the POST body -
	 * for those, keep the stored value instead of reading a missing field
	 * as "turned off".
	 *
	 * @param array $raw $_POST-shaped input (already unslashed).
	 * @return array
	 */
	public static function sanitize( array $raw ): array {
		$submitted = isset( $raw['modules'] ) && is_array( $raw['modules'] ) ? $raw['modules'] : array();
		$current   = self::get( 'modules' );

		$modules = array();
		foreach ( Registry::all() as $module ) {
			$slug = $module::slug();

			if ( ! $module::is_available() ) {
				$modules[ $slug ] = ! empty( $current[ $slug ] );
				continue;
			}

			$modules[ $slug ] = ! empty( $submitted[ $slug ] );
		}

		return array( 'modules' => $modules );
	}
}
