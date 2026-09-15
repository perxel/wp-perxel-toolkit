<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turns commenting off site-wide: closes comments and pings on every post
 * type, hides existing threads on the front end (without deleting them),
 * and strips the related admin UI (menu, dashboard widget, admin bar,
 * list-table column, widget).
 *
 * Ported from the "disable comments everywhere" config repeated by hand on
 * every client site.
 */
class Disable_Comments extends Module {

	public static function slug(): string {
		return 'disable-comments';
	}

	public static function label(): string {
		return __( 'Disable Comments', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Turn off commenting site-wide and hide the related admin UI.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'feature';
	}

	public function register(): void {
		add_action( 'admin_init', array( $this, 'remove_post_type_support' ) );
		add_action( 'admin_init', array( $this, 'add_column_filters' ) );
		add_action( 'admin_init', array( $this, 'redirect_disallowed_pages' ) );
		add_action( 'admin_menu', array( $this, 'remove_menu_pages' ), 999 );
		add_action( 'admin_bar_menu', array( $this, 'remove_admin_bar_node' ), 999 );
		add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widget' ) );
		add_action( 'widgets_init', array( $this, 'unregister_widgets' ), 11 );

		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'comments_array', array( $this, 'hide_existing_comments' ), 20, 2 );
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ) );
	}

	/**
	 * Drop 'comments' and 'trackbacks' support from every post type so the
	 * discussion box disappears from the editor.
	 */
	public function remove_post_type_support(): void {
		foreach ( get_post_types() as $post_type ) {
			remove_post_type_support( $post_type, 'comments' );
			remove_post_type_support( $post_type, 'trackbacks' );
		}
	}

	/**
	 * Drop the "Comments" column from every post-list screen.
	 */
	public function add_column_filters(): void {
		foreach ( get_post_types( array( 'show_ui' => true ), 'names' ) as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", array( $this, 'remove_comments_column' ) );
		}
	}

	/**
	 * @param array $columns Existing post list columns.
	 * @return array
	 */
	public function remove_comments_column( $columns ) {
		unset( $columns['comments'] );
		return $columns;
	}

	/**
	 * Bounce anyone who still navigates straight to a comments admin screen.
	 */
	public function redirect_disallowed_pages(): void {
		global $pagenow;

		if ( ! in_array( $pagenow, array( 'edit-comments.php', 'options-discussion.php' ), true ) ) {
			return;
		}

		wp_safe_redirect( admin_url() );
		exit;
	}

	/**
	 * Hide the "Comments" and "Discussion" menu items.
	 */
	public function remove_menu_pages(): void {
		remove_menu_page( 'edit-comments.php' );
		remove_submenu_page( 'options-general.php', 'options-discussion.php' );
	}

	public function remove_admin_bar_node( \WP_Admin_Bar $wp_admin_bar ): void {
		$wp_admin_bar->remove_node( 'comments' );
	}

	public function remove_dashboard_widget(): void {
		remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
	}

	public function unregister_widgets(): void {
		unregister_widget( 'WP_Widget_Recent_Comments' );
	}

	/**
	 * Hide comments already in the database on the front end, without
	 * deleting them - flipping this module off restores them as-is.
	 *
	 * @param array $comments Existing comments for the post.
	 * @param int   $post_id  Post ID.
	 * @return array
	 */
	public function hide_existing_comments( $comments, $post_id ) {
		unset( $comments, $post_id );
		return array();
	}

	/**
	 * @param string[] $headers Outgoing HTTP headers.
	 * @return string[]
	 */
	public function remove_pingback_header( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
}
