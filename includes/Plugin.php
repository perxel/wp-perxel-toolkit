<?php

namespace Perxel_Example;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boot: wire the admin surface. Instantiated once from the main file on
 * plugins_loaded.
 */
class Plugin {

	/**
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * @var Admin
	 */
	private $admin;

	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function admin() {
		return $this->admin;
	}

	public function boot() {
		$this->admin = new Admin();
		$this->admin->register();
	}

	/**
	 * Activation hook. Seed options / create tables here.
	 */
	public static function activate() {
		if ( false === get_option( PXEX_OPTION_KEY, false ) ) {
			add_option( PXEX_OPTION_KEY, Settings::defaults() );
		}
	}
}
