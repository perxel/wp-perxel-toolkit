<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a featured-image column to the post list (for every public post type
 * that supports thumbnails) with a click-to-set quick edit.
 *
 * Ported from wp-mu-plugins/featured-image-column.php.
 */
class Featured_Image_Column extends Module {

	const AJAX_ACTION = 'pxtk_set_featured_image';
	const NONCE       = 'pxtk_set_featured_image';

	public static function slug(): string {
		return 'featured-image-column';
	}

	public static function label(): string {
		return __( 'Featured Image Column', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Add a featured-image column with quick-edit to the post list.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'feature';
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'add_columns' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( $this, 'ajax_set_featured_image' ) );
	}

	public function add_columns(): void {
		$post_types = get_post_types( array( 'public' => true ), 'names' );

		foreach ( $post_types as $post_type ) {
			if ( post_type_supports( $post_type, 'thumbnail' ) ) {
				add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_column' ) );
				add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_column' ), 10, 2 );
			}
		}
	}

	/**
	 * @param array $columns Existing post list columns.
	 * @return array
	 */
	public function add_column( $columns ) {
		$new_columns = array();

		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'cb' === $key ) {
				$new_columns['pxtk_featured_image'] = __( 'Image', 'perxel-toolkit' );
			}
		}

		return $new_columns;
	}

	/**
	 * @param string $column  Current column id.
	 * @param int    $post_id Post ID.
	 */
	public function render_column( $column, $post_id ): void {
		if ( 'pxtk_featured_image' !== $column ) {
			return;
		}

		$thumbnail_id = get_post_thumbnail_id( $post_id );

		if ( $thumbnail_id ) {
			$thumbnail     = wp_get_attachment_image_src( $thumbnail_id, array( 60, 60 ) );
			$thumbnail_url = $thumbnail ? $thumbnail[0] : '';
			?>
			<div class="pxtk-featured-image-column" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-thumbnail-id="<?php echo esc_attr( $thumbnail_id ); ?>">
				<img src="<?php echo esc_url( $thumbnail_url ); ?>" alt="" />
			</div>
			<?php
		} else {
			?>
			<div class="pxtk-featured-image-column pxtk-featured-image-column--empty" data-post-id="<?php echo esc_attr( $post_id ); ?>" data-thumbnail-id="">
				<span aria-hidden="true">&#128247;</span>
			</div>
			<?php
		}
	}

	/**
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ): void {
		$screen = get_current_screen();
		if ( ! $screen || 'edit' !== $screen->base ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script( 'jquery' );

		$nonce = wp_create_nonce( self::NONCE );
		$css   = PXTK_DIR . '/assets/css/featured-image-column.css';
		$js    = PXTK_DIR . '/assets/js/featured-image-column.js';

		wp_enqueue_style( 'pxtk-featured-image-column', PXTK_URL . '/assets/css/featured-image-column.css', array(), file_exists( $css ) ? (string) filemtime( $css ) : PXTK_VERSION );

		wp_enqueue_script( 'pxtk-featured-image-column', PXTK_URL . '/assets/js/featured-image-column.js', array( 'jquery' ), file_exists( $js ) ? (string) filemtime( $js ) : PXTK_VERSION, true );

		wp_localize_script(
			'pxtk-featured-image-column',
			'pxtkFeaturedImageColumn',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'action'  => self::AJAX_ACTION,
				'nonce'   => $nonce,
			)
		);
	}

	public function ajax_set_featured_image(): void {
		check_ajax_referer( self::NONCE, 'nonce' );

		$post_id      = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		$thumbnail_id = isset( $_POST['thumbnail_id'] ) ? absint( $_POST['thumbnail_id'] ) : 0;

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( array( 'message' => __( 'Permission denied.', 'perxel-toolkit' ) ) );
		}

		if ( $thumbnail_id ) {
			set_post_thumbnail( $post_id, $thumbnail_id );
			wp_send_json_success( array( 'message' => __( 'Featured image set.', 'perxel-toolkit' ) ) );
		} else {
			delete_post_thumbnail( $post_id );
			wp_send_json_success( array( 'message' => __( 'Featured image removed.', 'perxel-toolkit' ) ) );
		}
	}
}
