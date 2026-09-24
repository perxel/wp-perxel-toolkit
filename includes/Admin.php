<?php

namespace Perxel_Toolkit;

use Perxel_Toolkit\Modules\Admin_Page_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin surface: one or more "Tools ->" screens, rendered inside the shared
 * Perxel UI layout (vendor/perxel-ui). Owns menu registration, asset
 * loading, the shared layout args, and the settings form handlers.
 *
 * Add a screen visible in the WP admin menu by giving it a slug constant, an
 * add_management_page() call in menu(), a $titles entry, and a render_*()
 * callback that delegates to screen() - or add_submenu_page( null, ... ) for
 * one reachable only via a link (e.g. the UI-kit showcase). Either way, add
 * it to layout_args()'s $pages too: the kit's in-page sidebar nav links every
 * page in that list together regardless of its own WP-menu visibility.
 */
class Admin {

	const PAGE_SETTINGS            = 'pxtk';
	const PAGE_ADMIN_PAGE_GUARD    = 'pxtk-admin-page-guard';
	const PAGE_RECOMMENDED_PLUGINS = 'pxtk-recommended-plugins';
	const PAGE_UI                  = 'pxtk-ui';

	/**
	 * Echo markup returned by a Perxel_UI renderer, escaped late through the
	 * kit's own wp_kses() allowlist. Never echo kit markup any other way, and
	 * never suppress EscapeOutput (WordPress.org review rejects both a
	 * file-wide disable and a per-line "escaped earlier" ignore).
	 *
	 * @param string $html Markup from a Perxel_UI:: renderer.
	 */
	public static function kit( $html ) {
		echo wp_kses( $html, \Perxel_UI::allowed_html() );
	}

	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( PXTK_FILE ), array( $this, 'action_links' ) );

		add_action( 'admin_post_pxtk_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_pxtk_reset_settings', array( $this, 'handle_reset_settings' ) );
		add_action( 'admin_post_pxtk_save_admin_page_guard', array( $this, 'handle_save_admin_page_guard' ) );
	}

	/*
	---------------------------------------------------------------------
	 * Menu
	 * ------------------------------------------------------------------- */

	public function menu() {
		add_management_page(
			PXTK_NAME,
			PXTK_NAME,
			'manage_options',
			self::PAGE_SETTINGS,
			array( $this, 'render_settings' )
		);

		// Reachable only through the kit's in-page sidebar nav (added to
		// $titles/layout_args() below), not as its own item in WP's Tools
		// menu - same off-menu pattern as the UI-kit showcase.
		add_submenu_page(
			null,
			Admin_Page_Guard::label(),
			'',
			'manage_options',
			self::PAGE_ADMIN_PAGE_GUARD,
			array( $this, 'render_admin_page_guard' )
		);

		// Same off-menu pattern - reachable only through the kit's in-page
		// sidebar nav, not as its own item in WP's Tools menu.
		add_submenu_page(
			null,
			__( 'Recommended Plugins', 'perxel-toolkit' ),
			'',
			'manage_options',
			self::PAGE_RECOMMENDED_PLUGINS,
			array( $this, 'render_recommended_plugins' )
		);

		$titles = array(
			self::PAGE_SETTINGS            => __( 'Settings', 'perxel-toolkit' ),
			self::PAGE_ADMIN_PAGE_GUARD    => Admin_Page_Guard::label(),
			self::PAGE_RECOMMENDED_PLUGINS => __( 'Recommended Plugins', 'perxel-toolkit' ),
		);

		// The bundled UI-kit showcase - a hidden, maintainer-only screen, and
		// only in a build that still ships showcase/.
		if ( self::can_see_showcase() ) {
			add_submenu_page( null, 'Perxel UI', '', 'manage_options', self::PAGE_UI, array( $this, 'render_ui' ) );
			$titles[ self::PAGE_UI ] = 'Perxel UI';
		}

		if ( class_exists( 'Perxel_UI_Layout' ) ) {
			\Perxel_UI_Layout::set_page_titles( $titles, PXTK_NAME );
		}
	}

	/**
	 * Add a "Settings" link to the plugin's row on the Plugins screen.
	 *
	 * @param string[] $links Existing action links.
	 * @return string[]
	 */
	public function action_links( $links ) {
		$links[] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( admin_url( 'tools.php?page=' . self::PAGE_SETTINGS ) ),
			esc_html__( 'Settings', 'perxel-toolkit' )
		);
		return $links;
	}

	/**
	 * Whether the current user may see the bundled UI-kit showcase - an
	 * administrator on a dev site that opts in with
	 * `define( 'PXTK_UI_SHOWCASE', true );` in wp-config.php, and only in a
	 * build that still ships showcase/ (the release zip strips it).
	 *
	 * @return bool
	 */
	public static function can_see_showcase() {
		return defined( 'PXTK_UI_SHOWCASE' ) && PXTK_UI_SHOWCASE
			&& current_user_can( 'manage_options' )
			&& class_exists( 'Perxel_UI_Showcase' );
	}

	/*
	---------------------------------------------------------------------
	 * Assets
	 * ------------------------------------------------------------------- */

	/**
	 * @param string $hook Current admin page hook.
	 */
	public function assets( $hook ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only screen switch.
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';

		$pages = array( self::PAGE_SETTINGS, self::PAGE_ADMIN_PAGE_GUARD, self::PAGE_RECOMMENDED_PLUGINS, self::PAGE_UI );

		if ( ! in_array( $page, $pages, true ) ) {
			return;
		}

		if ( class_exists( 'Perxel_UI' ) ) {
			\Perxel_UI::enqueue();
		}

		$css = PXTK_DIR . '/assets/css/admin.css';
		wp_enqueue_style( 'pxtk-admin', PXTK_URL . '/assets/css/admin.css', array( 'perxel-ui' ), file_exists( $css ) ? (string) filemtime( $css ) : PXTK_VERSION );

		if ( self::PAGE_SETTINGS === $page ) {
			$js = PXTK_DIR . '/assets/js/settings.js';
			wp_enqueue_script( 'pxtk-settings', PXTK_URL . '/assets/js/settings.js', array( 'perxel-ui', 'wp-i18n' ), file_exists( $js ) ? (string) filemtime( $js ) : PXTK_VERSION, true );
			wp_set_script_translations( 'pxtk-settings', 'perxel-toolkit', PXTK_DIR . '/languages' );
		}
	}

	/*
	---------------------------------------------------------------------
	 * Layout
	 * ------------------------------------------------------------------- */

	protected function plugin_header() {
		static $header = null;
		if ( null === $header ) {
			$header = get_file_data(
				PXTK_FILE,
				array(
					'name'       => 'Plugin Name',
					'plugin_uri' => 'Plugin URI',
					'author'     => 'Author',
					'author_uri' => 'Author URI',
				),
				'plugin'
			);
		}
		return $header;
	}

	/**
	 * @param string $current Active sidebar slug.
	 * @param string $title   Page title.
	 * @param array  $extra   Extra layout args (e.g. actions).
	 * @return array
	 */
	public function layout_args( $current, $title, array $extra = array() ) {
		$header = $this->plugin_header();

		$pages = array(
			self::PAGE_SETTINGS            => __( 'Settings', 'perxel-toolkit' ),
			self::PAGE_ADMIN_PAGE_GUARD    => Admin_Page_Guard::label(),
			self::PAGE_RECOMMENDED_PLUGINS => __( 'Recommended Plugins', 'perxel-toolkit' ),
		);

		if ( self::can_see_showcase() ) {
			$pages[ self::PAGE_UI ] = 'Perxel UI';
		}

		return array_merge(
			array(
				'title'       => $title,
				'plugin'      => PXTK_NAME,
				'version'     => PXTK_VERSION,
				'base'        => 'tools.php',
				'wrap_class'  => 'pxtk',
				'current'     => $current,
				'menu'        => array( '' => $pages ),
				'links'       => array( __( 'Docs', 'perxel-toolkit' ) => $header['plugin_uri'] ),
				'author'      => array(
					'name' => $header['author'],
					'url'  => $header['author_uri'],
				),
				'text_domain' => 'perxel-toolkit',
			),
			$extra
		);
	}

	public function ui_ready() {
		return class_exists( 'Perxel_UI' ) && class_exists( 'Perxel_UI_Layout' );
	}

	/**
	 * Open the shared layout, include a view, close it. Falls back to a plain
	 * wrap if the kit failed to load.
	 *
	 * @param string $current Active sidebar slug.
	 * @param string $title   Page title.
	 * @param string $view    View file name under includes/views/.
	 * @param array  $vars    Variables extracted into the view scope.
	 * @param array  $extra   Extra layout args.
	 */
	public function screen( $current, $title, $view, array $vars = array(), array $extra = array() ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$path = PXTK_DIR . '/includes/views/' . $view . '.php';

		if ( ! $this->ui_ready() ) {
			echo '<div class="wrap"><h1>' . esc_html( PXTK_NAME ) . '</h1>';
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The shared Perxel UI library could not be loaded. Run bin/update-ui.sh to vendor it.', 'perxel-toolkit' ) . '</p></div></div>';
			return;
		}

		\Perxel_UI_Layout::open( $this->layout_args( $current, $title, $extra ) );
		// phpcs:ignore WordPressVIPMinimum.Files.IncludingFile.UsingVariable -- fixed view path.
		( static function ( $__path, $__vars ) {
			// phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- controlled view vars.
			extract( $__vars );
			include $__path;
		} )( $path, $vars );
		\Perxel_UI_Layout::close();
	}

	/*
	---------------------------------------------------------------------
	 * Screen render callbacks
	 * ------------------------------------------------------------------- */

	public function render_settings() {
		$vars = array(
			'settings'  => Settings::all(),
			'updated'   => isset( $_GET['updated'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flash flag set by our own redirect.
			'was_reset' => isset( $_GET['reset'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flash flag set by our own redirect.
		);

		$save = get_submit_button(
			__( 'Save settings', 'perxel-toolkit' ),
			'primary',
			'pxtk-save',
			false,
			array( 'form' => 'pxtk-settings-form' )
		);

		$this->screen(
			self::PAGE_SETTINGS,
			__( 'Settings', 'perxel-toolkit' ),
			'settings',
			$vars,
			array( 'actions' => $save )
		);
	}

	public function render_admin_page_guard() {
		$vars = array(
			'updated' => isset( $_GET['updated'] ), // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- display-only flash flag set by our own redirect.
		);

		$save = get_submit_button(
			__( 'Save changes', 'perxel-toolkit' ),
			'primary',
			'pxtk-save-admin-page-guard',
			false,
			array( 'form' => 'pxtk-admin-page-guard-form' )
		);

		$page_url = menu_page_url( self::PAGE_ADMIN_PAGE_GUARD, false );

		if ( Admin_Page_Guard::is_previewing_as_restricted() ) {
			$view_as = '<a href="' . esc_url( $page_url ) . '" class="button">'
				. esc_html__( 'Back to normal view', 'perxel-toolkit' ) . '</a>';
		} else {
			$view_as = '<a href="' . esc_url( Admin_Page_Guard::view_as_url( $page_url ) ) . '" class="button">'
				. esc_html__( 'View as not-allowed user', 'perxel-toolkit' ) . '</a>';
		}

		$this->screen(
			self::PAGE_ADMIN_PAGE_GUARD,
			Admin_Page_Guard::label(),
			'admin-page-guard',
			$vars,
			array( 'actions' => $view_as . $save )
		);
	}

	public function render_recommended_plugins() {
		$this->screen(
			self::PAGE_RECOMMENDED_PLUGINS,
			__( 'Recommended Plugins', 'perxel-toolkit' ),
			'recommended-plugins'
		);
	}

	public function render_ui() {
		if ( ! self::can_see_showcase() || ! $this->ui_ready() ) {
			return;
		}
		\Perxel_UI_Layout::open( $this->layout_args( self::PAGE_UI, 'Perxel UI' ) );
		\Perxel_UI_Showcase::body();
		\Perxel_UI_Layout::close();
	}

	/*
	---------------------------------------------------------------------
	 * Handlers
	 * ------------------------------------------------------------------- */

	/**
	 * The two settings arrays a settings form posts - never the whole
	 * $_POST. Call only after the handler's nonce check.
	 *
	 * @return array{modules:array<string,string>,module_settings:array}
	 */
	private static function posted_settings(): array {
		$modules = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- each caller runs check_admin_referer() first.
		if ( isset( $_POST['modules'] ) && is_array( $_POST['modules'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- each caller runs check_admin_referer() first.
			$modules = map_deep( wp_unslash( $_POST['modules'] ), 'sanitize_key' );
		}

		// Field values are sanitised per field type in
		// Settings::sanitize_module_fields() - a generic text sanitiser here
		// would strip the percent-encoded octets a restricted-page URL can
		// legitimately contain (e.g. "%2F").
		$module_settings = array();
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- each caller runs check_admin_referer() first.
		if ( isset( $_POST['module_settings'] ) && is_array( $_POST['module_settings'] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- nonce checked by the caller; sanitised per field type, see above.
			$module_settings = wp_unslash( $_POST['module_settings'] );
		}

		return array(
			'modules'         => $modules,
			'module_settings' => $module_settings,
		);
	}

	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'perxel-toolkit' ) );
		}
		check_admin_referer( 'pxtk_save_settings' );

		Settings::update( Settings::sanitize( self::posted_settings() ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_SETTINGS,
					'updated' => '1',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	public function handle_save_admin_page_guard() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'perxel-toolkit' ) );
		}
		check_admin_referer( 'pxtk_save_admin_page_guard' );

		$raw    = self::posted_settings();
		$slug   = Admin_Page_Guard::slug();
		$values = isset( $raw['module_settings'][ $slug ] ) && is_array( $raw['module_settings'][ $slug ] ) ? $raw['module_settings'][ $slug ] : array();

		Settings::set_module_enabled( $slug, ! empty( $raw['modules'][ $slug ] ) );
		Settings::update_module_settings( $slug, Settings::sanitize_module_fields( Admin_Page_Guard::class, $values ) );

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => self::PAGE_ADMIN_PAGE_GUARD,
					'updated' => '1',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}

	public function handle_reset_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'perxel-toolkit' ) );
		}
		check_admin_referer( 'pxtk_reset_settings' );

		Settings::reset();

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => self::PAGE_SETTINGS,
					'reset' => '1',
				),
				admin_url( 'tools.php' )
			)
		);
		exit;
	}
}
