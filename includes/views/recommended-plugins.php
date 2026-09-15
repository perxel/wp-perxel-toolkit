<?php
/**
 * Recommended Plugins screen: Perxel's curated "install on every project"
 * list (see Recommended_Plugins), grouped the same way the Integrations
 * section on the main Settings screen is. These are not part of the
 * toolkit itself - just plugins Perxel suggests. A plugin already detected
 * as installed/active shows a check instead of an install action.
 *
 * @package Perxel_Toolkit
 */

use Perxel_Toolkit\Recommended_Plugins;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Perxel_UI escapes structure; dynamic values escaped inline.

echo \Perxel_UI::notice(
	'info',
	esc_html__( "These are plugins Perxel recommends for every project - they're not part of this toolkit. Free/wordpress.org plugins install directly below; others open the vendor's page.", 'perxel-toolkit' )
);

$pxtk_recommended_groups = array();
foreach ( Recommended_Plugins::all() as $pxtk_plugin ) {
	$pxtk_installed = Recommended_Plugins::is_installed( $pxtk_plugin );

	$pxtk_recommended_groups[ $pxtk_plugin['group'] ][] = array(
		'icon'    => $pxtk_installed ? 'good' : 'muted',
		'label'   => $pxtk_plugin['label'],
		'sub'     => esc_html( $pxtk_plugin['description'] ),
		'content' => $pxtk_installed ? '' : Recommended_Plugins::install_button( $pxtk_plugin ),
	);
}

$pxtk_recommended_sections = array();
foreach ( $pxtk_recommended_groups as $pxtk_group_title => $pxtk_group_rows ) {
	$pxtk_recommended_sections[] = array(
		'title' => $pxtk_group_title,
		'rows'  => $pxtk_group_rows,
	);
}

echo \Perxel_UI::rows( $pxtk_recommended_sections );

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
