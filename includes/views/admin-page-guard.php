<?php
/**
 * Admin Page Guard's own settings screen: the allowed-user and
 * restricted-page lists (Module::field_rows()), plus a "View as" preview per
 * restricted page - reloads that URL with a one-off, nonce-protected flag
 * that makes the guard treat the request as not-allowed, reproducing the
 * same soft redirect a real restricted user would hit. Nothing is switched
 * or persisted; it only affects that single page load.
 *
 * @package Perxel_Toolkit
 *
 * @var bool $updated Whether the form just saved.
 */

use Perxel_Toolkit\Modules\Admin_Page_Guard;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped -- Perxel_UI escapes structure; dynamic values escaped inline.

if ( $updated ) {
	echo \Perxel_UI::notice( 'success', esc_html__( 'Settings saved.', 'perxel-toolkit' ), array( 'dismissible' => true ) );
}
?>
<form id="pxtk-admin-page-guard-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-pxui-dirty-guard>
	<input type="hidden" name="action" value="pxtk_save_admin_page_guard" />
	<?php wp_nonce_field( 'pxtk_save_admin_page_guard' ); ?>

	<?php
	echo \Perxel_UI::rows(
		array(
			array(
				'title' => __( 'Access rules', 'perxel-toolkit' ),
				'note'  => __( 'One entry per line. Values set via the pxtk_admin_page_guard_* filters still apply and are merged with these.', 'perxel-toolkit' ),
				'rows'  => Admin_Page_Guard::field_rows(),
			),
		)
	);
	?>
</form>

<?php
$pxtk_preview_rows = array();

foreach ( Admin_Page_Guard::restricted_pages() as $pxtk_page_url ) {
	$pxtk_preview_rows[] = array(
		'label'   => $pxtk_page_url,
		'content' => '<a class="button button-small" href="' . esc_url( Admin_Page_Guard::view_as_url( $pxtk_page_url ) ) . '">'
			. esc_html__( 'View as', 'perxel-toolkit' ) . ' &rarr;</a>',
	);
}

if ( $pxtk_preview_rows ) {
	echo \Perxel_UI::rows(
		array(
			array(
				'title' => __( 'Preview', 'perxel-toolkit' ),
				'note'  => __( 'Opens the page as a restricted user would see it - redirected straight back to the dashboard. Nothing is changed; this only affects that one page load.', 'perxel-toolkit' ),
				'rows'  => $pxtk_preview_rows,
			),
		)
	);
}

// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
