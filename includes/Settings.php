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

	/**
	 * @return array<string,array<string,mixed>> Module slug => its
	 *         settings_fields() defaults, for modules that declare any.
	 */
	public static function default_module_settings(): array {
		$defaults = array();
		foreach ( Registry::all() as $module ) {
			if ( $module::settings_fields() ) {
				$defaults[ $module::slug() ] = $module::default_settings();
			}
		}
		return $defaults;
	}

	public static function defaults(): array {
		return array(
			'modules'         => self::default_modules(),
			'module_settings' => self::default_module_settings(),
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

		$saved_module_settings = isset( $saved['module_settings'] ) && is_array( $saved['module_settings'] ) ? $saved['module_settings'] : array();

		$module_settings = array();
		foreach ( self::default_module_settings() as $slug => $field_defaults ) {
			$module_settings[ $slug ] = wp_parse_args(
				isset( $saved_module_settings[ $slug ] ) && is_array( $saved_module_settings[ $slug ] ) ? $saved_module_settings[ $slug ] : array(),
				$field_defaults
			);
		}
		$saved['module_settings'] = $module_settings;

		return $saved;
	}

	/**
	 * @return array<string,mixed> One module's settings_fields() values,
	 *         merged over its defaults. Empty for a module with no fields.
	 */
	public static function module_settings( string $slug ): array {
		$all = self::all();
		return $all['module_settings'][ $slug ] ?? array();
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

		$submitted_settings = isset( $raw['module_settings'] ) && is_array( $raw['module_settings'] ) ? $raw['module_settings'] : array();
		$current_settings   = self::get( 'module_settings' );

		$modules         = array();
		$module_settings = array();

		foreach ( Registry::all() as $module ) {
			$slug = $module::slug();

			if ( ! $module::is_available() ) {
				$modules[ $slug ] = ! empty( $current[ $slug ] );
				if ( $module::settings_fields() ) {
					$module_settings[ $slug ] = $current_settings[ $slug ] ?? $module::default_settings();
				}
				continue;
			}

			$modules[ $slug ] = ! empty( $submitted[ $slug ] );

			$fields = $module::settings_fields();
			if ( ! $fields ) {
				continue;
			}

			$values    = isset( $submitted_settings[ $slug ] ) && is_array( $submitted_settings[ $slug ] ) ? $submitted_settings[ $slug ] : array();
			$sanitized = array();

			foreach ( $fields as $field ) {
				$key = $field['key'];

				if ( 'roles' === $field['type'] ) {
					$roles                = array_keys( wp_roles()->roles );
					$sanitized[ $key ]    = array_values(
						array_intersect(
							array_map( 'sanitize_key', (array) ( $values[ $key ] ?? array() ) ),
							$roles
						)
					);
					continue;
				}

				$sanitized[ $key ] = ! empty( $values[ $key ] );
			}

			$module_settings[ $slug ] = $sanitized;
		}

		return array(
			'modules'         => $modules,
			'module_settings' => $module_settings,
		);
	}
}
