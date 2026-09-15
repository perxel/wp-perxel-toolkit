<?php

namespace Perxel_Toolkit\Modules;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Gravity Forms integration: lets the same form run multiple times on one
 * page (AJAX) without ID conflicts, by rewriting every occurrence of the
 * form's numeric ID to a per-render random ID in the rendered HTML.
 *
 * Ported from wp-mu-plugins/gf-multiple-form-instances.php (unofficial
 * add-on originally by Nikunj, github.com/nikunj8866).
 */
class Gravity_Forms extends Module {

	public static function slug(): string {
		return 'gravity-forms';
	}

	public static function label(): string {
		return __( 'Gravity Forms', 'perxel-toolkit' );
	}

	public static function description(): string {
		return __( 'Run multiple instances of the same form on one page (AJAX) without ID conflicts.', 'perxel-toolkit' );
	}

	public static function group(): string {
		return 'integration';
	}

	public static function dependency(): array {
		return array(
			'label'       => __( 'Gravity Forms', 'perxel-toolkit' ),
			'check'       => static function () {
				return class_exists( 'GFForms' );
			},
			// Not on wordpress.org - no one-click install slug, only a link.
			'install_url' => 'https://www.gravityforms.com/',
		);
	}

	public function register(): void {
		add_filter( 'gform_get_form_filter', array( $this, 'rewrite_form_ids' ), 10, 2 );
	}

