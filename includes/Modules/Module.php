<?php

namespace Perxel_Toolkit\Modules;

use Perxel_Toolkit\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Base class for a toggleable module. A "feature" module has no dependency
 * and is always available; an "integration" module wraps config for a
 * specific third-party plugin (or theme convention) and declares a
 * dependency() so the settings screen can grey it out until that dependency
 * is present.
 */
abstract class Module {

	/** Settings key and toggle field name - stable, never rename in place. */
	abstract public static function slug(): string;

	abstract public static function label(): string;

	abstract public static function description(): string;

	/**
	 * 'feature', 'integration', or another group slug (e.g. 'security').
	 * The settings screen only renders a section for a group that has at
	 * least one module in it - see includes/views/settings.php.
	 */
	abstract public static function group(): string;

	/**
	 * Integration modules override this. Null means no dependency (always
	 * available) - the default, correct for every feature module.
	 *
	 * @return array{label:string,check:callable,wporg_slug?:string,install_url?:string}|null
	 */
	public static function dependency() {
		return null;
	}

	/**
	 * Admin::PAGE_* slug for a module with its own dedicated settings screen,
	 * linked from its row on the main settings screen as a "Configure"
	 * button. Null (default) means the toggle is the module's only control.
	 */
	public static function settings_page(): ?string {
		return null;
	}

	/**
	 * Whether this module's dependency (if any) is currently satisfied.
	 */
	public static function is_available(): bool {
		$dependency = static::dependency();

		if ( null === $dependency || ! isset( $dependency['check'] ) ) {
			return true;
		}

		return (bool) call_user_func( $dependency['check'] );
	}

	/**
	 * Per-module settings fields, rendered on the settings screen as a
	 * "Configure" disclosure under the module's row. Empty array (the
	 * default) means the module has no configurable fields beyond its
	 * on/off toggle.
	 *
	 * Each field is `[ 'key', 'type', 'label', 'default' ]`. `key` must be
	 * unique within the module and stable (stored under it, never rename in
	 * place). `type` is one of:
	 *  - 'toggle': a single on/off switch. `default` is bool.
	 *  - 'roles':  a multi-select of the site's roles (administrator
	 *              excluded). `default` is a string[] of role slugs.
	 *  - 'list':   a free-text list, one entry per line (e.g. user logins,
	 *              URLs). `default` is a string[].
	 *
	 * Rendered either inline as a "Configure" disclosure on the main
	 * settings screen, or on the module's own settings_page() - see
	 * field_rows().
	 *
	 * @return array<int,array{key:string,type:string,label:string,default:mixed}>
	 */
	public static function settings_fields(): array {
		return array();
	}

	/**
	 * @return array<string,mixed> Field key => default value.
	 */
	public static function default_settings(): array {
		$defaults = array();
		foreach ( static::settings_fields() as $field ) {
			$defaults[ $field['key'] ] = $field['default'] ?? null;
		}
		return $defaults;
	}

	/**
	 * Render this module's settings_fields(), pre-filled with its current
	 * stored values, as Perxel_UI::rows() row specs. Shared by the inline
	 * "Configure" accordion (views/settings.php) and a module's own
	 * dedicated settings page. Empty array when the module has no fields.
	 *
	 * @return array<int,array{label:string,content:string}>
	 */
	public static function field_rows(): array {
		$fields = static::settings_fields();
		if ( ! $fields ) {
			return array();
		}

		$values = Settings::module_settings( static::slug() );
		$roles  = wp_roles()->get_names();
		unset( $roles['administrator'] );

		$rows = array();

		foreach ( $fields as $field ) {
			$key   = $field['key'];
			$value = $values[ $key ] ?? $field['default'];
			$name  = 'module_settings[' . static::slug() . '][' . $key . ']';

			switch ( $field['type'] ) {
				case 'roles':
					$control = \Perxel_UI::checkbox_group(
						array(
							'name'     => $name,
							'options'  => $roles,
							'selected' => (array) $value,
						)
					);
					break;

				case 'list':
					$control = '<textarea name="' . esc_attr( $name ) . '" rows="4" class="large-text code">'
						. esc_textarea( implode( "\n", (array) $value ) ) . '</textarea>';
					break;

				default: // 'toggle'.
					$control = \Perxel_UI::toggle(
						array(
							'name'    => $name,
							'checked' => ! empty( $value ),
							'label'   => $field['label'],
						)
					);
			}

			$rows[] = array(
				'label'   => $field['label'],
				'content' => $control,
			);
		}

		return $rows;
	}

	/**
	 * Wire the module's hooks. Only called when the module is enabled in
	 * Settings AND is_available() is true.
	 */
	abstract public function register(): void;
}
