<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds a featured checkbox to posts (meta box + quick edit + sortable admin
 * column + title star), backed by native `_featured` post meta.
 *
 * Ported from wp-mu-plugins/featured-posts.php. The original shipped its own
 * "Settings -> Featured Posts" screen (which post types are enabled, whether
 * to disable sticky posts); that per-project configuration is exposed as
 * filters for now - `pxtk_featured_posts_post_types` (default `['post']`)
 * and `pxtk_featured_posts_disable_sticky` (default `false`) - until this
 * module gets its own settings UI here.
 *
 * Usage:
 *   $is_featured = get_post_meta( $post_id, '_featured', true );
 *   new WP_Query( array( 'meta_key' => '_featured', 'meta_value' => '1' ) );
 */
class Featured_Posts extends Module {

	public static function slug(): string {
		return 'featured-posts';
	}

	public static function label(): string {
		return __( 'Featured Posts', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Add a featured checkbox to posts with native query support.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'feature';
	}

	public static function supported_post_types(): array {
		return (array) apply_filters( 'pxtk_featured_posts_post_types', array( 'post' ) );
	}

	public static function disable_sticky(): bool {
		return (bool) apply_filters( 'pxtk_featured_posts_disable_sticky', false );
	}

	public function register(): void {
		if ( self::disable_sticky() ) {
			remove_post_type_support( 'post', 'sticky' );
			add_filter( 'admin_body_class', array( $this, 'hide_sticky_body_class' ) );
		}

		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save_meta_box' ) );

		add_action(
			'admin_init',
			function () {
				foreach ( self::supported_post_types() as $post_type ) {
					add_filter( "manage_{$post_type}_posts_columns", array( $this, 'add_admin_column' ) );
					add_action( "manage_{$post_type}_posts_custom_column", array( $this, 'render_admin_column' ), 10, 2 );
					add_filter( "manage_edit-{$post_type}_sortable_columns", array( $this, 'sortable_column' ) );
				}
			}
		);

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'pre_get_posts', array( $this, 'sort_by_featured' ) );

		add_action( 'quick_edit_custom_box', array( $this, 'quick_edit_box' ), 10, 2 );

		add_filter( 'the_title', array( $this, 'add_star_to_title' ), 10, 2 );
	}

	/**
	 * Tag the Posts list screen so assets/css/featured-posts.css hides Quick
	 * Edit's "Make this post sticky" checkbox (sticky support is removed).
	 *
	 * @param string $classes Space-separated admin body classes.
	 * @return string
	 */
	public function hide_sticky_body_class( $classes ) {
		$screen = get_current_screen();
		if ( $screen && 'edit' === $screen->base && 'post' === $screen->post_type ) {
			$classes .= ' pxtk-hide-sticky';
		}
		return $classes;
	}

	public function add_meta_box(): void {
		foreach ( self::supported_post_types() as $post_type ) {
			add_meta_box( 'pxtk_featured_post', __( 'Featured', 'perxel-toolkit' ), array( $this, 'render_meta_box' ), $post_type, 'side', 'high' );
		}
	}

	/**
	 * @param \WP_Post $post Current post.
	 */
	public function render_meta_box( $post ): void {
		wp_nonce_field( 'pxtk_featured_post', 'pxtk_featured_post_nonce' );
		$is_featured = get_post_meta( $post->ID, '_featured', true );
		?>
		<label style="display:block;margin-bottom:8px;">
			<input type="checkbox" name="pxtk_featured_post" value="1" <?php checked( $is_featured, '1' ); ?> />
			<?php esc_html_e( 'Mark as featured', 'perxel-toolkit' ); ?>
		</label>
		<?php
	}

