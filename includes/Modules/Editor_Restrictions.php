<?php

namespace Perxel_Toolkit\Modules;

use Perxel_Toolkit\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restricts block-editor capabilities for non-administrator roles.
 *
 * Ported from wp-mu-plugins/editor-restrictions.php. Roles and capabilities
 * are configured on the settings screen (see settings_fields()); the
 * pxtk_editor_restrictions_roles / _settings filters still apply on top, for
 * a project's theme to override via functions.php.
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

	public static function settings_fields(): array {
		return array(
			array(
				'key'     => 'restricted_roles',
				'type'    => 'roles',
				'label'   => __( 'Restricted roles', 'perxel-toolkit' ),
				'default' => array( 'editor', 'author', 'contributor', 'subscriber' ),
			),
			array(
				'key'     => 'disable_block_locking',
				'type'    => 'toggle',
				'label'   => __( 'Disable block locking', 'perxel-toolkit' ),
				'default' => true,
			),
			array(
				'key'     => 'disable_code_editor',
				'type'    => 'toggle',
				'label'   => __( 'Disable code editor mode', 'perxel-toolkit' ),
				'default' => true,
			),
			array(
				'key'     => 'disable_unfiltered_html',
				'type'    => 'toggle',
				'label'   => __( 'Disable unfiltered HTML', 'perxel-toolkit' ),
				'default' => true,
			),
		);
	}

	/**
	 * Roles the restrictions apply to. Filterable per project.
	 */
	public static function restricted_roles(): array {
		$roles = Settings::module_settings( self::slug() )['restricted_roles'] ?? array();

		return (array) apply_filters( 'pxtk_editor_restrictions_roles', (array) $roles );
	}

	/**
	 * Block editor settings to override for restricted roles. Filterable per
	 * project - see the original mu-plugin for the full menu of available
	 * keys (fixed toolbar, focus mode, custom colors/gradients/fonts,
	 * spacing controls, etc.) if a project needs more than the toggles on
	 * the settings screen.
	 */
	public static function overrides(): array {
		$settings  = Settings::module_settings( self::slug() );
		$overrides = array();

		if ( ! empty( $settings['disable_block_locking'] ) ) {
			$overrides['canLockBlocks'] = false;
		}
		if ( ! empty( $settings['disable_code_editor'] ) ) {
			$overrides['codeEditingEnabled'] = false;
		}
		if ( ! empty( $settings['disable_unfiltered_html'] ) ) {
			$overrides['__experimentalCanUserUseUnfilteredHTML'] = false;
		}

		return (array) apply_filters( 'pxtk_editor_restrictions_settings', $overrides );
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
