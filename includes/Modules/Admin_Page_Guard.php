<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restricts access to specific admin pages - redirects unauthorized users
 * to the dashboard and hides the matching menu items.
 *
 * Ported from wp-mu-plugins/admin-page-guard.php. The original hardcoded a
 * per-client allowed-user and restricted-page list; both are empty by
 * default here (inert until configured) and filterable per project until
 * this module gets its own settings UI.
 */
class Admin_Page_Guard extends Module {

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
		return 'feature';
	}

	/** User logins exempt from every restriction. */
	public static function allowed_users(): array {
		return (array) apply_filters( 'pxtk_admin_page_guard_allowed_users', array() );
	}

	/** wp-admin URLs (as pasted from the address bar) to restrict. */
	public static function restricted_pages(): array {
		return (array) apply_filters( 'pxtk_admin_page_guard_restricted_pages', array() );
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

		return null;
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
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page match, no state change.
				if ( ! isset( $_GET[ $key ] ) || wp_unslash( $_GET[ $key ] ) !== $value ) {
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
		$current_user = wp_get_current_user();
		return in_array( $current_user->user_login, self::allowed_users(), true );
	}
}