	/**
	 * @param int $post_id Post ID.
	 */
	public function save_meta_box( $post_id ): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- inline-edit nonce checked separately below.
		if ( isset( $_POST['_inline_edit'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['_inline_edit'] ) ), 'inlineeditnonce' ) ) {
			$this->save_quick_edit( $post_id );
			return;
		}

		if ( ! isset( $_POST['pxtk_featured_post_nonce'] )
			|| ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['pxtk_featured_post_nonce'] ) ), 'pxtk_featured_post' ) ) {
			return;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		update_post_meta( $post_id, '_featured', empty( $_POST['pxtk_featured_post'] ) ? '0' : '1' );
	}

	/**
	 * @param int $post_id Post ID.
	 */
	private function save_quick_edit( $post_id ): void {
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce verified by caller.
		update_post_meta( $post_id, '_featured', isset( $_POST['pxtk_featured_post'] ) ? '1' : '0' );
	}

	/**
	 * @param array $columns Existing post list columns.
	 * @return array
	 */
	public function add_admin_column( $columns ) {
		$new_columns = array();
		foreach ( $columns as $key => $value ) {
			$new_columns[ $key ] = $value;
			if ( 'title' === $key ) {
				$new_columns['pxtk_featured'] = '&#11088;';
			}
		}
		return $new_columns;
	}

	/**
	 * @param string $column  Current column id.
	 * @param int    $post_id Post ID.
	 */
	public function render_admin_column( $column, $post_id ): void {
		if ( 'pxtk_featured' !== $column ) {
			return;
		}
		echo get_post_meta( $post_id, '_featured', true ) ? '&#11088;' : '&mdash;';
	}

	/**
	 * Posts list screen only: the column / sticky CSS and the Quick Edit
	 * script (assets/css|js/featured-posts.*).
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( $hook ): void {
		$screen = get_current_screen();
		if ( 'edit.php' !== $hook || ! $screen ) {
			return;
		}

		$supported = in_array( $screen->post_type, self::supported_post_types(), true );
		if ( ! $supported && ! ( 'post' === $screen->post_type && self::disable_sticky() ) ) {
			return;
		}

		$css = PXTK_DIR . '/assets/css/featured-posts.css';
		wp_enqueue_style( 'pxtk-featured-posts', PXTK_URL . '/assets/css/featured-posts.css', array(), file_exists( $css ) ? (string) filemtime( $css ) : PXTK_VERSION );

		if ( $supported ) {
			$js = PXTK_DIR . '/assets/js/featured-posts.js';
			wp_enqueue_script( 'pxtk-featured-posts', PXTK_URL . '/assets/js/featured-posts.js', array( 'jquery', 'inline-edit-post' ), file_exists( $js ) ? (string) filemtime( $js ) : PXTK_VERSION, true );
		}
	}

	/**
	 * @param array $columns Sortable columns.
	 * @return array
	 */
	public function sortable_column( $columns ) {
		$columns['pxtk_featured'] = '_featured';
		return $columns;
	}

	/**
	 * @param \WP_Query $query Current query.
	 */
	public function sort_by_featured( $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}
		if ( '_featured' === $query->get( 'orderby' ) ) {
			$query->set( 'meta_key', '_featured' );
			$query->set( 'orderby', 'meta_value_num' );
		}
	}

	/**
	 * @param string $column    Current column id.
	 * @param string $post_type Current post type.
	 */
	public function quick_edit_box( $column, $post_type ): void {
		if ( 'pxtk_featured' !== $column || ! in_array( $post_type, self::supported_post_types(), true ) ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft">
					<input type="checkbox" name="pxtk_featured_post" value="1" />
					<span class="checkbox-title">&#11088; <?php esc_html_e( 'Featured', 'perxel-toolkit' ); ?></span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * @param string $title   Post title.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	public function add_star_to_title( $title, $post_id = 0 ) {
		if ( ! is_admin() || ! $post_id ) {
			return $title;
		}

		$screen = get_current_screen();
		if ( ! $screen || 'edit' !== $screen->base ) {
			return $title;
		}

		if ( ! in_array( get_post_type( $post_id ), self::supported_post_types(), true ) ) {
			return $title;
		}

		return get_post_meta( $post_id, '_featured', true ) ? $title . ' &#11088;' : $title;
	}
}
