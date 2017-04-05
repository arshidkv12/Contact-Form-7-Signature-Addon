<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://www.keybored.fr/2016/08/14/WP-Contact-Form-Signature-Field.html
 * @since      4.0.0
 *
 * @package    Wpcf7_Signature
 * @subpackage Wpcf7_Signature/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Wpcf7_Signature
 * @subpackage Wpcf7_Signature/public
 * @author     Breizhtorm <tameroski@gmail.com>
 */
class Wpcf7_Signature_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    4.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    4.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/style.css', array(), $this->version, 'all' );

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    4.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script( $this->plugin_name.'-signature', plugin_dir_url( __FILE__ ) . 'js/signature_pad.min.js', array(), $this->version, false );
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/scripts.js', array( 'jquery', $this->plugin_name.'-signature' ), $this->version, false );

	}

	/**
	 * Add shortcode handler to CF7
	 *
	 * @since    4.0.0
	 */
	public function add_signature_shortcode_handler() {
		wpcf7_add_form_tag(
			array( 'signature', 'signature*' ),
			array($this, 'signature_shortcode_handler'), true 
		);
	}

	/**
	 * Signature Shortcode handler
	 *
	 * @since    4.0.0
	 */
	public function signature_shortcode_handler($tag) {

		$tag = new WPCF7_FormTag( $tag );

		if ( empty( $tag->name ) )
			return '';

		$validation_error = wpcf7_get_validation_error( $tag->name );

		$class = wpcf7_form_controls_class( $tag->type, 'wpcf7-signature' );

		if ( $validation_error )
			$class .= ' wpcf7-not-valid';

		$atts = array();

		$width = $tag->get_cols_option( '300' );
		$height = $tag->get_rows_option( '200' );

		$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

		if ( $tag->has_option( 'readonly' ) )
			$atts['readonly'] = 'readonly';

		if ( $tag->is_required() )
			$atts['aria-required'] = 'true';

		$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

		$value = (string) reset( $tag->values );

		if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
			$atts['placeholder'] = $value;
			$value = '';
		} elseif ( '' === $value ) {
			$value = $tag->get_default_option();
		}

		if ( wpcf7_is_posted() && isset( $_POST[$tag->name] ) )
			$value = wp_unslash( $_POST[$tag->name] );

		/* Input attributes */

		$atts['value'] = $value;
		$atts['type'] = 'hidden';
		$atts['name'] = $tag->name;
		$atts = wpcf7_format_atts( $atts );

		/* Canvas attributes */

		// Pen color
		$atts_canvas['data-color'] = $tag->get_option( 'color', '#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})', true );
		
		// Background color
		$atts_canvas['data-background'] = $tag->get_option( 'background', '#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})', true );

		$atts_canvas['id'] = ($tag->get_id_option() != '' ? $tag->get_id_option() : "wpcf7_".$tag->name."_signature");

		$canvas_class = $tag->name;
		$atts_canvas['class'] = $tag->get_class_option( $canvas_class );

		$atts_canvas = wpcf7_format_atts( $atts_canvas );

		/* Attachment attributes */

		$atts_attach['value'] = $tag->has_option( 'attachment' );
		$atts_attach['type'] = 'hidden';
		$atts_attach['name'] = $tag->name . "-attachment";
		$atts_attach = wpcf7_format_atts( $atts_attach );

		$html = sprintf(
			'<div class="wpcf7-form-control-signature-global-wrap" data-field-id="%1$s">
				<div class="wpcf7-form-control-signature-wrap" style="width:%5$spx;height:%6$spx;">
					<div class="wpcf7-form-control-signature-body">
						<canvas %8$s></canvas>
					</div>
				</div>
				<div class="wpcf7-form-control-clear-wrap">
					<input id="wpcf7_%4$s_clear" type="button" value="%7$s"/>
				</div>
			</div>
			<span class="wpcf7-form-control-wrap wpcf7-form-control-signature-input-wrap %1$s">
				<input %2$s id="wpcf7_input_%1$s"/><input %9$s id="wpcf7_input_%1$s_attachment"/>%3$s
			</span>
			',
			sanitize_html_class( $tag->name ), $atts, $validation_error, $tag->name, $width, $height, __( 'Clear', 'wpcf7-signature' ), $atts_canvas, $atts_attach );

		return $html;
	}

	/**
	 * Signature validation
	 *
	 * @since    4.0.0
	 */
	public function signature_validation( $result, $tag ) {
		$tag = new WPCF7_FormTag( $tag );

		$name = $tag->name;

		$value = isset( $_POST[$name] )
			? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
			: '';

		if ( 'signature*' == $tag->type ) {
			if ( '' == $value ) {
				if (method_exists($result,"invalidate")){
					$result->invalidate( $tag, wpcf7_get_message( 'invalid_required' ) );
					return $result;
				}else{
					$result['valid'] = false;
					$result['reason'][$name] = wpcf7_get_message( 'invalid_required' );
				}
			}
		}

		if ( isset( $result['reason'][$name] ) && $id = $tag->get_id_option() ) {
			$result['idref'][$name] = $id;
		}

		return $result;
	}

}
