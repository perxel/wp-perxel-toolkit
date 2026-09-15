<?php
/**
 * Uninstall cleanup. Runs only when the plugin is deleted from the Plugins
 * screen. Remove the settings option here, plus any custom tables or extra
 * options the plugin created.
 *
 * @package Perxel_Toolkit
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'pxtk_settings' );

/*
 * If the plugin creates custom tables, drop them here through an
 * includes/Db.php helper (require it, then call Db::uninstall()). Bind the
 * table name with the %i placeholder, never string concatenation - see the
 * "Custom tables" section of CLAUDE.md. Each drop is:
 *
 *     $wpdb->query( $wpdb->prepare( 'DROP TABLE IF EXISTS %i', $table ) );
 *
 * with an inline phpcs:ignore of WordPress.DB.DirectDatabaseQuery.DirectQuery,
 * NoCaching and SchemaChange (dropping your own table on uninstall).
 */
