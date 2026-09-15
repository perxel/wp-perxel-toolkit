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

	/** 'feature' or 'integration'. */
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
	 * Wire the module's hooks. Only called when the module is enabled in
	 * Settings AND is_available() is true.
	 */
	abstract public function register(): void;
}
