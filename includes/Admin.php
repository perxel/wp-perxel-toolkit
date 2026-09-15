<?php

namespace Perxel_Example;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin surface: one "Tools -> Perxel Example" screen, rendered inside the
 * shared Perxel UI layout (vendor/perxel-ui). Owns menu registration, asset
 * loading, the shared layout args, and the settings form handlers.
 *
 * Add screens by giving each a slug constant, an off-menu add_submenu_page()
 * call in menu(), a $titles entry, and a render_*() callback that delegates to
 * screen(). The kit's in-page sidebar nav links them together.
 */
class Admin {

	const PAGE_SETTINGS = 'pxex';
	const PAGE_UI       = 'pxex-ui';

	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'assets' ) );

		add_filter( 'plugin_action_links_' . plugin_basename( PXEX_FILE ), array( $this, 'action_links' ) );

		add_action( 'admin_post_pxex_save_settings', array( $this, 'handle_save_settings' ) );
		add_action( 'admin_post_pxex_reset_settings', array( $this, 'handle_reset_settings' ) );
	}

	/*
	---------------------------------------------------------------------
	 * Menu
	 * ------------------------------------------------------------------- */

	public function menu() {
		add_management_page(
			PXEX_NAME,
			PXEX_NAME,
			'manage_options',
			self::PAGE_SETTINGS,
			array( $this, 'render_settings' )
		);

		$titles = array(
			self::PAGE_SETTINGS => __( 'Settings', 'perxel-example' ),
		);

		// The bundled UI-kit showcase - a hidden, maintainer-only screen, and
		// only in a build that still ships showcase/.
		if ( self::can_see_showcase() ) {
			add_submenu_page( null, 'Perxel UI', '', 'manage_options', self::PAGE_UI, array( $this, 'render_ui' ) );
			$titles[ self::PAGE_UI ] = 'Perxel UI';
		}

		if ( class_exists( 'Perxel_UI_Layout' ) ) {
			\Perxel_UI_Layout::set_page_titles( $titles, PXEX_NAME );
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
			esc_html__( 'Settings', 'perxel-example' )
		);
		return $links;
	}

	/**
	 * Whether the current user may see the bundled UI-kit showcase - the
	 * maintainer only, and only in a build that still ships showcase/.
	 *
	 * @return bool
	 */
	public static function can_see_showcase() {
		if ( ! current_user_can( 'manage_options' ) || ! class_exists( 'Perxel_UI_Showcase' ) ) {
			return false;
		}
		$user = wp_get_current_user();
		return $user && ( 'phucbm' === $user->user_login || 'phucbm.dev@gmail.com' === strtolower( (string) $user->user_email ) );
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

		if ( ! in_array( $page, array( self::PAGE_SETTINGS, self::PAGE_UI ), true ) ) {
			return;
		}

		if ( class_exists( 'Perxel_UI' ) ) {
			\Perxel_UI::enqueue();
		}

		$css = PXEX_DIR . '/assets/css/admin.css';
		wp_enqueue_style( 'pxex-admin', PXEX_URL . '/assets/css/admin.css', array( 'perxel-ui' ), file_exists( $css ) ? (string) filemtime( $css ) : PXEX_VERSION );

		if ( self::PAGE_SETTINGS === $page ) {
			$js = PXEX_DIR . '/assets/js/settings.js';
			wp_enqueue_script( 'pxex-settings', PXEX_URL . '/assets/js/settings.js', array( 'perxel-ui', 'wp-i18n' ), file_exists( $js ) ? (string) filemtime( $js ) : PXEX_VERSION, true );
			wp_set_script_translations( 'pxex-settings', 'perxel-example', PXEX_DIR . '/languages' );
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
				PXEX_FILE,
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
			self::PAGE_SETTINGS => __( 'Settings', 'perxel-example' ),
		);

		if ( self::can_see_showcase() ) {
			$pages[ self::PAGE_UI ] = 'Perxel UI';
		}

		return array_merge(
			array(
				'title'       => $title,
				'plugin'      => PXEX_NAME,
				'version'     => PXEX_VERSION,
				'base'        => 'tools.php',
				'wrap_class'  => 'pxex',
				'current'     => $current,
				'menu'        => array( '' => $pages ),
				'links'       => array( __( 'Docs', 'perxel-example' ) => $header['plugin_uri'] ),
				'author'      => array(
					'name' => $header['author'],
					'url'  => $header['author_uri'],
				),
				'text_domain' => 'perxel-example',
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

		$path = PXEX_DIR . '/includes/views/' . $view . '.php';

		if ( ! $this->ui_ready() ) {
			echo '<div class="wrap"><h1>' . esc_html( PXEX_NAME ) . '</h1>';
			echo '<div class="notice notice-error"><p>' . esc_html__( 'The shared Perxel UI library could not be loaded. Run bin/update-ui.sh to vendor it.', 'perxel-example' ) . '</p></div></div>';
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
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- display-only flash flags set by our own redirect.
		$vars = array(
			'settings'  => Settings::all(),
			'updated'   => isset( $_GET['updated'] ),
			'was_reset' => isset( $_GET['reset'] ),
		);
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$save = get_submit_button(
			__( 'Save settings', 'perxel-example' ),
			'primary',
			'pxex-save',
			false,
			array( 'form' => 'pxex-settings-form' )
		);

		$this->screen(
			self::PAGE_SETTINGS,
			__( 'Settings', 'perxel-example' ),
			'settings',
			$vars,
			array( 'actions' => $save )
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

	public function handle_save_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'perxel-example' ) );
		}
		check_admin_referer( 'pxex_save_settings' );

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- nonce checked above; sanitised in Settings::sanitize().
		$raw = wp_unslash( $_POST );
		Settings::update( Settings::sanitize( is_array( $raw ) ? $raw : array() ) );

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

	public function handle_reset_settings() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'perxel-example' ) );
		}
		check_admin_referer( 'pxex_reset_settings' );

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
