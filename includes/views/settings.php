<?php
/**
 * Settings screen: one toggle per module, grouped into "Features" (no
 * dependency, always available) and "Integrations" (config for a specific
 * third-party plugin or theme convention - greyed out with an install
 * link/message until its dependency is detected).
 *
 * @package Perxel_Toolkit
 *
 * @var array $settings  Settings::all().
 * @var bool  $updated   Whether the form just saved.
 * @var bool  $was_reset Whether settings were just reset.
 */

use Perxel_Toolkit\Modules\Registry;
use Perxel_Toolkit\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Perxel_UI escapes structure; dynamic values escaped inline.

if ( $updated ) {
	echo \Perxel_UI::notice( 'success', esc_html__( 'Settings saved.', 'perxel-toolkit' ), array( 'dismissible' => true ) );
} elseif ( $was_reset ) {
	echo \Perxel_UI::notice( 'success', esc_html__( 'Settings reset to defaults.', 'perxel-toolkit' ), array( 'dismissible' => true ) );
}

$pxtk_reset_url = wp_nonce_url(
	add_query_arg( array( 'action' => 'pxtk_reset_settings' ), admin_url( 'admin-post.php' ) ),
	'pxtk_reset_settings'
);

$pxtk_modules = $settings['modules'];

/**
 * Render one module's settings_fields() as controls for a "Configure"
 * disclosure's `details`. Empty string when the module has no fields.
 *
 * @param class-string<\Perxel_Toolkit\Modules\Module> $module Module class.
 * @return string
 */
$pxtk_module_fields_html = static function ( $module ) {
	$fields = $module::settings_fields();
	if ( ! $fields ) {
		return '';
	}

	$values = Settings::module_settings( $module::slug() );
	$roles  = wp_roles()->get_names();
	unset( $roles['administrator'] );

	$field_rows = array();

	foreach ( $fields as $field ) {
		$key   = $field['key'];
		$value = $values[ $key ] ?? $field['default'];
		$name  = 'module_settings[' . $module::slug() . '][' . $key . ']';

		if ( 'roles' === $field['type'] ) {
			$control = \Perxel_UI::checkbox_group(
				array(
					'name'     => $name,
					'options'  => $roles,
					'selected' => (array) $value,
				)
			);
		} else {
			$control = \Perxel_UI::toggle(
				array(
					'name'    => $name,
					'checked' => ! empty( $value ),
					'label'   => $field['label'],
				)
			);
		}

		$field_rows[] = array(
			'label'   => $field['label'],
			'content' => $control,
		);
	}

	return \Perxel_UI::rows( $field_rows );
};

/**
 * Build the row(s) for one module: its on/off toggle, plus a "Configure"
 * disclosure row when it declares settings_fields().
 *
 * @param class-string<\Perxel_Toolkit\Modules\Module> $module Module class.
 * @return array[]
 */
$pxtk_module_rows = static function ( $module ) use ( $pxtk_modules, $pxtk_module_fields_html ) {
	$slug      = $module::slug();
	$available = $module::is_available();
	$checked   = ! empty( $pxtk_modules[ $slug ] );

	$toggle_attr  = ' name="modules[' . esc_attr( $slug ) . ']"';
	$toggle_attr .= ' aria-label="' . esc_attr( $module::label() ) . '"';
	$toggle_attr .= $checked ? ' checked' : '';
	$toggle_attr .= $available ? '' : ' disabled';
	$content      = '<input type="checkbox" class="pxui-toggle" value="1"' . $toggle_attr . ' />';

	$row = array(
		'label'   => $module::label(),
		'sub'     => esc_html( $module::description() ),
		'content' => $content,
	);

	$dependency = $module::dependency();

	if ( null !== $dependency ) {
		$row['icon'] = $available ? 'good' : 'muted';

		if ( ! $available ) {
			// Not available: append a status line + an install action
			// (Recommended plugin) under the description. wporg_slug gets a
			// real one-click "Install Now" when the user may install
			// plugins; otherwise a plain link (e.g. Gravity Forms isn't on
			// wordpress.org).
			$action = '';

			if ( ! empty( $dependency['wporg_slug'] ) && current_user_can( 'install_plugins' ) ) {
				$install_url = wp_nonce_url(
					self_admin_url( 'update.php?action=install-plugin&plugin=' . rawurlencode( $dependency['wporg_slug'] ) ),
					'install-plugin_' . $dependency['wporg_slug']
				);
				$action = '<a class="button button-small" href="' . esc_url( $install_url ) . '">' . esc_html__( 'Install Now', 'perxel-toolkit' ) . '</a>';
			} elseif ( ! empty( $dependency['install_url'] ) ) {
				$action = '<a class="button button-small" href="' . esc_url( $dependency['install_url'] ) . '" target="_blank" rel="noopener noreferrer">'
					/* translators: %s: name of the required plugin/theme. */
					. sprintf( esc_html__( 'Get %s', 'perxel-toolkit' ), esc_html( $dependency['label'] ) ) . '</a>';
			}

			$notice = '<span class="pxtk-dependency-notice">'
				/* translators: %s: name of the required plugin/theme. */
				. sprintf( esc_html__( 'Not detected: %s.', 'perxel-toolkit' ), esc_html( $dependency['label'] ) )
				. '</span>';

			$row['sub'] .= '<br />' . $notice . ( $action ? ' ' . $action : '' );
		}
	}

	$rows = array( $row );

	// A disabled dependency has nothing to configure yet; only surface
	// "Configure" once the module can actually run.
	if ( $available ) {
		$fields_html = $pxtk_module_fields_html( $module );

		if ( '' !== $fields_html ) {
			$rows[] = array(
				'summary' => __( 'Configure', 'perxel-toolkit' ),
				'sub'     => esc_html__( 'Roles and capabilities this module applies to.', 'perxel-toolkit' ),
				'details' => $fields_html,
			);
		}
	}

	return $rows;
};

$pxtk_feature_rows     = array_merge( array(), ...array_map( $pxtk_module_rows, Registry::by_group( 'feature' ) ) );
$pxtk_integration_rows = array_merge( array(), ...array_map( $pxtk_module_rows, Registry::by_group( 'integration' ) ) );
?>
<form id="pxtk-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pxui-dirty-guard>
	<input type="hidden" name="action" value="pxtk_save_settings" />
	<?php wp_nonce_field( 'pxtk_save_settings' ); ?>

	<?php
	echo \Perxel_UI::rows(
		array(
			array(
				'title' => __( 'Features', 'perxel-toolkit' ),
				'rows'  => $pxtk_feature_rows,
			),
			array(
				'title' => __( 'Integrations', 'perxel-toolkit' ),
				'note'  => __( 'Config for a specific third-party plugin or theme convention - greyed out until it is detected on this site.', 'perxel-toolkit' ),
				'rows'  => $pxtk_integration_rows,
			),
		)
	);
	?>
</form>

<?php
echo \Perxel_UI::rows(
	array(
		array(
			'title'  => __( 'Danger zone', 'perxel-toolkit' ),
			'danger' => true,
			'rows'   => array(
				array(
					'label'   => __( 'Reset settings', 'perxel-toolkit' ),
					'sub'     => esc_html__( 'Restore every module toggle on this screen to its default (all enabled).', 'perxel-toolkit' ),
					'content' => '<a class="button" href="' . esc_url( $pxtk_reset_url ) . '">' . esc_html__( 'Reset', 'perxel-toolkit' ) . '</a>',
				),
			),
		),
	)
);

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
