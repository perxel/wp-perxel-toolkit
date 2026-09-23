<?php
/**
 * Admin Page Guard's own settings screen: the allowed-user and
 * restricted-page lists (Module::field_rows()). The "View as not-allowed
 * user" button next to Save changes (rendered by
 * Admin::render_admin_page_guard()) reloads this same screen with a
 * one-off query flag that makes the guard treat the request as
 * not-allowed, so the restricted pages disappear from the admin menu for
 * that one load. Nothing is switched or persisted - navigate away (or just
 * drop the query arg) and the menu is back to normal.
 *
 * @package Perxel_Toolkit
 *
 * @var bool $updated Whether the form just saved.
 */

use Perxel_Toolkit\Admin;
use Perxel_Toolkit\Modules\Admin_Page_Guard;
use Perxel_Toolkit\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $updated ) {
	Admin::kit( \Perxel_UI::notice( 'success', esc_html__( 'Settings saved.', 'perxel-toolkit' ), array( 'dismissible' => true ) ) );
}

if ( Admin_Page_Guard::is_previewing_as_restricted() ) {
	Admin::kit(
		\Perxel_UI::notice(
			'warning',
			esc_html__( 'Previewing as a not-allowed user - restricted pages are hidden from the menu on this load only. Use "Back to normal view" above to exit.', 'perxel-toolkit' )
		)
	);
}

$pxtk_slug = Admin_Page_Guard::slug();
?>
<form id="pxtk-admin-page-guard-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pxui-dirty-guard>
	<input type="hidden" name="action" value="pxtk_save_admin_page_guard" />
	<?php wp_nonce_field( 'pxtk_save_admin_page_guard' ); ?>

	<?php
	Admin::kit(
		\Perxel_UI::rows(
			array(
				array(
					'rows' => array(
						array(
							'label'   => Admin_Page_Guard::label(),
							'sub'     => esc_html( Admin_Page_Guard::description() ),
							'content' => \Perxel_UI::toggle(
								array(
									'name'    => 'modules[' . $pxtk_slug . ']',
									'checked' => Settings::is_module_enabled( $pxtk_slug ),
									'label'   => Admin_Page_Guard::label(),
								)
							),
						),
					),
				),
				array(
					'title' => __( 'Access rules', 'perxel-toolkit' ),
					'note'  => __( 'Values set via the pxtk_admin_page_guard_* filters still apply and are merged with these.', 'perxel-toolkit' ),
					'rows'  => Admin_Page_Guard::field_rows(),
				),
			)
		)
	);
	?>
</form>

