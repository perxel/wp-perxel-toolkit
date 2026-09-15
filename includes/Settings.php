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
	 * Save one module's settings_fields() values, leaving every other
	 * module's stored settings untouched. Used by a module's own dedicated
	 * settings page (see Module::settings_page()); the main settings form
	 * saves every module's fields at once via sanitize() instead.
	 *
	 * @param string               $slug   Module slug.
	 * @param array<string,mixed>  $values Already-sanitised field values.
	 */
	public static function update_module_settings( string $slug, array $values ): void {
		$module_settings = self::get( 'module_settings' );
		$module_settings[ $slug ] = $values;
		self::update( array( 'module_settings' => $module_settings ) );
	}

	/**
	 * Sanitise one module's settings_fields() values from raw input, by
	 * field type. Shared by the main settings form (sanitize(), all modules
	 * at once) and a module's own dedicated settings page (one module).
	 *
	 * @param class-string<\Perxel_Toolkit\Modules\Module> $module Module class.
	 * @param array                                         $values Raw field values (already unslashed), keyed by field key.
	 * @return array<string,mixed>
	 */
	public static function sanitize_module_fields( string $module, array $values ): array {
		$sanitized = array();

		foreach ( $module::settings_fields() as $field ) {
			$key = $field['key'];

			switch ( $field['type'] ) {
				case 'roles':
					$roles             = array_keys( wp_roles()->roles );
					$sanitized[ $key ] = array_values(
						array_intersect(
							array_map( 'sanitize_key', (array) ( $values[ $key ] ?? array() ) ),
							$roles
						)
					);
					break;

				case 'list':
					$lines             = preg_split( '/[\r\n]+/', (string) ( $values[ $key ] ?? '' ) );
					$lines             = array_filter( array_map( 'trim', $lines ), 'strlen' );
					$sanitized[ $key ] = array_values( array_unique( $lines ) );
					break;

				default: // 'toggle'.
					$sanitized[ $key ] = ! empty( $values[ $key ] );
			}
		}

		return $sanitized;
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

			// A module with its own dedicated settings_page() doesn't render
			// its fields on this form, so there is nothing to read here -
			// preserve its stored values instead of zeroing them out.
			if ( $module::settings_page() ) {
				$module_settings[ $slug ] = $current_settings[ $slug ] ?? $module::default_settings();
				continue;
			}

			$values = isset( $submitted_settings[ $slug ] ) && is_array( $submitted_settings[ $slug ] ) ? $submitted_settings[ $slug ] : array();

			$module_settings[ $slug ] = self::sanitize_module_fields( $module, $values );
		}

		return array(
			'modules'         => $modules,
			'module_settings' => $module_settings,
		);
	}
}
