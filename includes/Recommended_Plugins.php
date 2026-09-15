<?php

namespace Perxel_Toolkit;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static curated list of plugins Perxel recommends installing on every
 * project, shown on the settings screen with a direct install link.
 * Sourced from https://perxel.com/insights/wordpress-plugin-list.
 *
 * Each entry has a `label`, a `description`, and either `wporg_slug` (renders
 * a real one-click "Install Now" link, same as an integration module's
 * dependency()) or `install_url` (a plain link - use this when the plugin
 * isn't on wordpress.org, e.g. a premium plugin sold from its own site).
 */
class Recommended_Plugins {

	/**
	 * @return array<int,array{group:string,label:string,description:string,wporg_slug?:string,install_url?:string}>
	 */
	public static function all(): array {
		return array(
			array(
				'group'       => __( 'Core', 'perxel-toolkit' ),
				'label'       => __( 'NectarBlocks', 'perxel-toolkit' ),
				'description' => __( 'Page builder for Gutenberg with visual layout, responsive, hover and animation controls.', 'perxel-toolkit' ),
				'install_url' => 'https://nectarblocks.com/',
			),
			array(
				'group'       => __( 'Core', 'perxel-toolkit' ),
				'label'       => __( 'Gravity Forms', 'perxel-toolkit' ),
				'description' => __( 'Contact, survey and registration forms with complex conditional logic.', 'perxel-toolkit' ),
				'install_url' => 'https://www.gravityforms.com/',
			),
			array(
				'group'       => __( 'Core', 'perxel-toolkit' ),
				'label'       => __( 'Rank Math SEO', 'perxel-toolkit' ),
				'description' => __( 'Meta tags, sitemaps, schema and on-page SEO analysis.', 'perxel-toolkit' ),
				'wporg_slug'  => 'seo-by-rank-math',
			),
			array(
				'group'       => __( 'Core', 'perxel-toolkit' ),
				'label'       => __( 'Yoast SEO', 'perxel-toolkit' ),
				'description' => __( 'Alternative to Rank Math - meta tags, sitemaps, schema and on-page SEO analysis.', 'perxel-toolkit' ),
				'wporg_slug'  => 'wordpress-seo',
			),
			array(
				'group'       => __( 'Core', 'perxel-toolkit' ),
				'label'       => __( 'Secure Custom Fields', 'perxel-toolkit' ),
				'description' => __( 'Custom fields for the admin editor - the official free successor to Advanced Custom Fields.', 'perxel-toolkit' ),
				'wporg_slug'  => 'secure-custom-fields',
			),
			array(
				'group'       => __( 'Core', 'perxel-toolkit' ),
				'label'       => __( 'WP Rocket', 'perxel-toolkit' ),
				'description' => __( 'Page caching, CSS/JS minification and load-order tuning for real-world page speed.', 'perxel-toolkit' ),
				'install_url' => 'https://wp-rocket.me/',
			),
			array(
				'group'       => __( 'Multilingual', 'perxel-toolkit' ),
				'label'       => __( 'WPML', 'perxel-toolkit' ),
				'description' => __( 'Multilingual content, product and string translation across the site.', 'perxel-toolkit' ),
				'install_url' => 'https://wpml.org/',
			),
			array(
				'group'       => __( 'Security & backup', 'perxel-toolkit' ),
				'label'       => __( 'Sucuri Security', 'perxel-toolkit' ),
				'description' => __( 'File integrity monitoring, malware scanning and intrusion detection.', 'perxel-toolkit' ),
				'wporg_slug'  => 'sucuri-scanner',
			),
			array(
				'group'       => __( 'Security & backup', 'perxel-toolkit' ),
				'label'       => __( 'Kadence Security', 'perxel-toolkit' ),
				'description' => __( 'Login-attempt limiting, hidden wp-admin, and other baseline hardening rules.', 'perxel-toolkit' ),
				'wporg_slug'  => 'better-wp-security',
			),
			array(
				'group'       => __( 'Security & backup', 'perxel-toolkit' ),
				'label'       => __( 'Akeeba Backup', 'perxel-toolkit' ),
				'description' => __( 'Packages the full codebase and database for restore-ready backups.', 'perxel-toolkit' ),
				'install_url' => 'https://www.akeeba.com/products/akeeba-backup-wp.html',
			),
			array(
				'group'       => __( 'Admin utilities', 'perxel-toolkit' ),
				'label'       => __( 'White Label CMS', 'perxel-toolkit' ),
				'description' => __( 'Customises the admin screen and dashboard for client-facing sites.', 'perxel-toolkit' ),
				'wporg_slug'  => 'white-label-cms',
			),
			array(
				'group'       => __( 'Admin utilities', 'perxel-toolkit' ),
				'label'       => __( 'AddToAny Share Buttons', 'perxel-toolkit' ),
				'description' => __( 'Lightweight social share buttons on posts.', 'perxel-toolkit' ),
				'wporg_slug'  => 'add-to-any',
			),
			array(
				'group'       => __( 'Admin utilities', 'perxel-toolkit' ),
				'label'       => __( 'Regenerate Thumbnails', 'perxel-toolkit' ),
				'description' => __( 'Regenerates image sizes after an image-size configuration change.', 'perxel-toolkit' ),
				'wporg_slug'  => 'regenerate-thumbnails',
			),
			array(
				'group'       => __( 'Admin utilities', 'perxel-toolkit' ),
				'label'       => __( 'Enable Media Replace', 'perxel-toolkit' ),
				'description' => __( 'Replaces an image or PDF in place without changing its URL.', 'perxel-toolkit' ),
				'wporg_slug'  => 'enable-media-replace',
			),
			array(
				'group'       => __( 'Admin utilities', 'perxel-toolkit' ),
				'label'       => __( 'Better Search Replace', 'perxel-toolkit' ),
				'description' => __( 'Bulk find-and-replace across the database, e.g. after a domain change.', 'perxel-toolkit' ),
				'wporg_slug'  => 'better-search-replace',
			),
			array(
				'group'       => __( 'Admin utilities', 'perxel-toolkit' ),
				'label'       => __( 'WP Mail SMTP', 'perxel-toolkit' ),
				'description' => __( 'Sends outgoing site email through a real SMTP provider instead of the server default.', 'perxel-toolkit' ),
				'wporg_slug'  => 'wp-mail-smtp',
			),
		);
	}

	/**
	 * Render the "Install Now" / "Get X" action for one plugin entry, same
	 * link pattern as an integration module's dependency() action.
	 *
	 * @param array{label:string,wporg_slug?:string,install_url?:string} $plugin One entry from all().
	 */
	public static function install_button( array $plugin ): string {
		if ( ! empty( $plugin['wporg_slug'] ) && current_user_can( 'install_plugins' ) ) {
			$install_url = wp_nonce_url(
				self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $plugin['wporg_slug'] ) ),
				'install-plugin_' . $plugin['wporg_slug']
			);
			return '<a class="button button-small" href="' . esc_url( $install_url ) . '">' . esc_html__( 'Install Now', 'perxel-toolkit' ) . '</a>';
		}

		if ( ! empty( $plugin['install_url'] ) ) {
			return '<a class="button button-small" href="' . esc_url( $plugin['install_url'] ) . '" target="_blank" rel="noopener noreferrer">'
				/* translators: %s: name of the plugin. */
				. sprintf( esc_html__( 'Get %s', 'perxel-toolkit' ), esc_html( $plugin['label'] ) ) . '</a>';
		}

		return '';
	}
}