	/**
	 * Replace every occurrence of the form's numeric ID with a unique ID.
	 *
	 * @param string $form_string Rendered form HTML.
	 * @param array  $form        Form settings.
	 * @return string
	 */
	public function rewrite_form_ids( $form_string, $form ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- carries the render's own random ID across an AJAX resubmit, not a state change.
		$random_id = isset( $_POST['gform_random_id'] ) ? absint( wp_unslash( $_POST['gform_random_id'] ) ) : wp_rand();

		$hidden_field = "<input type='hidden' name='gform_field_values'";
		$form_id      = $form['id'];

		$strings = array(
			' gform_wrapper '                                             => ' gform_wrapper gform_wrapper_original_id_' . $form_id . ' ',
			"for='choice_"                                                => "for='choice_" . $random_id . '_',
			"id='choice_"                                                 => "id='choice_" . $random_id . '_',
			"id='gform_target_page_number_"                               => "id='gform_target_page_number_" . $random_id . '_',
			"id='gform_source_page_number_"                               => "id='gform_source_page_number_" . $random_id . '_',
			'#gform_target_page_number_'                                  => '#gform_target_page_number_' . $random_id . '_',
			'#gform_source_page_number_'                                  => '#gform_source_page_number_' . $random_id . '_',
			"id='label_"                                                  => "id='label_" . $random_id . '_',
			"'gform_wrapper_" . $form_id . "'"                            => "'gform_wrapper_" . $random_id . "'",
			"'gf_" . $form_id . "'"                                       => "'gf_" . $random_id . "'",
			"'gform_" . $form_id . "'"                                    => "'gform_" . $random_id . "'",
			"'gform_ajax_frame_" . $form_id . "'"                         => "'gform_ajax_frame_" . $random_id . "'",
			"#gf_" . $form_id . "'"                                       => '#gf_' . $random_id . "'",
			"'gform_fields_" . $form_id . "'"                             => "'gform_fields_" . $random_id . "'",
			"id='field_" . $form_id . '_'                                 => "id='field_" . $random_id . '_',
			"for='input_" . $form_id . '_'                                => "for='input_" . $random_id . '_',
			"id='input_" . $form_id . '_'                                 => "id='input_" . $random_id . '_',
			"'gform_submit_button_" . $form_id . "'"                      => "'gform_submit_button_" . $random_id . "'",
			'"gf_submitting_' . $form_id . '"'                            => '"gf_submitting_' . $random_id . '"',
			"'gf_submitting_" . $form_id . "'"                            => "'gf_submitting_" . $random_id . "'",
			'#gform_ajax_frame_' . $form_id                               => '#gform_ajax_frame_' . $random_id,
			'#gform_wrapper_' . $form_id                                  => '#gform_wrapper_' . $random_id,
			'#gform_' . $form_id                                          => '#gform_' . $random_id,
			"trigger('gform_post_render', [" . $form_id                  => "trigger('gform_post_render', [" . $random_id,
			'gformInitSpinner( ' . $form_id . ', '                        => 'gformInitSpinner( ' . $random_id . ', ',
			"trigger('gform_page_loaded', [" . $form_id                  => "trigger('gform_page_loaded', [" . $random_id,
			"'gform_confirmation_loaded', [" . $form_id . ']'             => "'gform_confirmation_loaded', [" . $random_id . ']',
			'gf_apply_rules(' . $form_id . ', '                           => 'gf_apply_rules(' . $random_id . ', ',
			'gform_confirmation_wrapper_' . $form_id                      => 'gform_confirmation_wrapper_' . $random_id,
			'gforms_confirmation_message_' . $form_id                     => 'gforms_confirmation_message_' . $random_id,
			'gform_confirmation_message_' . $form_id                      => 'gform_confirmation_message_' . $random_id,
			'if(formId == ' . $form_id . ')'                              => 'if(formId == ' . $random_id . ')',
			"window['gf_form_conditional_logic'][" . $form_id . ']'       => "window['gf_form_conditional_logic'][" . $random_id . ']',
			"trigger('gform_post_conditional_logic', [" . $form_id . ', ' => "trigger('gform_post_conditional_logic', [" . $random_id . ', ',
			"gformShowPasswordStrength(\"input_" . $form_id . '_'         => 'gformShowPasswordStrength("input_' . $random_id . '_',
			"gformInitChosenFields('#input_" . $form_id . '_'             => "gformInitChosenFields('#input_" . $random_id . '_',
			"jQuery('#input_" . $form_id . '_'                            => "jQuery('#input_" . $random_id . '_',
			'gforms_calendar_icon_input_' . $form_id . '_'                => 'gforms_calendar_icon_input_' . $random_id . '_',
			"id='ginput_base_price_" . $form_id . '_'                     => "id='ginput_base_price_" . $random_id . '_',
			"id='ginput_quantity_" . $form_id . '_'                       => "id='ginput_quantity_" . $random_id . '_',
			'gfield_price_' . $form_id . '_'                              => 'gfield_price_' . $random_id . '_',
			'gfield_quantity_' . $form_id . '_'                           => 'gfield_quantity_' . $random_id . '_',
			'gfield_product_' . $form_id . '_'                            => 'gfield_product_' . $random_id . '_',
			'ginput_total_' . $form_id                                    => 'ginput_total_' . $random_id,
			'GFCalc(' . $form_id . ', '                                   => 'GFCalc(' . $random_id . ', ',
			'gf_global["number_formats"][' . $form_id . ']'               => 'gf_global["number_formats"][' . $random_id . ']',
			'gform_next_button_' . $form_id . '_'                         => 'gform_next_button_' . $random_id . '_',
			'gform_previous_button_' . $form_id . '_'                     => 'gform_previous_button_' . $random_id . '_',
			$hidden_field                                                 => "<input type='hidden' name='gform_random_id' value='" . $random_id . "' />" . $hidden_field,
			"data-formid='" . $form_id                                    => "data-formid='" . $random_id,
		);

		/**
		 * Add or override find/replace strings for the ID rewrite.
		 *
		 * @param array $strings Find => replace map.
		 * @param array $form    Form settings.
		 */
		$strings = apply_filters( 'pxtk_gravity_forms_multiple_instances_strings', $strings, $form );

		foreach ( $strings as $find => $replace ) {
			$form_string = str_replace( $find, $replace, $form_string );
		}

		return $form_string;
	}
}
