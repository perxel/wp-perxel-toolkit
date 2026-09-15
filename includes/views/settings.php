<?php
/**
 * Settings screen: one toggle per module, grouped by Module::group() into
 * sections (see $pxtk_sections below) - "Features" (no dependency, always
 * available), "Access Control" (restricts/gates wp-admin access), and
 * "Integrations" (config for a specific third-party plugin or theme
 * convention - greyed out with an install link/message until its dependency
 * is detected).
 *
 * @package Perxel_Toolkit
 *
 * @var array $settings  Settings::all().
 * @var bool  $updated   Whether the form just saved.
 * @var bool  $was_reset Whether settings were just reset.
 */

use Perxel_Toolkit\Modules\Registry;
use Perxel_Toolkit\Recommended_Plugins;
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
 * Render one module's settings_fields() (via Module::field_rows()) as
 * controls for a "Configure" disclosure's `details`. Empty string when the
 * module has no fields, or when it has its own dedicated settings_page()
 * instead (see $pxtk_module_rows).
 *
 * @param class-string<\Perxel_Toolkit\Modules\Module> $module Module class.
 * @return string
 */
$pxtk_module_fields_html = static function ( $module ) {
	if ( $module::settings_page() ) {
		return '';
	}

	$field_rows = $module::field_rows();

	return $field_rows ? \Perxel_UI::rows( $field_rows ) : '';
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

	$settings_page = $module::settings_page();
	if ( $settings_page && $available ) {
		$configure_url = admin_url( 'tools.php?page=' . $settings_page );
		$content       = '<a class="button button-small" href="' . esc_url( $configure_url ) . '">' . esc_html__( 'Configure', 'perxel-toolkit' ) . '</a> ' . $content;
	}

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
				$action      = '<a class="button button-small" href="' . esc_url( $install_url ) . '">' . esc_html__( 'Install Now', 'perxel-toolkit' ) . '</a>';
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

// Section title/note per module group. A group with no modules in it is
// skipped below, so adding a new Module::group() value here is enough to
// give it its own section.
$pxtk_sections = array(
	'feature'     => array(
		'title' => __( 'Features', 'perxel-toolkit' ),
	),
	'security'    => array(
		'title' => __( 'Access Control', 'perxel-toolkit' ),
		'note'  => __( 'Modules that restrict or gate access to parts of wp-admin.', 'perxel-toolkit' ),
	),
	'integration' => array(
		'title' => __( 'Integrations', 'perxel-toolkit' ),
		'note'  => __( 'Config for a specific third-party plugin or theme convention - greyed out until it is detected on this site.', 'perxel-toolkit' ),
	),
);

$pxtk_module_sections = array();
foreach ( $pxtk_sections as $pxtk_group => $pxtk_section ) {
	$pxtk_group_modules = Registry::by_group( $pxtk_group );
	if ( ! $pxtk_group_modules ) {
		continue;
	}
	$pxtk_module_sections[] = array_merge(
		$pxtk_section,
		array( 'rows' => array_merge( array(), ...array_map( $pxtk_module_rows, $pxtk_group_modules ) ) )
	);
}

// Perxel's curated "install on every project" list - a plain install link,
// grouped the same way as the Features/Integrations sections above.
$pxtk_recommended_groups = array();
foreach ( Recommended_Plugins::all() as $pxtk_plugin ) {
	$pxtk_recommended_groups[ $pxtk_plugin['group'] ][] = array(
		'label'   => $pxtk_plugin['label'],
		'sub'     => esc_html( $pxtk_plugin['description'] ),
		'content' => Recommended_Plugins::install_button( $pxtk_plugin ),
	);
}

$pxtk_recommended_sections = array();
foreach ( $pxtk_recommended_groups as $pxtk_group_title => $pxtk_group_rows ) {
	$pxtk_recommended_sections[] = array(
		'title' => $pxtk_group_title,
		'note'  => empty( $pxtk_recommended_sections )
			? __( "Perxel's curated plugin list for every project. Free/wordpress.org plugins install directly; others open the vendor's page.", 'perxel-toolkit' )
			: '',
		'rows'  => $pxtk_group_rows,
	);
}
?>
<form id="pxtk-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pxui-dirty-guard>
	<input type="hidden" name="action" value="pxtk_save_settings" />
	<?php wp_nonce_field( 'pxtk_save_settings' ); ?>

	<?php echo \Perxel_UI::rows( $pxtk_module_sections ); ?>
</form>

<?php echo \Perxel_UI::rows( $pxtk_recommended_sections ); ?>

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
