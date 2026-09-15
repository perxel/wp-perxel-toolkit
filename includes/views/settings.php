<?php
/**
 * Settings screen: one toggle per module, grouped by Module::group() into
 * sections (see $pxtk_sections below) - "Features" (no dependency, always
 * available), "Access Control" (restricts/gates wp-admin access), and
 * "Integrations" (config for a specific third-party plugin or theme
 * convention - only listed here once its dependency is detected on this
 * site; see the Recommended Plugins screen for the full supported list). A
 * module with its own dedicated settings_page() (e.g. Admin_Page_Guard) has
 * no row on this screen at all - including its own on/off toggle, which
 * lives on that page instead - and a section with nothing left in it after
 * that filtering is skipped entirely.
 *
 * @package Perxel_Toolkit
 *
 * @var array $settings  Settings::all().
 * @var bool  $updated   Whether the form just saved.
 * @var bool  $was_reset Whether settings were just reset.
 */

use Perxel_Toolkit\Admin;
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
 * Render one module's settings_fields() (via Module::field_rows()) as
 * controls for a "Configure" disclosure's `details`. Empty string when the
 * module has no fields. A module with its own dedicated settings_page()
 * never reaches this - it's filtered out of $pxtk_module_sections below and
 * has no row on this screen at all.
 *
 * @param class-string<\Perxel_Toolkit\Modules\Module> $module Module class.
 * @return string
 */
$pxtk_module_fields_html = static function ( $module ) {
	$field_rows = $module::field_rows();

	return $field_rows ? \Perxel_UI::rows( $field_rows ) : '';
};

/**
 * Build the row for one module: its on/off toggle, expanding in place (a
 * disclosure row) to reveal its fields when it declares settings_fields().
 * Only ever called for an available module with no dedicated
 * settings_page() - both are filtered out before this runs (see
 * $pxtk_module_sections below), so there is no "not available" state to
 * render here.
 *
 * The toggle in `content` sits inside the disclosure's clickable <summary>;
 * assets/js/settings.js stops its click from also collapsing/expanding the
 * row, so the switch and the disclosure act as two independent controls.
 *
 * @param class-string<\Perxel_Toolkit\Modules\Module> $module Module class.
 * @return array
 */
$pxtk_module_row = static function ( $module ) use ( $pxtk_modules, $pxtk_module_fields_html ) {
	$slug    = $module::slug();
	$checked = ! empty( $pxtk_modules[ $slug ] );

	$toggle_attr  = ' name="modules[' . esc_attr( $slug ) . ']"';
	$toggle_attr .= ' aria-label="' . esc_attr( $module::label() ) . '"';
	$toggle_attr .= $checked ? ' checked' : '';
	$toggle_attr .= ' class="pxui-toggle pxtk-row-toggle"';
	$content      = '<input type="checkbox" value="1"' . $toggle_attr . ' />';

	$fields_html = $pxtk_module_fields_html( $module );

	$row = $fields_html
		? array(
			'summary' => $module::label(),
			'sub'     => esc_html( $module::description() ),
			'content' => $content,
			'details' => $fields_html,
		)
		: array(
			'label'   => $module::label(),
			'sub'     => esc_html( $module::description() ),
			'content' => $content,
		);

	if ( null !== $module::dependency() ) {
		$row['icon'] = 'good';
	}

	return $row;
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
		'note'  => sprintf(
			/* translators: %s: link to the Recommended Plugins screen. */
			__( 'Config for a specific third-party plugin or theme convention - listed here once that plugin is detected on this site. %s', 'perxel-toolkit' ),
			'<a href="' . esc_url( admin_url( 'tools.php?page=' . Admin::PAGE_RECOMMENDED_PLUGINS ) ) . '">' . esc_html__( 'See more supported integrations', 'perxel-toolkit' ) . '</a>'
		),
	),
);

$pxtk_module_sections = array();
foreach ( $pxtk_sections as $pxtk_group => $pxtk_section ) {
	// A module with its own dedicated settings_page() is fully managed
	// there (including its own on/off toggle) - it has no row here.
	$pxtk_group_modules = array_values(
		array_filter(
			Registry::by_group( $pxtk_group ),
			static function ( $pxtk_module ) {
				return ! $pxtk_module::settings_page();
			}
		)
	);

	// Integrations: only list a module once its dependency is actually
	// detected - the full supported list (installed or not) lives on the
	// Recommended Plugins screen instead (see the section note above).
	if ( 'integration' === $pxtk_group ) {
		$pxtk_group_modules = array_values(
			array_filter(
				$pxtk_group_modules,
				static function ( $pxtk_module ) {
					return $pxtk_module::is_available();
				}
			)
		);
	}

	if ( ! $pxtk_group_modules ) {
		continue;
	}

	$pxtk_module_sections[] = array_merge(
		$pxtk_section,
		array( 'rows' => array_map( $pxtk_module_row, $pxtk_group_modules ) )
	);
}
?>
<form id="pxtk-settings-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pxui-dirty-guard>
	<input type="hidden" name="action" value="pxtk_save_settings" />
	<?php wp_nonce_field( 'pxtk_save_settings' ); ?>

	<?php echo \Perxel_UI::rows( $pxtk_module_sections ); ?>
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
