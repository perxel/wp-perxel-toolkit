<?php

namespace Perxel_Toolkit\Modules;

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
	 * Wire the module's hooks. Only called when the module is enabled in
	 * Settings AND is_available() is true.
	 */
	abstract public function register(): void;
}
