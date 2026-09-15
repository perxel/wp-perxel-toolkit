<?php

namespace Perxel_Example;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin settings storage. One option (PXEX_OPTION_KEY) holding an array.
 * Read through the typed accessors, never get_option() directly.
 */
class Settings {

	public static function defaults() {
		return array(
			'api_key' => '',
			'enabled' => true,
		);
	}

	/**
	 * @return array Saved settings merged over defaults.
	 */
	public static function all() {
		$saved = get_option( PXEX_OPTION_KEY, array() );
		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
	}

	/**
	 * @param string $key One of the defaults keys.
	 * @return mixed
	 */
	public static function get( $key ) {
		$all = self::all();
		return $all[ $key ] ?? null;
	}

	public static function api_key() {
		return (string) self::get( 'api_key' );
	}

	public static function is_enabled() {
		return (bool) self::get( 'enabled' );
	}

	/**
	 * @param array $values Partial or full settings.
	 */
	public static function update( array $values ) {
		update_option( PXEX_OPTION_KEY, wp_parse_args( $values, self::all() ) );
	}

	public static function reset() {
		update_option( PXEX_OPTION_KEY, self::defaults() );
	}

	/**
	 * Sanitise a raw settings-form submission into a storable array.
	 *
	 * @param array $raw $_POST-shaped input (already unslashed).
	 * @return array
	 */
	public static function sanitize( array $raw ) {
		return array(
			'api_key' => isset( $raw['api_key'] ) ? sanitize_text_field( $raw['api_key'] ) : '',
			'enabled' => ! empty( $raw['enabled'] ),
		);
	}
}
