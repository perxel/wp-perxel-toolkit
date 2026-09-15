<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Replaces WordPress core's small default image-size ladder (thumbnail
 * 150x150, medium 300x300, medium_large 768w, large 1024x1024) with larger,
 * more usable sizes, then sweeps away every *other* registered size - core's
 * own 1536x1536/2048x2048, plus whatever WooCommerce, Yoast, page builders,
 * or the theme add via add_image_size() - so uploads don't keep generating
 * image files nothing on the site displays.
 *
 * A project that needs different numbers, an extra custom size, or has to
 * keep a specific plugin size (e.g. `woocommerce_thumbnail`) can override
 * via filters instead of duplicating this whole module:
 *
 *     add_filter( 'pxtk_media_sizes', function ( $sizes ) {
 *         $sizes['large'] = array( 'width' => 1600 );
 *         $sizes['hero']  = array( 'width' => 1920, 'height' => 960, 'crop' => true );
 *         return $sizes;
 *     } );
 *
 *     add_filter( 'pxtk_media_sizes_keep', function ( $keep ) {
 *         $keep[] = 'woocommerce_thumbnail';
 *         return $keep;
 *     } );
 *
 * Ported from the theme-level media-size.php config repeated per client.
 */
class Media_Sizes extends Module {

	const DEFAULT_SIZES = array(
		'thumbnail'    => array(
			'width'  => 480,
			'height' => 480,
			'crop'   => true,
		),
		'medium'       => array(
			'width'  => 860,
			'height' => 0,
		),
		'medium_large' => array(
			'width'  => 1440,
			'height' => 0,
		),
		'large'        => array(
			'width'  => 1920,
			'height' => 0,
		),
	);

	const CORE_SIZE_NAMES = array( 'thumbnail', 'medium', 'medium_large', 'large' );

	/** @var string[] Non-core size names applied by apply_custom_sizes(), kept by remove_other_sizes(). */
	private $custom_size_names = array();

	public static function slug(): string {
		return 'media-sizes';
	}

	public static function label(): string {
		return __( 'Media Sizes', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( "Replace WordPress's small default image sizes with a larger ladder and remove every other size registered by core, plugins, or the theme.", 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'feature';
	}

	public function register(): void {
		add_action( 'init', array( $this, 'apply_custom_sizes' ), 999 );
		add_action( 'init', array( $this, 'remove_other_sizes' ), PHP_INT_MAX );
	}

	/**
	 * Apply the default ladder, or a project's override supplied via the
	 * `pxtk_media_sizes` filter.
	 */
	public function apply_custom_sizes(): void {
		$sizes = apply_filters( 'pxtk_media_sizes', self::DEFAULT_SIZES );

		foreach ( (array) $sizes as $name => $size ) {
			if ( empty( $size['width'] ) ) {
				continue;
			}

			$width  = (int) $size['width'];
			$height = isset( $size['height'] ) ? (int) $size['height'] : 0;
			$crop   = ! empty( $size['crop'] );

			if ( in_array( $name, self::CORE_SIZE_NAMES, true ) ) {
				$this->maybe_update_option( $name . '_size_w', $width );
				$this->maybe_update_option( $name . '_size_h', $height );
				$this->maybe_update_option( $name . '_crop', $crop );
			} else {
				add_image_size( $name, $width, $height, $crop );
				$this->custom_size_names[] = $name;
			}
		}
	}

	/**
	 * Remove every additional image size still registered that isn't part of
	 * this module's own ladder - core's 1536x1536/2048x2048 included, plus
	 * anything a plugin or the theme added via add_image_size(). Runs last on
	 * 'init' so every other registrar has already had its turn.
	 */
	public function remove_other_sizes(): void {
		global $_wp_additional_image_sizes;

		if ( empty( $_wp_additional_image_sizes ) ) {
			return;
		}

		$keep = apply_filters( 'pxtk_media_sizes_keep', $this->custom_size_names );

		foreach ( array_keys( $_wp_additional_image_sizes ) as $size_name ) {
			if ( ! in_array( $size_name, $keep, true ) ) {
				remove_image_size( $size_name );
			}
		}
	}

	/**
	 * Call update_option() only when the value actually changes, so this
	 * doesn't write to the options table on every single request.
	 *
	 * @param string $option Option name.
	 * @param mixed  $value  Option value.
	 */
	private function maybe_update_option( $option, $value ): void {
		if ( get_option( $option ) != $value ) { // phpcs:ignore Universal.Operators.StrictComparisons.LooseNotEqual -- option values come back as strings; compare loosely.
			update_option( $option, $value );
		}
	}
}
