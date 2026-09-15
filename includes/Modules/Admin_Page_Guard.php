<?php

namespace Perxel_Toolkit\Modules;

use Perxel_Toolkit\Admin;
use Perxel_Toolkit\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restricts access to specific admin pages - redirects unauthorized users
 * to the dashboard and hides the matching menu items.
 *
 * Ported from wp-mu-plugins/admin-page-guard.php. The allowed-user and
 * restricted-page lists are configured on the module's own settings screen
 * (Admin::PAGE_ADMIN_PAGE_GUARD, see settings_fields()); the
 * pxtk_admin_page_guard_* filters still apply on top of the stored values,
 * for a project's theme to override via functions.php.
 */
class Admin_Page_Guard extends Module {

	const VIEW_AS_ACTION = 'pxtk_view_as_restricted';

	/** Ported from wp-mu-plugins/admin-page-guard.php's hardcoded list. */
	const DEFAULT_RESTRICTED_PAGES = array(
		'/wp-admin/admin.php?page=nectar-blocks'       => 'Nectar Blocks',
		'/wp-admin/edit.php?post_type=acf-field-group' => 'ACF Field Groups',
		'/wp-admin/admin.php?page=akeebabackupwp%2Fakeebabackupwp.php' => 'Akeeba Backup',
		'/wp-admin/plugins.php'                        => 'Plugins',
	);

	public static function slug(): string {
		return 'admin-page-guard';
	}

	public static function label(): string {
		return __( 'Admin Page Guard', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Restrict selected admin pages, redirect unauthorized users to the dashboard.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'security';
	}

	public static function settings_page(): ?string {
		return Admin::PAGE_ADMIN_PAGE_GUARD;
	}

	public static function settings_fields(): array {
		return array(
			array(
				'key'     => 'allowed_users',
				'type'    => 'users',
				'label'   => __( 'Allowed users', 'perxel-toolkit' ),
				'default' => array( 'phucbm' ),
			),
			array(
				'key'     => 'default_restricted_pages',
				'type'    => 'checkbox_group',
				'label'   => __( 'Default restricted pages', 'perxel-toolkit' ),
				'options' => self::DEFAULT_RESTRICTED_PAGES,
				'default' => array_keys( self::DEFAULT_RESTRICTED_PAGES ),
			),
			array(
				'key'     => 'custom_restricted_pages',
				'type'    => 'list',
				'label'   => __( 'Custom restricted pages', 'perxel-toolkit' ),
				'desc'    => __( 'wp-admin URLs, as pasted from the address bar - one per line.', 'perxel-toolkit' ),
				'default' => array(),
			),
		);
	}

	/** User logins exempt from every restriction. */
	public static function allowed_users(): array {
		$stored = Settings::module_settings( self::slug() )['allowed_users'] ?? array();
		return (array) apply_filters( 'pxtk_admin_page_guard_allowed_users', $stored );
	}

	/** The wp-admin URLs (as pasted from the address bar) to restrict. */
	public static function restricted_pages(): array {
		$settings = Settings::module_settings( self::slug() );
		$default  = (array) ( $settings['default_restricted_pages'] ?? array_keys( self::DEFAULT_RESTRICTED_PAGES ) );
		$custom   = (array) ( $settings['custom_restricted_pages'] ?? array() );

		$stored = array_values( array_unique( array_merge( $default, $custom ) ) );
		return (array) apply_filters( 'pxtk_admin_page_guard_restricted_pages', $stored );
	}

	/**
	 * A link that reloads $page_url with a one-off "view as restricted user"
	 * flag - used by the "View as" button on the settings screen to
	 * demonstrate the redirect without switching users. Nothing is
	 * persisted: the flag only affects the single page load it's present
	 * on, and is gone the moment that query arg isn't.
	 *
	 * @param string $page_url Restricted admin URL to preview.
	 */
	public static function view_as_url( string $page_url ): string {
		return add_query_arg( self::VIEW_AS_ACTION, '1', $page_url );
	}

	/**
	 * Whether this request is a "View as" preview - gated to users who can
	 * already manage the guard's settings, so previewing never grants a
	 * capability, only simulates losing the allowed-user exemption. Public
	 * so the settings screen can flip its "View as" button to a "Back to
	 * normal view" link while a preview is active. A read-only display
	 * toggle, not a state change, so it's a plain query flag - no nonce.
	 */
	public static function is_previewing_as_restricted(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display toggle for the current request only, no state change; see docblock.
		return ! empty( $_GET[ self::VIEW_AS_ACTION ] ) && current_user_can( 'manage_options' );
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'guard' ) );
		add_action( 'admin_menu', array( $this, 'hide_menus' ), 999 );
	}

	/**
	 * Derive the WP menu slug from a restricted page URL.
	 *
	 * @param string $url Restricted page URL.
	 */
	private function get_menu_slug( $url ) {
		$parsed = wp_parse_url( $url );
		$file   = isset( $parsed['path'] ) ? basename( $parsed['path'] ) : '';
		$params = array();
		if ( isset( $parsed['query'] ) ) {
			parse_str( $parsed['query'], $params );
		}

		// admin.php?page=slug -> slug is the menu slug.
		if ( 'admin.php' === $file && isset( $params['page'] ) ) {
			return $params['page'];
		}

		// edit.php?post_type=foo -> menu slug is "edit.php?post_type=foo".
		if ( 'edit.php' === $file && isset( $params['post_type'] ) ) {
			return 'edit.php?post_type=' . $params['post_type'];
		}

		// A bare top-level page (plugins.php, tools.php, users.php, ...) -
		// the file itself is the menu slug.
		return '' !== $file ? $file : null;
	}

	public function hide_menus(): void {
		if ( $this->current_user_is_allowed() ) {
			return;
		}

		foreach ( self::restricted_pages() as $url ) {
			$slug = $this->get_menu_slug( $url );
			if ( $slug ) {
				remove_menu_page( $slug );
			}
		}
	}

	public function guard(): void {
		if ( $this->current_user_is_allowed() ) {
			return;
		}

		global $pagenow;

		foreach ( self::restricted_pages() as $url ) {
			$parsed = wp_parse_url( $url );
			$file   = isset( $parsed['path'] ) ? basename( $parsed['path'] ) : '';

			if ( $pagenow !== $file ) {
				continue;
			}

			$params = array();
			if ( isset( $parsed['query'] ) ) {
				parse_str( $parsed['query'], $params );
			}

			$match = true;
			foreach ( $params as $key => $value ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- read-only page match against our own stored config, no state change.
				if ( ! isset( $_GET[ $key ] ) || sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) !== $value ) {
					$match = false;
					break;
				}
			}

			if ( $match ) {
				wp_safe_redirect( admin_url() );
				exit;
			}
		}
	}

	private function current_user_is_allowed(): bool {
		if ( self::is_previewing_as_restricted() ) {
			return false;
		}

		$current_user = wp_get_current_user();
		return in_array( $current_user->user_login, self::allowed_users(), true );
	}
}
