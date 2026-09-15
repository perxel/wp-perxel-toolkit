<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restricts block-editor capabilities for non-administrator roles.
 *
 * Ported from wp-mu-plugins/editor-restrictions.php. The roles list and the
 * settings overrides are filterable until this module gets its own settings
 * UI - a project's theme can adjust either via functions.php.
 */
class Editor_Restrictions extends Module {

	public static function slug(): string {
		return 'editor-restrictions';
	}

	public static function label(): string {
		return __( 'Editor Restrictions', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Lock down block-editor capabilities for non-admin roles.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'feature';
	}

	/**
	 * Roles the restrictions apply to. Filterable per project.
	 */
	public static function restricted_roles(): array {
		return (array) apply_filters(
			'pxtk_editor_restrictions_roles',
			array( 'editor', 'author', 'contributor', 'subscriber' )
		);
	}

	/**
	 * Block editor settings to override for restricted roles. Filterable per
	 * project - see the original mu-plugin for the full menu of available
	 * keys (fixed toolbar, focus mode, custom colors/gradients/fonts,
	 * spacing controls, etc.).
	 */
	public static function overrides(): array {
		return (array) apply_filters(
			'pxtk_editor_restrictions_settings',
			array(
				'canLockBlocks'                          => false,
				'codeEditingEnabled'                      => false,
				'__experimentalCanUserUseUnfilteredHTML' => false,
			)
		);
	}

	public function register(): void {
		add_filter( 'block_editor_settings_all', array( $this, 'apply' ), 10, 2 );
	}

	/**
	 * @param array $settings Block editor settings.
	 * @return array
	 */
	public function apply( $settings ) {
		if ( ! $this->is_restricted_user() ) {
			return $settings;
		}

		foreach ( self::overrides() as $key => $value ) {
			$settings[ $key ] = $value;
		}

		return $settings;
	}

	private function is_restricted_user(): bool {
		$user = wp_get_current_user();
		if ( ! $user->exists() ) {
			return false;
		}

		foreach ( self::restricted_roles() as $role ) {
			if ( in_array( $role, (array) $user->roles, true ) ) {
				return true;
			}
		}

		return false;
	}
}
