<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ACF/SCF integration: routes field-group Local JSON saves/loads to the
 * correct location in the active theme so they're git-tracked. Block field
 * groups go to `blocks/{slug}/fields.json` (one file per block folder);
 * every other group (post types, pages, options pages, taxonomies, ...)
 * goes to `acf-json/{location-type}-{title-slug}.json`.
 *
 * Ported from wp-mu-plugins/acf-json-sync.php (formerly "ACF Local JSON
 * Router").
 */
class Acf extends Module {

	public static function slug(): string {
		return 'acf';
	}

	public static function label(): string {
		return __( 'ACF / SCF', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Route field-group JSON to per-block / per-location paths for git sync.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'integration';
	}

	public static function dependency(): array {
		return array(
			// Either plugin satisfies this integration - both register the
			// `ACF` class, so the check() below covers either - but suggest
			// Secure Custom Fields, the free official successor, over the
			// original ACF plugin.
			'label'       => __( 'Advanced Custom Fields or Secure Custom Fields', 'perxel-toolkit' ),
			'check'       => static function () {
				return class_exists( 'ACF' );
			},
			'wporg_slug'  => 'secure-custom-fields',
			'install_url' => 'https://wordpress.org/plugins/secure-custom-fields/',
		);
	}

	public function register(): void {
		add_filter( 'acf/settings/load_json', array( $this, 'load_json_paths' ) );
		add_filter( 'acf/settings/save_json', array( $this, 'save_json_path' ) );
		add_filter( 'acf/json/save_file_name', array( $this, 'save_file_name' ), 10, 3 );
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	private function shared_dir(): string {
		$dir = get_stylesheet_directory() . '/acf-json';
		if ( ! is_dir( $dir ) ) {
			wp_mkdir_p( $dir );
		}
		return $dir;
	}

	private function blocks_dir(): string {
		return get_stylesheet_directory() . '/blocks';
	}

	/**
	 * @param string $value A `block` location rule value, e.g. `acf/hero`.
	 */
	private function block_slug( $value ): string {
		$slug  = str_replace( 'acf/', '', $value );
		$parts = explode( '/', $slug );
		return end( $parts );
	}

	/**
	 * @param array $field_group ACF field group array.
	 */
	private function location_type( array $field_group ): string {
		foreach ( ( $field_group['location'] ?? array() ) as $group ) {
			foreach ( $group as $rule ) {
				return $rule['param'] ?? '';
			}
		}
		return '';
	}

	/**
	 * @param array $field_group ACF field group array.
	 */
	private function shared_filename( array $field_group ): string {
		$type  = str_replace( '_', '-', $this->location_type( $field_group ) );
		$title = sanitize_title( $field_group['title'] ?? 'group' );
		return ltrim( $type . '-' . $title, '-' ) . '.json';
	}

	/**
	 * @param array $paths Existing ACF Local JSON load paths.
	 * @return array
	 */
	public function load_json_paths( $paths ) {
		$blocks_dir = $this->blocks_dir();
		if ( is_dir( $blocks_dir ) ) {
			foreach ( glob( $blocks_dir . '/*', GLOB_ONLYDIR ) as $block ) {
				if ( ! empty( glob( $block . '/*.json' ) ) ) {
					$paths[] = $block;
				}
			}
		}

		$shared = $this->shared_dir();
		if ( is_dir( $shared ) ) {
			$paths[] = $shared;
		}

		return $paths;
	}

	/**
	 * @param string $path Default ACF Local JSON save path.
	 * @return string
	 */
	public function save_json_path( $path ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- ACF handles the field-group save nonce; only reading location rules here.
		if ( ! isset( $_POST['acf_field_group'] ) ) {
			return $path;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- ACF handles the field-group save nonce; this is ACF's own field-group array, only used to pick a filesystem save path (block_slug(), shared_filename()) that's then checked with is_dir()/sanitize_title() before use.
		$field_group = wp_unslash( $_POST['acf_field_group'] );

		foreach ( ( $field_group['location'] ?? array() ) as $group ) {
			foreach ( $group as $rule ) {
				if ( 'block' === $rule['param'] && '==' === $rule['operator'] ) {
					$block_slug = $this->block_slug( $rule['value'] );
					$block_path = $this->blocks_dir() . '/' . $block_slug;

					if ( is_dir( $block_path ) ) {
						set_transient(
							'pxtk_acf_saved_' . $field_group['key'],
							array(
								'type'          => 'block',
								'relative_path' => 'blocks/' . $block_slug . '/fields.json',
							),
							30
						);
						return $block_path;
					}
				}
			}
		}

		$shared   = $this->shared_dir();
		$filename = $this->shared_filename( $field_group );

		set_transient(
			'pxtk_acf_saved_' . $field_group['key'],
			array(
				'type'          => 'shared',
				'relative_path' => 'acf-json/' . $filename,
			),
			30
		);

		return $shared;
	}

	/**
	 * @param string $filename  Default filename.
	 * @param array  $post      Field group array.
	 * @param string $load_path Path the file is being saved into.
	 * @return string
	 */
	public function save_file_name( $filename, $post, $load_path ) {
		if ( str_starts_with( wp_normalize_path( $load_path ), wp_normalize_path( $this->blocks_dir() ) ) ) {
			return 'fields.json';
		}
		return $this->shared_filename( $post );
	}

	/**
	 * @param array $field_group ACF field group array.
	 */
	private function resolve_relative_path( array $field_group ): ?string {
		foreach ( ( $field_group['location'] ?? array() ) as $group ) {
			foreach ( $group as $rule ) {
				if ( 'block' === $rule['param'] && '==' === $rule['operator'] ) {
					return 'blocks/' . $this->block_slug( $rule['value'] ) . '/fields.json';
				}
			}
		}
		return 'acf-json/' . $this->shared_filename( $field_group );
	}

	public function admin_notice(): void {
		$screen = get_current_screen();
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only: which field group's edit screen this is, no state change.
		if ( ! $screen || 'acf-field-group' !== $screen->id || ! isset( $_GET['post'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only, and absint() sanitises the value.
		$field_group = acf_get_field_group( absint( $_GET['post'] ) );
		if ( ! $field_group ) {
			return;
		}

		$transient_key = 'pxtk_acf_saved_' . $field_group['key'];
		$saved_info    = get_transient( $transient_key );

		if ( $saved_info ) {
			?>
			<div class="notice notice-success is-dismissible">
				<p>
					<strong><?php esc_html_e( 'JSON file updated', 'perxel-toolkit' ); ?></strong><br />
					<?php esc_html_e( 'Location:', 'perxel-toolkit' ); ?> <code><?php echo esc_html( $saved_info['relative_path'] ); ?></code>
				</p>
				<p><?php esc_html_e( 'Commit this file to git for version control and team sync.', 'perxel-toolkit' ); ?></p>
			</div>
			<?php
			delete_transient( $transient_key );
			return;
		}

		$relative_path = $this->resolve_relative_path( $field_group );
		if ( ! $relative_path ) {
			return;
		}

		$abs_path = get_stylesheet_directory() . '/' . $relative_path;

		if ( file_exists( $abs_path ) ) {
			?>
			<div class="notice notice-info">
				<p>
					<?php
					printf(
						/* translators: %s: relative file path. */
						esc_html__( 'JSON file at %s. Edit here or directly in the file - sync from the ACF admin after manual edits.', 'perxel-toolkit' ),
						'<code>' . esc_html( $relative_path ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		} else {
			?>
			<div class="notice notice-warning">
				<p>
					<?php
					printf(
						/* translators: %s: relative file path. */
						esc_html__( 'No JSON file yet. Save this field group to create %s.', 'perxel-toolkit' ),
						'<code>' . esc_html( $relative_path ) . '</code>'
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
