<?php
/**
 * Settings screen.
 *
 * @package Perxel_Example
 *
 * @var array $settings  Settings::all().
 * @var bool  $updated   Whether the form just saved.
 * @var bool  $was_reset Whether settings were just reset.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Perxel_UI escapes structure; dynamic values escaped inline.

if ( $updated ) {
	echo \Perxel_UI::notice( 'success', esc_html__( 'Settings saved.', 'perxel-example' ), array( 'dismissible' => true ) );
} elseif ( $was_reset ) {
	echo \Perxel_UI::notice( 'success', esc_html__( 'Settings reset to defaults.', 'perxel-example' ), array( 'dismissible' => true ) );
}

$pxex_reset_url = wp_nonce_url(
	add_query_arg( array( 'action' => 'pxex_reset_settings' ), admin_url( 'admin-post.php' ) ),
	'pxex_reset_settings'
);
?>
<form id="pxex-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pxui-dirty-guard>
	<input type="hidden" name="action" value="pxex_save_settings" />
	<?php wp_nonce_field( 'pxex_save_settings' ); ?>

	<?php
	echo \Perxel_UI::rows(
		array(
			array(
				'title' => __( 'General', 'perxel-example' ),
				'rows'  => array(
					array(
						'label'   => __( 'Enabled', 'perxel-example' ),
						'sub'     => esc_html__( 'Turn the plugin on or off without deactivating it.', 'perxel-example' ),
						'content' => \Perxel_UI::toggle(
							array(
								'name'    => 'enabled',
								'checked' => (bool) $settings['enabled'],
								'label'   => __( 'Enabled', 'perxel-example' ),
							)
						),
					),
					array(
						'label'   => __( 'API key', 'perxel-example' ),
						'sub'     => esc_html__( 'Example text field. Replace with the settings your plugin needs.', 'perxel-example' ),
						'content' => '<input type="password" name="api_key" autocomplete="off" value="' . esc_attr( $settings['api_key'] ) . '" />',
					),
				),
			),
		)
	);
	?>
</form>

<?php
echo \Perxel_UI::rows(
	array(
		array(
			'title'  => __( 'Danger zone', 'perxel-example' ),
			'danger' => true,
			'rows'   => array(
				array(
					'label'   => __( 'Reset settings', 'perxel-example' ),
					'sub'     => esc_html__( 'Restore every setting on this screen to its default.', 'perxel-example' ),
					'content' => '<a class="button" href="' . esc_url( $pxex_reset_url ) . '">' . esc_html__( 'Reset', 'perxel-example' ) . '</a>',
				),
			),
		),
	)
);

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
